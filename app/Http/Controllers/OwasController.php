<?php

namespace App\Http\Controllers;

use App\Models\Evaluacion;
use App\Models\Metodo;
use App\Models\OwasDetalle;
use App\Models\OwasEvaluacion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Exports\OwasExport;
use App\Services\Reportes\OwasReportService;
use Maatwebsite\Excel\Facades\Excel;

class OwasController extends Controller
{
    public function index()
    {
        $owas = OwasEvaluacion::with([
            'evaluacion.empresa',
            'evaluacion.sucursal',
            'evaluacion.puesto',
            'evaluacion.trabajador',
            'evaluacion.usuario'
        ])->latest()->paginate(10);

        return view('owas.index', compact('owas'));
    }

    public function create(Request $request, $evaluacion)
{
    $evaluacionModel = Evaluacion::with([
        'empresa',
        'sucursal',
        'puesto',
        'trabajador',
        'metodo',
        'usuario',
    ])->findOrFail($evaluacion);

    $datosBase = [
        'empresa_id' => $evaluacionModel->empresa_id,
        'sucursal_id' => $evaluacionModel->sucursal_id,
        'puesto_id' => $evaluacionModel->puesto_id,
        'trabajador_id' => $evaluacionModel->trabajador_id,
        'fecha_evaluacion' => $evaluacionModel->fecha_evaluacion,
        'area_evaluada' => $evaluacionModel->area_evaluada,
        'actividad_general' => $evaluacionModel->actividad,
        'observaciones' => $evaluacionModel->observaciones,
    ];

    return view('owas.create', [
        'datosBase' => $datosBase,
        'evaluacion' => $evaluacionModel,
    ]);
}

public function store(Request $request, $evaluacion)
{
    $request->validate([
        'empresa_id' => 'required|exists:empresas,id',
        'sucursal_id' => 'required|exists:sucursales,id',
        'puesto_id' => 'required|exists:puestos,id',
        'trabajador_id' => 'required|exists:trabajadores,id',
        'fecha_evaluacion' => 'required|date',

        'area_evaluada' => 'nullable|string|max:255',
        'actividad_general' => 'nullable|string|max:255',
        'observaciones' => 'nullable|string',

        'posturas' => 'required|array|min:1',
        'posturas.*.espalda' => 'required|integer|min:1|max:4',
        'posturas.*.brazos' => 'required|integer|min:1|max:3',
        'posturas.*.piernas' => 'required|integer|min:1|max:7',
        'posturas.*.carga' => 'required|integer|min:1|max:3',
        'posturas.*.frecuencia' => 'required|integer|min:1',
    ]);

    DB::beginTransaction();

    try {
        $metodo = Metodo::whereRaw('LOWER(nombre) = ?', ['owas'])->first();

        if (!$metodo) {
            return back()->withInput()->with('error', 'No existe el método OWAS en la tabla metodos.');
        }

        $resultado = $this->calcularOwasDesdeFormulario($request->posturas);

        $evaluacionModel = Evaluacion::findOrFail($evaluacion);

        $evaluacionModel->update([
            'empresa_id' => $request->empresa_id,
            'sucursal_id' => $request->sucursal_id,
            'puesto_id' => $request->puesto_id,
            'trabajador_id' => $request->trabajador_id,
            'metodo_id' => $metodo->id,
            'user_id' => Auth::id(),
            'fecha_evaluacion' => $request->fecha_evaluacion,
            'area_evaluada' => $request->area_evaluada,
            'actividad' => $request->actividad_general,
            'observaciones' => $request->observaciones,
            'resultado_final' => $resultado['categoria_final'],
            'nivel_riesgo' => $resultado['nivel_riesgo'],
            'recomendaciones' => $resultado['accion_requerida'],
        ]);

       $owas = OwasEvaluacion::create([
    'evaluacion_id' => $evaluacionModel->id,
    'espalda' => $resultado['postura_critica']['espalda'],
    'brazos' => $resultado['postura_critica']['brazos'],
    'piernas' => $resultado['postura_critica']['piernas'],
    'carga' => $resultado['postura_critica']['carga'],
    'codigo_postura' => $resultado['postura_critica']['codigo_postura'],

    // Aquí debe ir número: 1, 2, 3 o 4
    'categoria_riesgo' => $resultado['postura_critica']['categoria'],

    'accion_correctiva' => $resultado['postura_critica']['accion'],
]);

        foreach ($resultado['posturas'] as $index => $postura) {
            $seccion = 'POSTURA_' . ($index + 1);

            OwasDetalle::create([
                'owas_evaluacion_id' => $owas->id,
                'seccion' => $seccion,
                'concepto' => 'espalda',
                'valor' => $this->textoEspalda($postura['espalda']),
                'puntaje' => $postura['espalda'],
            ]);

            OwasDetalle::create([
                'owas_evaluacion_id' => $owas->id,
                'seccion' => $seccion,
                'concepto' => 'brazos',
                'valor' => $this->textoBrazos($postura['brazos']),
                'puntaje' => $postura['brazos'],
            ]);

            OwasDetalle::create([
                'owas_evaluacion_id' => $owas->id,
                'seccion' => $seccion,
                'concepto' => 'piernas',
                'valor' => $this->textoPiernas($postura['piernas']),
                'puntaje' => $postura['piernas'],
            ]);

            OwasDetalle::create([
                'owas_evaluacion_id' => $owas->id,
                'seccion' => $seccion,
                'concepto' => 'carga',
                'valor' => $this->textoCarga($postura['carga']),
                'puntaje' => $postura['carga'],
            ]);

            OwasDetalle::create([
                'owas_evaluacion_id' => $owas->id,
                'seccion' => $seccion,
                'concepto' => 'frecuencia',
                'valor' => 'Frecuencia observada',
                'puntaje' => $postura['frecuencia'],
            ]);

            OwasDetalle::create([
                'owas_evaluacion_id' => $owas->id,
                'seccion' => $seccion,
                'concepto' => 'porcentaje',
                'valor' => 'Porcentaje de aparición',
                'puntaje' => $postura['porcentaje'],
            ]);

            OwasDetalle::create([
                'owas_evaluacion_id' => $owas->id,
                'seccion' => $seccion,
                'concepto' => 'codigo_postura',
                'valor' => $postura['codigo_postura'],
                'puntaje' => (int) $postura['codigo_postura'],
            ]);

            OwasDetalle::create([
                'owas_evaluacion_id' => $owas->id,
                'seccion' => $seccion,
                'concepto' => 'categoria_riesgo',
                'valor' => $postura['nivel'],
                'puntaje' => $postura['categoria'],
            ]);

            OwasDetalle::create([
                'owas_evaluacion_id' => $owas->id,
                'seccion' => $seccion,
                'concepto' => 'accion_correctiva',
                'valor' => $postura['accion'],
                'puntaje' => 0,
            ]);
        }

        foreach ($resultado['analisis_partes'] as $parte => $filas) {
            $seccion = 'PARTE_' . strtoupper($parte);

            foreach ($filas as $fila) {
                OwasDetalle::create([
                    'owas_evaluacion_id' => $owas->id,
                    'seccion' => $seccion,
                    'concepto' => $fila['concepto'],
                    'valor' => $fila['valor'],
                    'puntaje' => $fila['puntaje'],
                ]);
            }
        }

        OwasDetalle::create([
            'owas_evaluacion_id' => $owas->id,
            'seccion' => 'GENERAL',
            'concepto' => 'actividad_general',
            'valor' => $request->actividad_general ?? 'No especificada',
            'puntaje' => 0,
        ]);

        OwasDetalle::create([
            'owas_evaluacion_id' => $owas->id,
            'seccion' => 'GENERAL',
            'concepto' => 'area_evaluada',
            'valor' => $request->area_evaluada ?? 'No especificada',
            'puntaje' => 0,
        ]);

        OwasDetalle::create([
            'owas_evaluacion_id' => $owas->id,
            'seccion' => 'RESULTADO',
            'concepto' => 'categoria_final',
            'valor' => $resultado['nivel_riesgo'],
            'puntaje' => $resultado['categoria_final'],
        ]);

        DB::commit();

        return redirect()->route('owas.show', $owas->id)
            ->with('success', 'Evaluación OWAS guardada correctamente.');
    } catch (\Throwable $e) {
        DB::rollBack();
        return back()->withInput()->with('error', 'Error al guardar: ' . $e->getMessage());
    }
}
    public function show($id)
    {
        $owas = OwasEvaluacion::with([
            'evaluacion.empresa',
            'evaluacion.sucursal',
            'evaluacion.puesto',
            'evaluacion.trabajador',
            'evaluacion.usuario',
            'detalles'
        ])->findOrFail($id);

        return view('owas.show', compact('owas'));
    }

    public function pdf($id)
{
    $owas = OwasEvaluacion::with([
        'evaluacion.empresa',
        'evaluacion.sucursal',
        'evaluacion.puesto',
        'evaluacion.trabajador',
        'evaluacion.usuario',
        'detalles'
    ])->findOrFail($id);

    $pdf = Pdf::loadView('owas.pdf', compact('owas'))
        ->setPaper('a4', 'portrait');

    $empresa = $owas->evaluacion->empresa->nombre ?? 'empresa';
    $fecha = now()->format('Y-m-d');
    $codigo = 'ERG-' . now()->format('Ymd-His');

    $nombreArchivo = 'reporte_' .
        $this->limpiarNombreArchivo($empresa) .
        '_OWAS_' .
        $codigo .
        '_' .
        $fecha .
        '.pdf';

    return $pdf->download($nombreArchivo);
}

    private function calcularOwasDesdeFormulario(array $posturas): array
    {
        $posturasResultado = [];
        $totalFrecuencia = 0;

        foreach ($posturas as $p) {
            $totalFrecuencia += (int) ($p['frecuencia'] ?? 0);
        }

        $categoriaFinal = 1;
        $posturaCritica = null;

        $frecuenciaEspalda = [1 => 0, 2 => 0, 3 => 0, 4 => 0];
        $frecuenciaBrazos = [1 => 0, 2 => 0, 3 => 0];
        $frecuenciaPiernas = [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0, 6 => 0, 7 => 0];

        foreach ($posturas as $p) {
            $espalda = (int) $p['espalda'];
            $brazos = (int) $p['brazos'];
            $piernas = (int) $p['piernas'];
            $carga = (int) $p['carga'];
            $frecuencia = (int) $p['frecuencia'];

            $codigoPostura = "{$espalda}{$brazos}{$piernas}{$carga}";
            $categoria = $this->getOwasMatrices()[$codigoPostura] ?? 1;
            $clasificacion = $this->clasificarCategoria($categoria);

            $porcentaje = $totalFrecuencia > 0
                ? round(($frecuencia / $totalFrecuencia) * 100, 2)
                : 0;

            $fila = [
                'espalda' => $espalda,
                'brazos' => $brazos,
                'piernas' => $piernas,
                'carga' => $carga,
                'frecuencia' => $frecuencia,
                'porcentaje' => $porcentaje,
                'codigo_postura' => $codigoPostura,
                'categoria' => $categoria,
                'nivel' => $clasificacion['nivel'],
                'accion' => $clasificacion['accion'],
            ];

            $posturasResultado[] = $fila;

            $frecuenciaEspalda[$espalda] += $frecuencia;
            $frecuenciaBrazos[$brazos] += $frecuencia;
            $frecuenciaPiernas[$piernas] += $frecuencia;

            if ($categoria > $categoriaFinal) {
                $categoriaFinal = $categoria;
            }

            if (
                $posturaCritica === null ||
                $categoria > $posturaCritica['categoria'] ||
                ($categoria === $posturaCritica['categoria'] && $frecuencia > $posturaCritica['frecuencia'])
            ) {
                $posturaCritica = $fila;
            }
        }

        $clasificacionFinal = $this->clasificarCategoria($categoriaFinal);

        $analisisPartes = [
            'espalda' => $this->armarAnalisisParte('espalda', $frecuenciaEspalda, $totalFrecuencia),
            'brazos' => $this->armarAnalisisParte('brazos', $frecuenciaBrazos, $totalFrecuencia),
            'piernas' => $this->armarAnalisisParte('piernas', $frecuenciaPiernas, $totalFrecuencia),
        ];

        return [
            'posturas' => $posturasResultado,
            'postura_critica' => $posturaCritica,
            'categoria_final' => $categoriaFinal,
            'nivel_riesgo' => $clasificacionFinal['nivel'],
            'accion_requerida' => $clasificacionFinal['accion'],
            'analisis_partes' => $analisisPartes,
        ];
    }

    private function armarAnalisisParte(string $parte, array $frecuencias, int $totalFrecuencia): array
    {
        $resultado = [];

        foreach ($frecuencias as $codigo => $frecuencia) {
            $porcentaje = $totalFrecuencia > 0
                ? round(($frecuencia / $totalFrecuencia) * 100, 2)
                : 0;

            $categoria = $this->categoriaPartePorFrecuencia($parte, (int)$codigo, $porcentaje);
            $clasificacion = $this->clasificarCategoria($categoria);

            $resultado[] = [
                'concepto' => 'codigo_' . $codigo . '_descripcion',
                'valor' => $this->textoParte($parte, (int)$codigo),
                'puntaje' => $codigo,
            ];

            $resultado[] = [
                'concepto' => 'codigo_' . $codigo . '_frecuencia',
                'valor' => 'Frecuencia',
                'puntaje' => $frecuencia,
            ];

            $resultado[] = [
                'concepto' => 'codigo_' . $codigo . '_porcentaje',
                'valor' => 'Porcentaje',
                'puntaje' => $porcentaje,
            ];

            $resultado[] = [
                'concepto' => 'codigo_' . $codigo . '_categoria',
                'valor' => $clasificacion['nivel'],
                'puntaje' => $categoria,
            ];
        }

        return $resultado;
    }

    private function categoriaPartePorFrecuencia(string $parte, int $codigo, float $porcentaje): int
    {
        $tabla = $this->getTablaFrecuenciaPartes();

        $fila = $tabla[$parte][$codigo] ?? [1,1,1,1,1,1,1,1,1,1];

        if ($porcentaje <= 10) return $fila[0];
        if ($porcentaje <= 20) return $fila[1];
        if ($porcentaje <= 30) return $fila[2];
        if ($porcentaje <= 40) return $fila[3];
        if ($porcentaje <= 50) return $fila[4];
        if ($porcentaje <= 60) return $fila[5];
        if ($porcentaje <= 70) return $fila[6];
        if ($porcentaje <= 80) return $fila[7];
        if ($porcentaje <= 90) return $fila[8];
        return $fila[9];
    }

    private function clasificarCategoria(int $categoria): array
    {
        return match ($categoria) {
            1 => [
                'nivel' => 'Bajo',
                'accion' => 'No requiere acción.'
            ],
            2 => [
                'nivel' => 'Medio',
                'accion' => 'Se requieren acciones correctivas en un futuro cercano.'
            ],
            3 => [
                'nivel' => 'Alto',
                'accion' => 'Se requieren acciones correctivas lo antes posible.'
            ],
            4 => [
                'nivel' => 'Muy alto',
                'accion' => 'Se requiere tomar acciones correctivas inmediatamente.'
            ],
            default => [
                'nivel' => 'No definido',
                'accion' => 'Sin acción definida.'
            ],
        };
    }

   private function getOwasMatrices(): array
{
    $filas = [
        '11' => [1,1,1,1,1,1,1,1,1,2,2,2,2,2,2,1,1,1,1,1,1],
        '12' => [1,1,1,1,1,1,1,1,1,2,2,2,2,2,2,1,1,1,1,1,1],
        '13' => [1,1,1,1,1,1,1,1,1,2,2,3,2,2,3,1,1,1,1,1,2],

        '21' => [2,2,3,2,2,3,2,2,3,3,3,3,3,3,3,2,2,2,2,3,3],
        '22' => [2,2,3,2,2,3,2,3,3,3,4,4,3,4,4,3,3,4,2,3,4],
        '23' => [3,3,4,2,2,3,3,3,3,3,4,4,4,4,4,4,4,4,2,3,4],

        '31' => [1,1,1,1,1,1,1,1,2,3,3,3,4,4,4,1,1,1,1,1,1],
        '32' => [2,2,3,1,1,1,1,1,2,4,4,4,4,4,4,3,3,3,1,1,1],
        '33' => [2,2,3,1,1,1,2,3,3,4,4,4,4,4,4,4,4,4,1,1,1],

        '41' => [2,3,3,2,2,3,2,2,3,4,4,4,4,4,4,4,4,4,2,3,4],
        '42' => [3,3,4,2,3,4,3,3,4,4,4,4,4,4,4,4,4,4,2,3,4],
        '43' => [4,4,4,2,3,4,3,3,4,4,4,4,4,4,4,4,4,4,2,3,4],
    ];

    $matriz = [];

    foreach ($filas as $clave => $valores) {
        $clave = (string) $clave;

        $espalda = (int) substr($clave, 0, 1);
        $brazos = (int) substr($clave, 1, 1);

        $i = 0;

        for ($piernas = 1; $piernas <= 7; $piernas++) {
            for ($carga = 1; $carga <= 3; $carga++) {
                $codigo = "{$espalda}{$brazos}{$piernas}{$carga}";
                $matriz[$codigo] = $valores[$i] ?? 1;
                $i++;
            }
        }
    }

    return $matriz;
}

    private function getTablaFrecuenciaPartes(): array
    {
        return [
            'espalda' => [
                1 => [1,1,1,1,1,1,1,1,1,1],
                2 => [1,1,1,2,2,2,2,2,3,3],
                3 => [1,1,2,2,2,3,3,3,3,3],
                4 => [1,2,2,3,3,3,3,4,4,4],
            ],
            'brazos' => [
                1 => [1,1,1,1,1,1,1,1,1,1],
                2 => [1,1,1,2,2,2,2,2,3,3],
                3 => [1,1,2,2,2,2,2,3,3,3],
            ],
            'piernas' => [
                1 => [1,1,1,1,1,1,1,1,1,2],
                2 => [1,1,1,1,1,1,1,1,2,2],
                3 => [1,1,1,2,2,2,2,2,3,3],
                4 => [1,2,2,3,3,3,3,4,4,4],
                5 => [1,2,2,3,3,3,3,4,4,4],
                6 => [1,1,2,2,2,3,3,3,3,3],
                7 => [1,1,1,1,1,1,1,1,2,2],
            ],
        ];
    }

    private function textoParte(string $parte, int $codigo): string
    {
        return match ($parte) {
            'espalda' => $this->textoEspalda($codigo),
            'brazos' => $this->textoBrazos($codigo),
            'piernas' => $this->textoPiernas($codigo),
            default => 'No definido',
        };
    }

    private function textoEspalda($valor): string
    {
        return match ((int)$valor) {
            1 => 'Espalda derecha',
            2 => 'Espalda doblada',
            3 => 'Espalda con giro',
            4 => 'Espalda doblada con giro',
            default => 'No definido',
        };
    }

    private function textoBrazos($valor): string
    {
        return match ((int)$valor) {
            1 => 'Los dos brazos bajos',
            2 => 'Un brazo bajo y el otro elevado',
            3 => 'Los dos brazos elevados',
            default => 'No definido',
        };
    }

    private function textoPiernas($valor): string
    {
        return match ((int)$valor) {
            1 => 'Sentado',
            2 => 'De pie con las dos piernas rectas',
            3 => 'De pie con una pierna recta y la otra flexionada',
            4 => 'De pie o en cuclillas con las dos piernas flexionadas y el peso equilibrado',
            5 => 'De pie o en cuclillas con las dos piernas flexionadas y el peso desequilibrado',
            6 => 'Arrodillado',
            7 => 'Andando',
            default => 'No definido',
        };
    }


    private function textoCarga($valor): string
    {
        return match ((int)$valor) {
            1 => 'Menos de 10 kg',
            2 => 'Entre 10 y 20 kg',
            3 => 'Más de 20 kg',
            default => 'No definido',
        };
    }

    public function excel($id, OwasReportService $reportService)
{
    $owas = $reportService->findOrFail((int) $id);
    $data = $reportService->build($owas);

    $empresa = $owas->evaluacion->empresa->nombre ?? 'empresa';
    $fecha = now()->format('Y-m-d');
    $codigo = 'ERG-' . now()->format('Ymd-His');

    $nombreArchivo = 'reporte_' .
        $this->limpiarNombreArchivo($empresa) .
        '_OWAS_' .
        $codigo .
        '_' .
        $fecha .
        '.xlsx';

    return Excel::download(
        new OwasExport($data),
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