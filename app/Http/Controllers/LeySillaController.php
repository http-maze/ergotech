<?php

namespace App\Http\Controllers;

use App\Models\Evaluacion;
use App\Models\LeySillaDetalle;
use App\Models\LeySillaEvaluacion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;

class LeySillaController extends Controller
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

        return view('ley_silla.create', compact('evaluacion'));
    }

    public function store(Request $request, $evaluacionId)
    {
        $evaluacion = Evaluacion::findOrFail($evaluacionId);

        $request->validate([
            'tipo_puesto' => 'nullable|string|max:255',
            'horas_de_pie' => 'required|numeric|min:0|max:24',
            'observaciones' => 'nullable|string',
        ]);

        DB::beginTransaction();

        try {
            $campos = [
                'cuenta_con_silla',
                'silla_con_respaldo',
                'silla_en_area_cercana',
                'sillas_suficientes',
                'silla_en_buen_estado',
                'permite_sentarse',
                'permite_pausas',
                'pausas_definidas',
                'alternancia_postural',
                'reglamento_actualizado',
                'evidencia_documental',
                'capacitacion_trabajadores',
            ];

            $puntaje = 0;

            foreach ($campos as $campo) {
                if ($request->boolean($campo)) {
                    $puntaje++;
                }
            }

            $bipedestacionProlongada = $request->horas_de_pie > 3;

            [$resultado, $nivelRiesgo, $recomendaciones] = $this->clasificarLeySilla(
                $puntaje,
                $bipedestacionProlongada,
                $request
            );

            $leySilla = LeySillaEvaluacion::create([
                'evaluacion_id' => $evaluacion->id,
                'tipo_puesto' => $request->tipo_puesto,
                'horas_de_pie' => $request->horas_de_pie,
                'bipedestacion_prolongada' => $bipedestacionProlongada,

                'cuenta_con_silla' => $request->boolean('cuenta_con_silla'),
                'silla_con_respaldo' => $request->boolean('silla_con_respaldo'),
                'silla_en_area_cercana' => $request->boolean('silla_en_area_cercana'),
                'sillas_suficientes' => $request->boolean('sillas_suficientes'),
                'silla_en_buen_estado' => $request->boolean('silla_en_buen_estado'),

                'permite_sentarse' => $request->boolean('permite_sentarse'),
                'permite_pausas' => $request->boolean('permite_pausas'),
                'pausas_definidas' => $request->boolean('pausas_definidas'),
                'alternancia_postural' => $request->boolean('alternancia_postural'),

                'reglamento_actualizado' => $request->boolean('reglamento_actualizado'),
                'evidencia_documental' => $request->boolean('evidencia_documental'),
                'capacitacion_trabajadores' => $request->boolean('capacitacion_trabajadores'),

                'puntaje_total' => $puntaje,
                'resultado_cumplimiento' => $resultado,
                'nivel_riesgo' => $nivelRiesgo,
                'recomendaciones' => $recomendaciones,
                'observaciones' => $request->observaciones,
            ]);

            $this->guardarDetalles($leySilla, $request, $bipedestacionProlongada);

            $evaluacion->update([
                'resultado_final' => $puntaje . ' / 12',
                'nivel_riesgo' => $nivelRiesgo,
                'recomendaciones' => $recomendaciones,
                'observaciones' => $request->observaciones,
            ]);

            DB::commit();

            return redirect()
                ->route('ley_silla.show', $leySilla->id)
                ->with('success', 'Evaluación Ley Silla registrada correctamente.');

        } catch (\Exception $e) {
            DB::rollBack();

            return back()
                ->withInput()
                ->with('error', 'Error al guardar la evaluación: ' . $e->getMessage());
        }
    }

    public function show($id)
    {
        $leySilla = LeySillaEvaluacion::with([
            'evaluacion.empresa',
            'evaluacion.sucursal',
            'evaluacion.puesto',
            'evaluacion.trabajador',
            'evaluacion.metodo',
            'evaluacion.usuario',
            'detalles',
        ])->findOrFail($id);

        return view('ley_silla.show', compact('leySilla'));
    }

    public function pdf($id)
    {
        $leySilla = LeySillaEvaluacion::with([
            'evaluacion.empresa',
            'evaluacion.sucursal',
            'evaluacion.puesto',
            'evaluacion.trabajador',
            'evaluacion.metodo',
            'evaluacion.usuario',
            'detalles',
        ])->findOrFail($id);

        $empresa = $leySilla->evaluacion->empresa->nombre ?? 'empresa';
        $fecha = now()->format('Y-m-d');

        $nombreArchivo = 'ley_silla_' . str_replace(' ', '_', strtolower($empresa)) . '_' . $fecha . '.pdf';

        $pdf = Pdf::loadView('ley_silla.pdf', compact('leySilla'))
            ->setPaper('letter', 'portrait');

        return $pdf->download($nombreArchivo);
    }

    private function clasificarLeySilla($puntaje, $bipedestacionProlongada, Request $request): array
    {
        if (!$request->boolean('cuenta_con_silla') || !$request->boolean('silla_con_respaldo') || !$request->boolean('permite_sentarse')) {
            return [
                'No cumple',
                'Alto',
                'Proporcionar sillas con respaldo suficientes, permitir que el personal pueda sentarse durante pausas o lapsos sin actividad, y corregir de inmediato las condiciones de bipedestación prolongada.'
            ];
        }

        if ($bipedestacionProlongada && $puntaje < 9) {
            return [
                'Cumple parcialmente',
                'Medio',
                'Mejorar la disponibilidad de sillas, definir pausas, actualizar el reglamento interior de trabajo y documentar la evidencia de cumplimiento.'
            ];
        }

        if ($puntaje >= 10) {
            return [
                'Cumple',
                'Bajo',
                'Mantener las condiciones actuales, conservar evidencia documental y verificar periódicamente que las sillas estén disponibles, funcionales y en buen estado.'
            ];
        }

        return [
            'Cumple parcialmente',
            'Medio',
            'Reforzar las medidas de descanso, alternancia postural, capacitación y documentación para asegurar el cumplimiento completo.'
        ];
    }

    private function guardarDetalles(LeySillaEvaluacion $leySilla, Request $request, bool $bipedestacionProlongada): void
    {
        $detalles = [
            ['General', 'Tipo de puesto', $request->tipo_puesto ?? 'No especificado', null],
            ['General', 'Horas de pie durante la jornada', $request->horas_de_pie . ' horas', null],
            ['General', 'Bipedestación prolongada', $bipedestacionProlongada ? 'Sí' : 'No', null],

            ['Disponibilidad de silla', 'Cuenta con silla o asiento', $request->boolean('cuenta_con_silla') ? 'Sí' : 'No', $request->boolean('cuenta_con_silla') ? 1 : 0],
            ['Disponibilidad de silla', 'La silla tiene respaldo', $request->boolean('silla_con_respaldo') ? 'Sí' : 'No', $request->boolean('silla_con_respaldo') ? 1 : 0],
            ['Disponibilidad de silla', 'La silla está en el puesto o área cercana', $request->boolean('silla_en_area_cercana') ? 'Sí' : 'No', $request->boolean('silla_en_area_cercana') ? 1 : 0],
            ['Disponibilidad de silla', 'Hay sillas suficientes', $request->boolean('sillas_suficientes') ? 'Sí' : 'No', $request->boolean('sillas_suficientes') ? 1 : 0],
            ['Disponibilidad de silla', 'La silla está en buen estado', $request->boolean('silla_en_buen_estado') ? 'Sí' : 'No', $request->boolean('silla_en_buen_estado') ? 1 : 0],

            ['Descanso y pausas', 'Se permite sentarse', $request->boolean('permite_sentarse') ? 'Sí' : 'No', $request->boolean('permite_sentarse') ? 1 : 0],
            ['Descanso y pausas', 'Se permiten pausas', $request->boolean('permite_pausas') ? 'Sí' : 'No', $request->boolean('permite_pausas') ? 1 : 0],
            ['Descanso y pausas', 'Las pausas están definidas', $request->boolean('pausas_definidas') ? 'Sí' : 'No', $request->boolean('pausas_definidas') ? 1 : 0],
            ['Descanso y pausas', 'Existe alternancia sentado/de pie', $request->boolean('alternancia_postural') ? 'Sí' : 'No', $request->boolean('alternancia_postural') ? 1 : 0],

            ['Documentación', 'Reglamento actualizado', $request->boolean('reglamento_actualizado') ? 'Sí' : 'No', $request->boolean('reglamento_actualizado') ? 1 : 0],
            ['Documentación', 'Existe evidencia documental', $request->boolean('evidencia_documental') ? 'Sí' : 'No', $request->boolean('evidencia_documental') ? 1 : 0],
            ['Documentación', 'Trabajadores capacitados/informados', $request->boolean('capacitacion_trabajadores') ? 'Sí' : 'No', $request->boolean('capacitacion_trabajadores') ? 1 : 0],
        ];

        foreach ($detalles as $detalle) {
            LeySillaDetalle::create([
                'ley_silla_evaluacion_id' => $leySilla->id,
                'seccion' => $detalle[0],
                'concepto' => $detalle[1],
                'valor' => $detalle[2],
                'puntaje' => $detalle[3],
            ]);
        }
    }
}