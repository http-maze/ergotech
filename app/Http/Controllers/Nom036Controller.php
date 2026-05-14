<?php

namespace App\Http\Controllers;

use App\Models\Evaluacion;
use App\Models\Nom036Detalle;
use App\Models\Nom036Evaluacion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Exports\Nom036Export;
use App\Services\Reportes\Nom036ReportService;
use Maatwebsite\Excel\Facades\Excel;

class Nom036Controller extends Controller
{
    public function create($evaluacionId)
    {
        $evaluacion = Evaluacion::with([
            'empresa',
            'sucursal',
            'puesto',
            'trabajador',
            'metodo',
            'usuario',
        ])->findOrFail($evaluacionId);

        return view('nom036.create', compact('evaluacion'));
    }

    public function store(Request $request, $evaluacionId)
    {
        $evaluacion = Evaluacion::findOrFail($evaluacionId);

        $request->validate([
            'tareas' => 'required|array|min:1',
            'tareas.*' => 'string|in:levantar,bajar,transportar,empujar,jalar',
            'medio_ayuda' => 'nullable|string|max:255',
            'descripcion_apoyo' => 'nullable|string|max:255',
            'tarea_nombre' => 'nullable|string|max:255',
            'observaciones' => 'nullable|string',

            'lev_peso_frecuencia' => 'nullable|string',
            'lev_distancia_horizontal' => 'nullable|string',
            'lev_asimetria' => 'nullable|string',
            'lev_restricciones_posturales' => 'nullable|string',
            'lev_agarre_carga' => 'nullable|string',
            'lev_superficie_suelo' => 'nullable|string',
            'lev_factores_ambientales' => 'nullable|string',

            'baj_peso_frecuencia' => 'nullable|string',
            'baj_control_descenso' => 'nullable|string',
            'baj_asimetria' => 'nullable|string',
            'baj_restricciones_posturales' => 'nullable|string',
            'baj_agarre_carga' => 'nullable|string',
            'baj_superficie_suelo' => 'nullable|string',

            'tra_peso_frecuencia' => 'nullable|string',
            'tra_distancia_transporte' => 'nullable|string',
            'tra_asimetria' => 'nullable|string',
            'tra_agarre_carga' => 'nullable|string',
            'tra_superficie_suelo' => 'nullable|string',
            'tra_obstaculos' => 'nullable|string',
            'tra_factores_ambientales' => 'nullable|string',

            'emp_peso_carga' => 'nullable|string',
            'emp_postura' => 'nullable|string',
            'emp_agarre_mano' => 'nullable|string',
            'emp_patron_trabajo' => 'nullable|string',
            'emp_distancia_viaje' => 'nullable|string',
            'emp_condicion_equipo' => 'nullable|string',

            'jal_peso_carga' => 'nullable|string',
            'jal_postura' => 'nullable|string',
            'jal_agarre_mano' => 'nullable|string',
            'jal_patron_trabajo' => 'nullable|string',
            'jal_distancia_viaje' => 'nullable|string',
            'jal_condicion_equipo' => 'nullable|string',
        ]);

        DB::beginTransaction();

        try {
            $tareas = $request->tareas ?? [];
            $detalles = [];
            $puntaje = 0;

            foreach ($tareas as $tarea) {
                switch ($tarea) {
                    case 'levantar':
                        $campos = [
                            'lev_peso_frecuencia' => 'Peso y frecuencia de la carga',
                            'lev_distancia_horizontal' => 'Distancia horizontal entre las manos y la espalda baja',
                            'lev_asimetria' => 'Asimetría de la espalda o la carga',
                            'lev_restricciones_posturales' => 'Restricciones posturales por espacio disponible',
                            'lev_agarre_carga' => 'Agarre de la carga',
                            'lev_superficie_suelo' => 'Superficie del suelo',
                            'lev_factores_ambientales' => 'Factores ambientales',
                        ];
                        $seccion = 'Levantar';
                        break;

                    case 'bajar':
                        $campos = [
                            'baj_peso_frecuencia' => 'Peso y frecuencia de la carga',
                            'baj_control_descenso' => 'Control de descenso',
                            'baj_asimetria' => 'Asimetría',
                            'baj_restricciones_posturales' => 'Restricciones posturales',
                            'baj_agarre_carga' => 'Agarre de la carga',
                            'baj_superficie_suelo' => 'Superficie del suelo',
                        ];
                        $seccion = 'Bajar';
                        break;

                    case 'transportar':
                        $campos = [
                            'tra_peso_frecuencia' => 'Peso y frecuencia de la carga',
                            'tra_distancia_transporte' => 'Distancia de transporte',
                            'tra_asimetria' => 'Asimetría de la carga',
                            'tra_agarre_carga' => 'Agarre de la carga',
                            'tra_superficie_suelo' => 'Superficie del suelo',
                            'tra_obstaculos' => 'Obstáculos en la ruta',
                            'tra_factores_ambientales' => 'Factores ambientales',
                        ];
                        $seccion = 'Transportar';
                        break;

                    case 'empujar':
                        $campos = [
                            'emp_peso_carga' => 'Peso de la carga',
                            'emp_postura' => 'Postura',
                            'emp_agarre_mano' => 'Agarre de la mano',
                            'emp_patron_trabajo' => 'Patrón de trabajo',
                            'emp_distancia_viaje' => 'Distancia por viaje',
                            'emp_condicion_equipo' => 'Condición del equipo auxiliar',
                        ];
                        $seccion = 'Empujar';
                        break;

                    case 'jalar':
                        $campos = [
                            'jal_peso_carga' => 'Peso de la carga',
                            'jal_postura' => 'Postura',
                            'jal_agarre_mano' => 'Agarre de la mano',
                            'jal_patron_trabajo' => 'Patrón de trabajo',
                            'jal_distancia_viaje' => 'Distancia por viaje',
                            'jal_condicion_equipo' => 'Condición del equipo auxiliar',
                        ];
                        $seccion = 'Jalar';
                        break;

                    default:
                        $campos = [];
                        $seccion = 'General';
                        break;
                }

                foreach ($campos as $campo => $concepto) {
                    [$valorNumerico, $descripcion] = $this->separarValorDescripcion($request->$campo);
                    $puntaje += $valorNumerico;

                    $detalles[] = $this->detalleNom(
                        $seccion,
                        $concepto,
                        $descripcion,
                        $valorNumerico
                    );
                }
            }

            [$nivelRiesgo, $recomendacion] = $this->clasificarNom036($puntaje);

            $nom036 = Nom036Evaluacion::create([
                'evaluacion_id' => $evaluacion->id,
                'tipo_actividad' => implode(',', $tareas),
                'objeto_manipulado' => $request->tarea_nombre,
                'peso_carga' => null,
                'frecuencia' => null,
                'duracion' => null,
                'distancia_recorrida' => null,
                'altura_inicial' => null,
                'altura_final' => null,
                'postura_tronco' => null,
                'postura_brazos' => null,
                'postura_piernas' => null,
                'agarre' => null,
                'asimetria' => false,
                'movimientos_repetitivos' => false,
                'fuerza_brusca' => false,
                'condiciones_ambientales' => null,
                'superficie_trabajo' => null,
                'espacio_trabajo' => null,
                'nivel_riesgo' => $nivelRiesgo,
                'observaciones' => $request->observaciones,
            ]);

            $evaluacion->update([
                'resultado_final' => $puntaje,
                'nivel_riesgo' => $nivelRiesgo,
                'recomendaciones' => $recomendacion,
            ]);

            Nom036Detalle::create([
                'nom036_evaluacion_id' => $nom036->id,
                'seccion' => 'General',
                'concepto' => 'Tareas seleccionadas',
                'valor' => $this->tareasBonitas($tareas),
                'resultado' => null,
            ]);

            Nom036Detalle::create([
                'nom036_evaluacion_id' => $nom036->id,
                'seccion' => 'General',
                'concepto' => 'Tarea observada',
                'valor' => $request->tarea_nombre ?? 'No especificada',
                'resultado' => null,
            ]);

            Nom036Detalle::create([
                'nom036_evaluacion_id' => $nom036->id,
                'seccion' => 'General',
                'concepto' => 'Medio de ayuda utilizado',
                'valor' => $request->medio_ayuda ?? 'No especificado',
                'resultado' => null,
            ]);

            Nom036Detalle::create([
                'nom036_evaluacion_id' => $nom036->id,
                'seccion' => 'General',
                'concepto' => 'Descripción del apoyo o equipo utilizado',
                'valor' => $request->descripcion_apoyo ?? 'No especificada',
                'resultado' => null,
            ]);

            foreach ($detalles as $detalle) {
                Nom036Detalle::create([
                    'nom036_evaluacion_id' => $nom036->id,
                    'seccion' => $detalle['seccion'],
                    'concepto' => $detalle['concepto'],
                    'valor' => $detalle['valor'],
                    'resultado' => $detalle['resultado'],
                ]);
            }

            Nom036Detalle::create([
                'nom036_evaluacion_id' => $nom036->id,
                'seccion' => 'Resultado',
                'concepto' => 'Puntaje total',
                'valor' => (string) $puntaje,
                'resultado' => 'Calculado automáticamente',
            ]);

            Nom036Detalle::create([
                'nom036_evaluacion_id' => $nom036->id,
                'seccion' => 'Resultado',
                'concepto' => 'Nivel de riesgo',
                'valor' => $nivelRiesgo,
                'resultado' => 'Calculado automáticamente',
            ]);

            Nom036Detalle::create([
                'nom036_evaluacion_id' => $nom036->id,
                'seccion' => 'Resultado',
                'concepto' => 'Recomendación',
                'valor' => $recomendacion,
                'resultado' => 'Calculado automáticamente',
            ]);

            DB::commit();

            return redirect()->route('nom036.show', $nom036->id)
                ->with('success', 'Evaluación NOM-036 guardada correctamente.');
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Error al guardar: ' . $e->getMessage());
        }
    }

    public function show($id)
    {
        $nom036 = Nom036Evaluacion::with([
            'evaluacion.empresa',
            'evaluacion.sucursal',
            'evaluacion.puesto',
            'evaluacion.trabajador',
            'evaluacion.usuario',
            'detalles'
        ])->findOrFail($id);

        return view('nom036.show', compact('nom036'));
    }

    public function pdf($id)
{
    $nom036 = Nom036Evaluacion::with([
        'evaluacion.empresa',
        'evaluacion.sucursal',
        'evaluacion.puesto',
        'evaluacion.trabajador',
        'evaluacion.usuario',
        'detalles'
    ])->findOrFail($id);

    $pdf = Pdf::loadView('nom036.pdf', compact('nom036'))
        ->setPaper('a4', 'portrait');

    $empresa = $nom036->evaluacion->empresa->nombre ?? 'empresa';
    $fecha = now()->format('Y-m-d');
    $codigo = 'ERG-' . now()->format('Ymd-His');

    $nombreArchivo = 'reporte_' .
        $this->limpiarNombreArchivo($empresa) .
        '_NOM-036_' .
        $codigo .
        '_' .
        $fecha .
        '.pdf';

    return $pdf->download($nombreArchivo);
}

    private function clasificarNom036(int $puntaje): array
    {
        if ($puntaje <= 4) {
            return ['Bajo', 'La tarea puede mantenerse bajo observación.'];
        }

        if ($puntaje <= 8) {
            return ['Medio', 'Examinar las tareas con detenimiento.'];
        }

        if ($puntaje <= 12) {
            return ['Alto', 'Se requiere una acción correctiva pronta.'];
        }

        return ['Alto', 'Se necesita una acción inmediata. Se puede exponer a una proporción significativa de la población laboral a un riesgo de lesiones.'];
    }

    private function separarValorDescripcion($valor): array
    {
        if (!$valor || !str_contains($valor, '|')) {
            return [0, 'No especificado'];
        }

        [$numero, $descripcion] = explode('|', $valor, 2);

        return [(int) $numero, trim($descripcion)];
    }

    private function detalleNom(string $seccion, string $concepto, ?string $valor, $resultado): array
    {
        return [
            'seccion' => $seccion,
            'concepto' => $concepto,
            'valor' => $valor ?: 'No especificado',
            'resultado' => is_null($resultado) ? null : 'Valor: ' . $resultado,
        ];
    }

    private function tareasBonitas(array $tareas): string
    {
        $mapa = [
            'levantar' => 'Levantar',
            'bajar' => 'Bajar',
            'transportar' => 'Transportar',
            'empujar' => 'Empujar',
            'jalar' => 'Jalar',
        ];

        $nombres = [];
        foreach ($tareas as $tarea) {
            $nombres[] = $mapa[$tarea] ?? $tarea;
        }

        return implode(', ', $nombres);
    }

    public function excel($id, Nom036ReportService $reportService)
{
    $nom036 = $reportService->findOrFail((int) $id);
    $data = $reportService->build($nom036);

    $empresa = $nom036->evaluacion->empresa->nombre ?? 'empresa';
    $fecha = now()->format('Y-m-d');
    $codigo = 'ERG-' . now()->format('Ymd-His');

    $nombreArchivo = 'reporte_' .
        $this->limpiarNombreArchivo($empresa) .
        '_NOM-036_' .
        $codigo .
        '_' .
        $fecha .
        '.xlsx';

    return Excel::download(
        new Nom036Export($data),
        $nombreArchivo
    );
}

private function limpiarNombreArchivo($texto)
{
    $texto = strtolower($texto);
    $texto = str_replace([' ', '/', '\\', ':', '*', '?', '"', '<', '>', '|'], '_', $texto);
    $texto = preg_replace('/_+/', '_', $texto);

    return trim($texto, '_');
}
}