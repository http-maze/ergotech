<?php

namespace App\Http\Controllers;

use App\Models\Evaluacion;
use App\Models\RulaDetalle;
use App\Models\RulaEvaluacion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Exports\RulaExport;
use App\Services\Reportes\RulaReportService;
use Maatwebsite\Excel\Facades\Excel;

class RulaController extends Controller
{
    public function index()
    {
        $rulas = RulaEvaluacion::with([
            'evaluacion.empresa',
            'evaluacion.sucursal',
            'evaluacion.puesto',
            'evaluacion.trabajador',
            'evaluacion.usuario'
        ])->latest()->paginate(10);

        return view('rula.index', compact('rulas'));
    }

    public function create($evaluacionId)
    {
        $evaluacion = Evaluacion::with([
            'empresa',
            'sucursal',
            'puesto',
            'trabajador',
            'metodo',
            'usuario'
        ])->findOrFail($evaluacionId);

        return view('rula.create', compact('evaluacion'));
    }

    public function calcular(Request $request)
    {
        $resultado = $this->calcularRulaDesdeFormulario($request->all());
        return response()->json($resultado);
    }

    public function store(Request $request, $evaluacionId)
    {
        $evaluacion = Evaluacion::findOrFail($evaluacionId);

        $request->validate([
            'lado_evaluado' => 'required|string|max:50',
            'tarea' => 'nullable|string|max:255',

            'brazo_base' => 'required|integer|min:1|max:4',
            'brazo_hombro_elevado' => 'nullable|integer|min:0|max:1',
            'brazo_abducido' => 'nullable|integer|min:0|max:1',
            'brazo_apoyo' => 'nullable|integer|min:0|max:1',

            'antebrazo_base' => 'required|integer|min:1|max:2',
            'antebrazo_fuera_cuerpo' => 'nullable|integer|min:0|max:1',
            'antebrazo_cruza_linea_media' => 'nullable|integer|min:0|max:1',

            'muneca_base' => 'required|integer|min:1|max:3',
            'muneca_desviacion' => 'nullable|integer|min:0|max:1',

            'giro_muneca' => 'required|integer|min:1|max:2',

            'cuello_base' => 'required|integer|min:1|max:4',
            'cuello_rotado' => 'nullable|integer|min:0|max:1',
            'cuello_inclinado' => 'nullable|integer|min:0|max:1',

            'tronco_base' => 'required|integer|min:1|max:4',
            'tronco_rotado' => 'nullable|integer|min:0|max:1',
            'tronco_inclinado' => 'nullable|integer|min:0|max:1',

            'piernas' => 'required|integer|min:1|max:2',
            'uso_muscular' => 'required|integer|min:0|max:1',
            'carga_fuerza' => 'required|integer|min:0|max:3',
        ]);

        DB::beginTransaction();

        try {
            $resultado = $this->calcularRulaDesdeFormulario($request->all());

            $rula = RulaEvaluacion::create([
                'evaluacion_id' => $evaluacion->id,
                'brazo' => $resultado['brazo'],
                'antebrazo' => $resultado['antebrazo'],
                'muneca' => $resultado['muneca'],
                'giro_muneca' => $resultado['giro_muneca'],
                'cuello' => $resultado['cuello'],
                'tronco' => $resultado['tronco'],
                'piernas' => $resultado['piernas'],
                'uso_muscular' => $resultado['uso_muscular'],
                'carga_fuerza' => $resultado['carga_fuerza'],
                'puntuacion_a' => $resultado['puntuacion_a'],
                'puntuacion_b' => $resultado['puntuacion_b'],
                'puntuacion_c' => $resultado['puntuacion_c'],
                'puntuacion_d' => $resultado['puntuacion_d'],
                'puntuacion_final' => $resultado['puntuacion_final'],
                'nivel_accion' => $resultado['nivel_accion'],
            ]);

            $evaluacion->update([
                'resultado_final' => $resultado['puntuacion_final'],
                'nivel_riesgo' => $resultado['nivel_riesgo'],
                'recomendaciones' => $resultado['accion_requerida'],
            ]);

            $detalles = [
                ['GENERAL', 'lado_evaluado', $request->lado_evaluado, 0],
                ['GENERAL', 'tarea', $request->tarea ?? 'No especificada', 0],
                ['GENERAL', 'area_evaluada', $evaluacion->area_evaluada ?? 'No especificada', 0],
                ['GENERAL', 'actividad_general', $evaluacion->actividad ?? 'No especificada', 0],

                ['A', 'brazo_base', $this->textoBrazoBase($request->brazo_base), (int)$request->brazo_base],
                ['A', 'brazo_hombro_elevado', $this->textoBool($request->brazo_hombro_elevado, 'Hombro elevado o brazo rotado'), (int)($request->brazo_hombro_elevado ?? 0)],
                ['A', 'brazo_abducido', $this->textoBool($request->brazo_abducido, 'Brazo abducido'), (int)($request->brazo_abducido ?? 0)],
                ['A', 'brazo_apoyo', $this->textoApoyo($request->brazo_apoyo), (int)($request->brazo_apoyo ?? 0)],
                ['A', 'brazo_resultado', 'Puntuación final del brazo', $resultado['brazo']],

                ['A', 'antebrazo_base', $this->textoAntebrazoBase($request->antebrazo_base), (int)$request->antebrazo_base],
                ['A', 'antebrazo_fuera_cuerpo', $this->textoBool($request->antebrazo_fuera_cuerpo, 'A un lado del cuerpo'), (int)($request->antebrazo_fuera_cuerpo ?? 0)],
                ['A', 'antebrazo_cruza_linea_media', $this->textoBool($request->antebrazo_cruza_linea_media, 'Cruza la línea media'), (int)($request->antebrazo_cruza_linea_media ?? 0)],
                ['A', 'antebrazo_resultado', 'Puntuación final del antebrazo', $resultado['antebrazo']],

                ['A', 'muneca_base', $this->textoMunecaBase($request->muneca_base), (int)$request->muneca_base],
                ['A', 'muneca_desviacion', $this->textoBool($request->muneca_desviacion, 'Desviación radial o cubital'), (int)($request->muneca_desviacion ?? 0)],
                ['A', 'muneca_resultado', 'Puntuación final de la muñeca', $resultado['muneca']],

                ['A', 'giro_muneca', $this->textoGiroMuneca($request->giro_muneca), (int)$request->giro_muneca],

                ['B', 'cuello_base', $this->textoCuelloBase($request->cuello_base), (int)$request->cuello_base],
                ['B', 'cuello_rotado', $this->textoBool($request->cuello_rotado, 'Cabeza rotada'), (int)($request->cuello_rotado ?? 0)],
                ['B', 'cuello_inclinado', $this->textoBool($request->cuello_inclinado, 'Cabeza con inclinación lateral'), (int)($request->cuello_inclinado ?? 0)],
                ['B', 'cuello_resultado', 'Puntuación final del cuello', $resultado['cuello']],

                ['B', 'tronco_base', $this->textoTroncoBase($request->tronco_base), (int)$request->tronco_base],
                ['B', 'tronco_rotado', $this->textoBool($request->tronco_rotado, 'Tronco rotado'), (int)($request->tronco_rotado ?? 0)],
                ['B', 'tronco_inclinado', $this->textoBool($request->tronco_inclinado, 'Tronco con inclinación lateral'), (int)($request->tronco_inclinado ?? 0)],
                ['B', 'tronco_resultado', 'Puntuación final del tronco', $resultado['tronco']],

                ['B', 'piernas', $this->textoPiernas($request->piernas), (int)$request->piernas],

                ['C', 'uso_muscular', $this->textoUsoMuscular($request->uso_muscular), (int)$request->uso_muscular],
                ['D', 'carga_fuerza', $this->textoCargaFuerza($request->carga_fuerza), (int)$request->carga_fuerza],

                ['RESULTADO', 'puntuacion_a', 'Resultado Grupo A', $resultado['puntuacion_a']],
                ['RESULTADO', 'puntuacion_b', 'Resultado Grupo B', $resultado['puntuacion_b']],
                ['RESULTADO', 'puntuacion_c', 'Resultado C', $resultado['puntuacion_c']],
                ['RESULTADO', 'puntuacion_d', 'Resultado D', $resultado['puntuacion_d']],
                ['RESULTADO', 'puntuacion_final', $resultado['nivel_riesgo'], $resultado['puntuacion_final']],
            ];

            foreach ($detalles as $detalle) {
                RulaDetalle::create([
                    'rula_evaluacion_id' => $rula->id,
                    'seccion' => $detalle[0],
                    'concepto' => $detalle[1],
                    'valor' => $detalle[2],
                    'puntaje' => $detalle[3],
                ]);
            }

            DB::commit();

            return redirect()->route('rula.show', $rula->id)
                ->with('success', 'Evaluación RULA guardada correctamente.');
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Error al guardar: ' . $e->getMessage());
        }
    }

    public function show($id)
    {
        $rula = RulaEvaluacion::with([
            'evaluacion.empresa',
            'evaluacion.sucursal',
            'evaluacion.puesto',
            'evaluacion.trabajador',
            'evaluacion.usuario',
            'detalles'
        ])->findOrFail($id);

        return view('rula.show', compact('rula'));
    }

    public function pdf($id)
{
    $rula = RulaEvaluacion::with([
        'evaluacion.empresa',
        'evaluacion.sucursal',
        'evaluacion.puesto',
        'evaluacion.trabajador',
        'evaluacion.usuario',
        'detalles'
    ])->findOrFail($id);

    $pdf = Pdf::loadView('rula.pdf', compact('rula'))
        ->setPaper('a4', 'portrait');

    $empresa = $rula->evaluacion->empresa->nombre ?? 'empresa';
    $fecha = now()->format('Y-m-d');
    $codigo = 'ERG-' . now()->format('Ymd-His');

    $nombreArchivo = 'reporte_' .
        $this->limpiarNombreArchivo($empresa) .
        '_RULA_' .
        $codigo .
        '_' .
        $fecha .
        '.pdf';

    return $pdf->download($nombreArchivo);
}

    private function calcularRulaDesdeFormulario(array $data): array
    {
        $brazo = (int)($data['brazo_base'] ?? 1);
        if ((int)($data['brazo_hombro_elevado'] ?? 0) === 1) $brazo += 1;
        if ((int)($data['brazo_abducido'] ?? 0) === 1) $brazo += 1;
        if ((int)($data['brazo_apoyo'] ?? 0) === 1) $brazo -= 1;
        $brazo = max(1, min(6, $brazo));

        $antebrazo = (int)($data['antebrazo_base'] ?? 1);
        if ((int)($data['antebrazo_fuera_cuerpo'] ?? 0) === 1) $antebrazo += 1;
        if ((int)($data['antebrazo_cruza_linea_media'] ?? 0) === 1) $antebrazo += 1;
        $antebrazo = max(1, min(3, $antebrazo));

        $muneca = (int)($data['muneca_base'] ?? 1);
        if ((int)($data['muneca_desviacion'] ?? 0) === 1) $muneca += 1;
        $muneca = max(1, min(4, $muneca));

        $giroMuneca = max(1, min(2, (int)($data['giro_muneca'] ?? 1)));

        $cuello = (int)($data['cuello_base'] ?? 1);
        if ((int)($data['cuello_rotado'] ?? 0) === 1) $cuello += 1;
        if ((int)($data['cuello_inclinado'] ?? 0) === 1) $cuello += 1;
        $cuello = max(1, min(6, $cuello));

        $tronco = (int)($data['tronco_base'] ?? 1);
        if ((int)($data['tronco_rotado'] ?? 0) === 1) $tronco += 1;
        if ((int)($data['tronco_inclinado'] ?? 0) === 1) $tronco += 1;
        $tronco = max(1, min(6, $tronco));

        $piernas = max(1, min(2, (int)($data['piernas'] ?? 1)));
        $usoMuscular = max(0, min(1, (int)($data['uso_muscular'] ?? 0)));
        $cargaFuerza = max(0, min(3, (int)($data['carga_fuerza'] ?? 0)));

        $matrices = $this->getRulaMatrices();

        $puntuacionA = $matrices['tablaA'][$brazo][$antebrazo][$muneca][$giroMuneca];
        $puntuacionB = $matrices['tablaB'][$cuello][$tronco][$piernas];

        $puntuacionC = min(8, $puntuacionA + $usoMuscular);
        $puntuacionD = min(7, $puntuacionB + $cargaFuerza);
        $puntuacionFinal = $matrices['tablaFinal'][$puntuacionC][$puntuacionD];

        $clasificacion = $this->clasificarRiesgo($puntuacionFinal);

        return [
            'brazo' => $brazo,
            'antebrazo' => $antebrazo,
            'muneca' => $muneca,
            'giro_muneca' => $giroMuneca,
            'cuello' => $cuello,
            'tronco' => $tronco,
            'piernas' => $piernas,
            'uso_muscular' => $usoMuscular,
            'carga_fuerza' => $cargaFuerza,
            'puntuacion_a' => $puntuacionA,
            'puntuacion_b' => $puntuacionB,
            'puntuacion_c' => $puntuacionC,
            'puntuacion_d' => $puntuacionD,
            'puntuacion_final' => $puntuacionFinal,
            'nivel_accion' => $clasificacion['nivel_accion'],
            'nivel_riesgo' => $clasificacion['nivel'],
            'accion_requerida' => $clasificacion['accion'],
        ];
    }

    private function clasificarRiesgo(int $puntaje): array
    {
        if ($puntaje <= 2) {
            return ['nivel_accion' => 1, 'nivel' => 'Bajo', 'accion' => 'La postura es aceptable si no se mantiene o repite durante largos periodos.'];
        }
        if ($puntaje <= 4) {
            return ['nivel_accion' => 2, 'nivel' => 'Medio', 'accion' => 'Es necesaria una investigación adicional y pueden requerirse cambios.'];
        }
        if ($puntaje <= 6) {
            return ['nivel_accion' => 3, 'nivel' => 'Alto', 'accion' => 'Se requiere investigar y realizar cambios pronto.'];
        }
        return ['nivel_accion' => 4, 'nivel' => 'Muy alto', 'accion' => 'Se requieren cambios inmediatos.'];
    }

    private function getRulaMatrices(): array
    {
        return [
            'tablaA' => [
                1 => [
                    1 => [1 => [1 => 1, 2 => 2], 2 => [1 => 2, 2 => 2], 3 => [1 => 2, 2 => 3], 4 => [1 => 3, 2 => 3]],
                    2 => [1 => [1 => 2, 2 => 2], 2 => [1 => 2, 2 => 2], 3 => [1 => 3, 2 => 3], 4 => [1 => 3, 2 => 3]],
                    3 => [1 => [1 => 2, 2 => 3], 2 => [1 => 3, 2 => 3], 3 => [1 => 3, 2 => 3], 4 => [1 => 4, 2 => 4]],
                ],
                2 => [
                    1 => [1 => [1 => 2, 2 => 3], 2 => [1 => 3, 2 => 3], 3 => [1 => 3, 2 => 3], 4 => [1 => 4, 2 => 4]],
                    2 => [1 => [1 => 3, 2 => 3], 2 => [1 => 3, 2 => 3], 3 => [1 => 3, 2 => 4], 4 => [1 => 4, 2 => 4]],
                    3 => [1 => [1 => 3, 2 => 4], 2 => [1 => 4, 2 => 4], 3 => [1 => 4, 2 => 4], 4 => [1 => 4, 2 => 5]],
                ],
                3 => [
                    1 => [1 => [1 => 3, 2 => 3], 2 => [1 => 3, 2 => 3], 3 => [1 => 3, 2 => 4], 4 => [1 => 4, 2 => 4]],
                    2 => [1 => [1 => 3, 2 => 3], 2 => [1 => 3, 2 => 4], 3 => [1 => 4, 2 => 4], 4 => [1 => 4, 2 => 4]],
                    3 => [1 => [1 => 4, 2 => 4], 2 => [1 => 4, 2 => 4], 3 => [1 => 4, 2 => 4], 4 => [1 => 4, 2 => 5]],
                ],
                4 => [
                    1 => [1 => [1 => 4, 2 => 4], 2 => [1 => 4, 2 => 4], 3 => [1 => 4, 2 => 5], 4 => [1 => 5, 2 => 5]],
                    2 => [1 => [1 => 4, 2 => 4], 2 => [1 => 4, 2 => 5], 3 => [1 => 5, 2 => 5], 4 => [1 => 5, 2 => 5]],
                    3 => [1 => [1 => 5, 2 => 5], 2 => [1 => 5, 2 => 5], 3 => [1 => 5, 2 => 6], 4 => [1 => 6, 2 => 6]],
                ],
                5 => [
                    1 => [1 => [1 => 5, 2 => 5], 2 => [1 => 5, 2 => 5], 3 => [1 => 5, 2 => 6], 4 => [1 => 6, 2 => 6]],
                    2 => [1 => [1 => 5, 2 => 6], 2 => [1 => 6, 2 => 6], 3 => [1 => 6, 2 => 7], 4 => [1 => 7, 2 => 7]],
                    3 => [1 => [1 => 6, 2 => 6], 2 => [1 => 6, 2 => 7], 3 => [1 => 7, 2 => 7], 4 => [1 => 7, 2 => 8]],
                ],
                6 => [
                    1 => [1 => [1 => 7, 2 => 7], 2 => [1 => 7, 2 => 8], 3 => [1 => 8, 2 => 8], 4 => [1 => 8, 2 => 9]],
                    2 => [1 => [1 => 8, 2 => 8], 2 => [1 => 8, 2 => 9], 3 => [1 => 9, 2 => 9], 4 => [1 => 9, 2 => 9]],
                    3 => [1 => [1 => 9, 2 => 9], 2 => [1 => 9, 2 => 9], 3 => [1 => 9, 2 => 9], 4 => [1 => 9, 2 => 9]],
                ],
            ],
            'tablaB' => [
                1 => [1 => [1 => 1, 2 => 3], 2 => [1 => 2, 2 => 3], 3 => [1 => 3, 2 => 4], 4 => [1 => 5, 2 => 5], 5 => [1 => 6, 2 => 6], 6 => [1 => 7, 2 => 7]],
                2 => [1 => [1 => 2, 2 => 3], 2 => [1 => 2, 2 => 3], 3 => [1 => 4, 2 => 5], 4 => [1 => 5, 2 => 5], 5 => [1 => 6, 2 => 7], 6 => [1 => 7, 2 => 7]],
                3 => [1 => [1 => 3, 2 => 3], 2 => [1 => 3, 2 => 4], 3 => [1 => 4, 2 => 5], 4 => [1 => 5, 2 => 6], 5 => [1 => 6, 2 => 7], 6 => [1 => 7, 2 => 7]],
                4 => [1 => [1 => 5, 2 => 5], 2 => [1 => 5, 2 => 6], 3 => [1 => 6, 2 => 7], 4 => [1 => 7, 2 => 7], 5 => [1 => 7, 2 => 7], 6 => [1 => 8, 2 => 8]],
                5 => [1 => [1 => 7, 2 => 7], 2 => [1 => 7, 2 => 7], 3 => [1 => 7, 2 => 8], 4 => [1 => 8, 2 => 8], 5 => [1 => 8, 2 => 8], 6 => [1 => 8, 2 => 8]],
                6 => [1 => [1 => 8, 2 => 8], 2 => [1 => 8, 2 => 8], 3 => [1 => 8, 2 => 8], 4 => [1 => 8, 2 => 9], 5 => [1 => 9, 2 => 9], 6 => [1 => 9, 2 => 9]],
            ],
            'tablaFinal' => [
                1 => [1 => 1, 2 => 2, 3 => 3, 4 => 3, 5 => 4, 6 => 5, 7 => 5],
                2 => [1 => 2, 2 => 2, 3 => 3, 4 => 4, 5 => 4, 6 => 5, 7 => 5],
                3 => [1 => 3, 2 => 3, 3 => 3, 4 => 4, 5 => 4, 6 => 5, 7 => 6],
                4 => [1 => 3, 2 => 3, 3 => 3, 4 => 4, 5 => 5, 6 => 6, 7 => 6],
                5 => [1 => 4, 2 => 4, 3 => 4, 4 => 5, 5 => 6, 6 => 7, 7 => 7],
                6 => [1 => 4, 2 => 4, 3 => 5, 4 => 6, 5 => 6, 6 => 7, 7 => 7],
                7 => [1 => 5, 2 => 5, 3 => 6, 4 => 6, 5 => 7, 6 => 7, 7 => 7],
                8 => [1 => 5, 2 => 5, 3 => 6, 4 => 7, 5 => 7, 6 => 7, 7 => 7],
            ],
        ];
    }

    private function textoBool($valor, $texto): string
    {
        return (int)($valor ?? 0) === 1 ? $texto : 'No aplica';
    }

    private function textoApoyo($valor): string
    {
        return (int)($valor ?? 0) === 1 ? 'Existe punto de apoyo (-1)' : 'Sin punto de apoyo';
    }

    private function textoBrazoBase($valor): string
    {
        return match ((int)$valor) {
            1 => 'Desde 20° de extensión a 20° de flexión',
            2 => 'Extensión >20° o flexión >20° y <45°',
            3 => 'Flexión >45° y 90°',
            4 => 'Flexión >90°',
            default => 'No definido',
        };
    }

    private function textoAntebrazoBase($valor): string
    {
        return match ((int)$valor) {
            1 => 'Flexión entre 60° y 100°',
            2 => 'Flexión <60° o >100°',
            default => 'No definido',
        };
    }

    private function textoMunecaBase($valor): string
    {
        return match ((int)$valor) {
            1 => 'Posición neutra',
            2 => 'Flexión o extensión >0° y <15°',
            3 => 'Flexión o extensión >15°',
            default => 'No definido',
        };
    }

    private function textoGiroMuneca($valor): string
    {
        return match ((int)$valor) {
            1 => 'Pronación o supinación media',
            2 => 'Pronación o supinación extrema',
            default => 'No definido',
        };
    }

    private function textoCuelloBase($valor): string
    {
        return match ((int)$valor) {
            1 => 'Flexión entre 0° y 10°',
            2 => 'Flexión >10° y ≤20°',
            3 => 'Flexión >20°',
            4 => 'Extensión en cualquier grado',
            default => 'No definido',
        };
    }

    private function textoTroncoBase($valor): string
    {
        return match ((int)$valor) {
            1 => 'De pie erguido sin flexión ni extensión, o sentado bien apoyado y con un ángulo tronco-piernas >90°',
            2 => 'Flexión entre >0° y 20°',
            3 => 'Flexión >20° y ≤60°',
            4 => 'Flexión >60°',
            default => 'No definido',
        };
    }

    private function textoPiernas($valor): string
    {
        return match ((int)$valor) {
            1 => 'Sentado con piernas y pies bien apoyados, o de pie con peso simétrico',
            2 => 'Los pies no están apoyados o el peso no está simétricamente distribuido',
            default => 'No definido',
        };
    }

    private function textoUsoMuscular($valor): string
    {
        return (int)$valor === 1 ? 'Postura estática o repetida frecuentemente (+1)' : 'Sin incremento por uso muscular';
    }

    private function textoCargaFuerza($valor): string
    {
        return match ((int)$valor) {
            0 => 'Menor a 2 kg, intermitente o sin carga adicional',
            1 => 'Entre 2 y 10 kg, intermitente',
            2 => 'Entre 2 y 10 kg estática o repetitiva / mayor a 10 kg intermitente',
            3 => 'Mayor a 10 kg estática o repetitiva / esfuerzos bruscos',
            default => 'No definido',
        };
    }

    public function excel($id, RulaReportService $reportService)
{
    $rula = $reportService->findOrFail((int) $id);
    $data = $reportService->build($rula);

    $empresa = $rula->evaluacion->empresa->nombre ?? 'empresa';
    $fecha = now()->format('Y-m-d');
    $codigo = 'ERG-' . now()->format('Ymd-His');

    $nombreArchivo = 'reporte_' .
        $this->limpiarNombreArchivo($empresa) .
        '_RULA_' .
        $codigo .
        '_' .
        $fecha .
        '.xlsx';

    return Excel::download(
        new RulaExport($data),
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