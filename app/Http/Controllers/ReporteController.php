<?php

namespace App\Http\Controllers;

use App\Models\Evaluacion;
use App\Models\Empresa;
use App\Models\Puesto;
use App\Exports\ReportesErgonomicosExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReporteController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $soloLectura = ($user->rol->nombre ?? '') === 'visitante';

        $evaluacionesData = $this->obtenerEvaluacionesData();

        $totalEvaluaciones = $evaluacionesData->count();

        $riesgosAltos = $evaluacionesData->filter(function ($eval) {
            $riesgo = strtolower($eval['nivel_riesgo'] ?? '');
            return str_contains($riesgo, 'alto')
                || str_contains($riesgo, 'muy alto')
                || str_contains($riesgo, 'no aceptable');
        })->count();

        $totalEmpresas = $evaluacionesData->pluck('empresa_nombre')
            ->filter(fn($v) => $v !== 'N/A')
            ->unique()
            ->count();

        $totalPuestos = $evaluacionesData->pluck('puesto_nombre')
            ->filter(fn($v) => $v !== 'N/A')
            ->unique()
            ->count();

        $metodoMasUsado = $evaluacionesData
            ->groupBy('metodo')
            ->map(fn($items) => $items->count())
            ->sortDesc()
            ->keys()
            ->first() ?? 'N/A';

        $nivelPredominante = $evaluacionesData
            ->groupBy('nivel_riesgo')
            ->map(fn($items) => $items->count())
            ->sortDesc()
            ->keys()
            ->first() ?? 'N/A';

        $puestosCriticos = $evaluacionesData
            ->groupBy('puesto_nombre')
            ->map(function ($items, $puesto) {
                $altos = $items->filter(function ($eval) {
                    $riesgo = strtolower($eval['nivel_riesgo'] ?? '');
                    return str_contains($riesgo, 'alto')
                        || str_contains($riesgo, 'muy alto')
                        || str_contains($riesgo, 'no aceptable');
                })->count();

                return [
                    'puesto' => $puesto,
                    'total' => $items->count(),
                    'riesgos_altos' => $altos,
                    'porcentaje' => $items->count() > 0
                        ? round(($altos / $items->count()) * 100, 1)
                        : 0,
                ];
            })
            ->sortByDesc('riesgos_altos')
            ->take(5)
            ->values();

        $empresas = Empresa::orderBy('nombre')->get();
        $puestos = Puesto::orderBy('nombre')->get();

        return view('reportes.index', compact(
            'evaluacionesData',
            'empresas',
            'puestos',
            'soloLectura',
            'totalEvaluaciones',
            'riesgosAltos',
            'totalEmpresas',
            'totalPuestos',
            'metodoMasUsado',
            'nivelPredominante',
            'puestosCriticos'
        ));
    }

    public function excel(Request $request)
    {
        $evaluacionesData = $this->obtenerEvaluacionesData();

        if ($request->empresa) {
            $evaluacionesData = $evaluacionesData->where('empresa_nombre', $request->empresa);
        }

        if ($request->puesto) {
            $evaluacionesData = $evaluacionesData->where('puesto_nombre', $request->puesto);
        }

        if ($request->filled('metodos')) {
            $metodosSeleccionados = collect($request->metodos)
                ->map(fn($m) => strtolower(trim($m)))
                ->toArray();

            $evaluacionesData = $evaluacionesData->filter(function ($e) use ($metodosSeleccionados) {
                return in_array(strtolower(trim($e['metodo'] ?? '')), $metodosSeleccionados);
            });
        } elseif ($request->metodo) {
            $evaluacionesData = $evaluacionesData->filter(fn($e) =>
                strtolower($e['metodo'] ?? '') === strtolower($request->metodo)
            );
        }

        if ($request->fecha) {
            $evaluacionesData = $evaluacionesData->where('fecha', $request->fecha);
        }

        if ($request->riesgo) {
            $evaluacionesData = $evaluacionesData->filter(fn($e) =>
                str_contains(strtolower($e['nivel_riesgo'] ?? ''), strtolower($request->riesgo))
            );
        }

        $empresaNombre = $request->empresa ?: 'todas_las_empresas';

        if ($request->filled('metodos')) {
            $metodosNombre = implode('_', $request->metodos);
        } elseif ($request->metodo) {
            $metodosNombre = $request->metodo;
        } else {
            $metodosNombre = 'todos_los_metodos';
        }

        $fecha = now()->format('Y-m-d');
        $codigo = 'ERG-' . now()->format('Ymd-His');

        $nombreArchivo = 'reporte_' .
            $this->limpiarNombreArchivo($empresaNombre) . '_' .
            $this->limpiarNombreArchivo($metodosNombre) . '_' .
            $codigo . '_' .
            $fecha . '.xlsx';

        return Excel::download(
            new ReportesErgonomicosExport($evaluacionesData->values()),
            $nombreArchivo
        );
    }

    private function obtenerEvaluacionesData()
    {
        $evaluaciones = Evaluacion::with([
            'empresa',
            'puesto',
            'trabajador',
            'metodo',
            'rebaEvaluacion',
            'rulaEvaluacion',
            'owasEvaluacion',
            'nioshEvaluacion',
            'nom036',
            'leySilla',
        ])->latest()->get();

        return $evaluaciones->map(function ($eval) {
            $metodo = strtoupper($eval->metodo->nombre ?? 'N/A');

            $resultado = $eval->resultado_final ?? null;
            $nivelRiesgo = $eval->nivel_riesgo ?? 'N/A';
            $accion = $eval->recomendaciones ?? 'Sin recomendación registrada';

            if (!$resultado) {
                switch ($metodo) {
                    case 'REBA':
                        $resultado = $eval->rebaEvaluacion->puntuacion_final ?? 'N/A';
                        break;

                    case 'RULA':
                        $resultado = $eval->rulaEvaluacion->puntuacion_final ?? 'N/A';
                        break;

                    case 'OWAS':
                        $resultado = $eval->owasEvaluacion->categoria_riesgo ?? 'N/A';
                        break;

                    case 'NIOSH':
                        $resultado = $eval->nioshEvaluacion->indice_levantamiento ?? 'N/A';
                        break;

                    case 'NOM-036':
                    case 'NOM036':
                    case 'NOM 036':
                        $resultado = $eval->nom036->resultado_final ?? 'N/A';
                        break;

                    case 'LEY SILLA':
                        $resultado = $eval->leySilla->resultado_cumplimiento ?? 'N/A';
                        $nivelRiesgo = $eval->leySilla->nivel_riesgo ?? $nivelRiesgo;
                        $accion = $eval->leySilla->recomendaciones ?? $accion;
                        break;

                    default:
                        $resultado = 'N/A';
                        break;
                }
            }

            return [
                'id' => $eval->id,
                'empresa_nombre' => $eval->empresa->nombre ?? 'N/A',
                'puesto_nombre' => $eval->puesto->nombre ?? 'N/A',
                'trabajador_nombre' => trim(
                    ($eval->trabajador->nombre ?? '') . ' ' .
                    ($eval->trabajador->apellido_paterno ?? '') . ' ' .
                    ($eval->trabajador->apellido_materno ?? '')
                ) ?: 'N/A',
                'fecha' => $eval->fecha_evaluacion ?? 'N/A',
                'area' => $eval->area_evaluada ?? 'N/A',
                'actividad' => $eval->actividad ?? 'N/A',
                'resultado' => $resultado ?? 'N/A',
                'nivel_riesgo' => $nivelRiesgo,
                'accion_recomendada' => $accion,
                'observaciones' => $eval->observaciones ?? '',
                'metodo' => $eval->metodo->nombre ?? 'N/A',
            ];
        })->values();
    }

    private function limpiarNombreArchivo($texto)
    {
        $texto = strtolower($texto);
        $texto = str_replace(
            [' ', '/', '\\', ':', '*', '?', '"', '<', '>', '|'],
            '_',
            $texto
        );
        $texto = preg_replace('/_+/', '_', $texto);

        return trim($texto, '_');
    }
}