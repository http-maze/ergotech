<?php

namespace App\Http\Controllers;

use App\Models\Evaluacion;
use App\Models\RebaDetalle;
use App\Models\RebaEvaluacion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Exports\RebaExport;
use App\Services\Reportes\RebaReportService;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory as WordIOFactory;

class RebaController extends Controller
{
    public function index()
    {
        $rebas = RebaEvaluacion::with([
            'evaluacion.empresa',
            'evaluacion.sucursal',
            'evaluacion.puesto',
            'evaluacion.trabajador',
            'evaluacion.usuario',
        ])->latest()->paginate(10);

        return view('reba.index', compact('rebas'));
    }

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

        $matrices = $this->getRebaMatrices();

        return view('reba.create', compact('evaluacion', 'matrices'));
    }

    public function store(Request $request, $evaluacionId)
    {
        $evaluacion = Evaluacion::findOrFail($evaluacionId);

        $request->validate([
            'cuello' => 'required|integer|min:1|max:3',
            'tronco' => 'required|integer|min:1|max:5',
            'piernas' => 'required|integer|min:1|max:4',
            'carga' => 'required|integer|min:0|max:2',
            'brazo' => 'required|integer|min:1|max:6',
            'antebrazo' => 'required|integer|min:1|max:2',
            'muneca' => 'required|integer|min:1|max:3',
            'tipo_agarre' => 'required|integer|min:0|max:3',
            'actividad_reba' => 'required|integer|min:0|max:3',
            'ajuste_cuello' => 'nullable|integer|min:0|max:1',
            'ajuste_tronco' => 'nullable|integer|min:0|max:1',
            'ajuste_muneca' => 'nullable|integer|min:0|max:1',
            'lado_evaluado' => 'nullable|string|max:100',
            'tarea' => 'nullable|string|max:255',
        ]);

        DB::beginTransaction();

        try {
            $resultado = $this->calcularRebaOficial(
                (int) $request->cuello,
                (int) ($request->ajuste_cuello ?? 0),
                (int) $request->tronco,
                (int) ($request->ajuste_tronco ?? 0),
                (int) $request->piernas,
                (int) $request->carga,
                (int) $request->brazo,
                (int) $request->antebrazo,
                (int) $request->muneca,
                (int) ($request->ajuste_muneca ?? 0),
                (int) $request->tipo_agarre,
                (int) $request->actividad_reba
            );

            $reba = RebaEvaluacion::create([
                'evaluacion_id' => $evaluacion->id,
                'cuello' => $request->cuello,
                'tronco' => $request->tronco,
                'piernas' => $request->piernas,
                'brazo' => $request->brazo,
                'antebrazo' => $request->antebrazo,
                'muneca' => $request->muneca,
                'carga' => $request->carga,
                'tipo_agarre' => $request->tipo_agarre,
                'actividad' => $request->actividad_reba,
                'puntuacion_a' => $resultado['puntuacion_a'],
                'puntuacion_b' => $resultado['puntuacion_b'],
                'puntuacion_c' => $resultado['puntuacion_c'],
                'puntuacion_final' => $resultado['puntuacion_final'],
                'nivel_riesgo' => $resultado['nivel_riesgo'],
                'accion_requerida' => $resultado['accion_requerida'],
            ]);

            $evaluacion->update([
                'resultado_final' => $resultado['puntuacion_final'],
                'nivel_riesgo' => $resultado['nivel_riesgo'],
                'recomendaciones' => $resultado['accion_requerida'],
            ]);

            $detalles = [
                ['seccion' => 'A', 'concepto' => 'cuello', 'valor' => $this->textoCuello($request->cuello), 'puntaje' => $request->cuello],
                ['seccion' => 'A', 'concepto' => 'ajuste_cuello', 'valor' => $this->textoAjusteCuello($request->ajuste_cuello), 'puntaje' => $request->ajuste_cuello ?? 0],
                ['seccion' => 'A', 'concepto' => 'tronco', 'valor' => $this->textoTronco($request->tronco), 'puntaje' => $request->tronco],
                ['seccion' => 'A', 'concepto' => 'ajuste_tronco', 'valor' => $this->textoAjusteTronco($request->ajuste_tronco), 'puntaje' => $request->ajuste_tronco ?? 0],
                ['seccion' => 'A', 'concepto' => 'piernas', 'valor' => $this->textoPiernas($request->piernas), 'puntaje' => $request->piernas],
                ['seccion' => 'A', 'concepto' => 'carga', 'valor' => $this->textoCarga($request->carga), 'puntaje' => $request->carga],

                ['seccion' => 'B', 'concepto' => 'brazo', 'valor' => $this->textoBrazo($request->brazo), 'puntaje' => $request->brazo],
                ['seccion' => 'B', 'concepto' => 'antebrazo', 'valor' => $this->textoAntebrazo($request->antebrazo), 'puntaje' => $request->antebrazo],
                ['seccion' => 'B', 'concepto' => 'muneca', 'valor' => $this->textoMuneca($request->muneca), 'puntaje' => $request->muneca],
                ['seccion' => 'B', 'concepto' => 'ajuste_muneca', 'valor' => $this->textoAjusteMuneca($request->ajuste_muneca), 'puntaje' => $request->ajuste_muneca ?? 0],
                ['seccion' => 'B', 'concepto' => 'tipo_agarre', 'valor' => $this->textoAgarre($request->tipo_agarre), 'puntaje' => $request->tipo_agarre],

                ['seccion' => 'C', 'concepto' => 'actividad_reba', 'valor' => $this->textoActividad($request->actividad_reba), 'puntaje' => $request->actividad_reba],
                ['seccion' => 'GENERAL', 'concepto' => 'lado_evaluado', 'valor' => $request->lado_evaluado ?? 'No especificado', 'puntaje' => 0],
                ['seccion' => 'GENERAL', 'concepto' => 'tarea', 'valor' => $request->tarea ?? 'No especificada', 'puntaje' => 0],
                ['seccion' => 'GENERAL', 'concepto' => 'area_evaluada', 'valor' => $evaluacion->area_evaluada ?? 'No especificada', 'puntaje' => 0],
                ['seccion' => 'GENERAL', 'concepto' => 'actividad_general', 'valor' => $evaluacion->actividad ?? 'No especificada', 'puntaje' => 0],

                ['seccion' => 'RESULTADO', 'concepto' => 'puntuacion_a', 'valor' => 'Resultado Grupo A', 'puntaje' => $resultado['puntuacion_a']],
                ['seccion' => 'RESULTADO', 'concepto' => 'puntuacion_b', 'valor' => 'Resultado Grupo B', 'puntaje' => $resultado['puntuacion_b']],
                ['seccion' => 'RESULTADO', 'concepto' => 'puntuacion_c', 'valor' => 'Resultado Tabla C', 'puntaje' => $resultado['puntuacion_c']],
                ['seccion' => 'RESULTADO', 'concepto' => 'puntuacion_final', 'valor' => $resultado['nivel_riesgo'], 'puntaje' => $resultado['puntuacion_final']],
            ];

            foreach ($detalles as $detalle) {
                RebaDetalle::create([
                    'reba_evaluacion_id' => $reba->id,
                    'seccion' => $detalle['seccion'],
                    'concepto' => $detalle['concepto'],
                    'valor' => $detalle['valor'],
                    'puntaje' => $detalle['puntaje'],
                ]);
            }

            DB::commit();

            return redirect()->route('reba.show', $reba->id)
                ->with('success', 'Evaluación REBA guardada correctamente.');
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Error al guardar: ' . $e->getMessage());
        }
    }

    public function show($id)
    {
        $reba = RebaEvaluacion::with([
            'evaluacion.empresa',
            'evaluacion.sucursal',
            'evaluacion.puesto',
            'evaluacion.trabajador',
            'evaluacion.usuario',
            'detalles'
        ])->findOrFail($id);

        return view('reba.show', compact('reba'));
    }

    public function pdf($id)
{
    $reba = RebaEvaluacion::with([
        'evaluacion.empresa',
        'evaluacion.sucursal',
        'evaluacion.puesto',
        'evaluacion.trabajador',
        'evaluacion.usuario',
        'detalles'
    ])->findOrFail($id);

    $pdf = Pdf::loadView('reba.pdf', compact('reba'))
        ->setPaper('a4', 'portrait');

    $empresa = $reba->evaluacion->empresa->nombre ?? 'empresa';
    $fecha = now()->format('Y-m-d');
    $codigo = 'ERG-' . now()->format('Ymd-His');

    $nombreArchivo = 'reporte_' .
        $this->limpiarNombreArchivo($empresa) .
        '_REBA_' .
        $codigo .
        '_' .
        $fecha .
        '.pdf';

    return $pdf->download($nombreArchivo);
}

    private function calcularRebaOficial(
        int $cuello,
        int $ajusteCuello,
        int $tronco,
        int $ajusteTronco,
        int $piernas,
        int $carga,
        int $brazo,
        int $antebrazo,
        int $muneca,
        int $ajusteMuneca,
        int $tipoAgarre,
        int $actividad
    ): array {
        $matrices = $this->getRebaMatrices();

        $cuelloTabla = min(3, $cuello + $ajusteCuello);
        $troncoTabla = min(5, $tronco + $ajusteTronco);
        $munecaTabla = min(3, $muneca + $ajusteMuneca);

        $aBase = $matrices['tablaA'][$troncoTabla][$cuelloTabla][$piernas];
        $puntuacionA = $aBase + $carga;
        $puntuacionA = max(1, min(12, $puntuacionA));

        $bBase = $matrices['tablaB'][$brazo][$antebrazo][$munecaTabla];
        $puntuacionB = $bBase + $tipoAgarre;
        $puntuacionB = max(1, min(12, $puntuacionB));

        $puntuacionC = $matrices['tablaC'][$puntuacionA][$puntuacionB];
        $puntuacionFinal = $puntuacionC + $actividad;

        $clasificacion = $this->clasificarRiesgo($puntuacionFinal);

        return [
            'puntuacion_a' => $puntuacionA,
            'puntuacion_b' => $puntuacionB,
            'puntuacion_c' => $puntuacionC,
            'puntuacion_final' => $puntuacionFinal,
            'nivel_riesgo' => $clasificacion['nivel'],
            'accion_requerida' => $clasificacion['accion'],
        ];
    }

    private function clasificarRiesgo(int $puntaje): array
    {
        if ($puntaje <= 1) return ['nivel' => 'Inapreciable', 'accion' => 'No requiere acción.'];
        if ($puntaje <= 3) return ['nivel' => 'Bajo', 'accion' => 'Puede ser necesaria alguna acción.'];
        if ($puntaje <= 7) return ['nivel' => 'Medio', 'accion' => 'Se requiere acción.'];
        if ($puntaje <= 10) return ['nivel' => 'Alto', 'accion' => 'Se requiere acción pronto.'];
        return ['nivel' => 'Muy alto', 'accion' => 'Se requiere actuación inmediata.'];
    }

    private function getRebaMatrices(): array
    {
        return [
            'tablaA' => [
                1 => [1 => [1 => 1, 2 => 2, 3 => 3, 4 => 4], 2 => [1 => 1, 2 => 2, 3 => 3, 4 => 4], 3 => [1 => 3, 2 => 3, 3 => 5, 4 => 6]],
                2 => [1 => [1 => 2, 2 => 3, 3 => 4, 4 => 5], 2 => [1 => 3, 2 => 4, 3 => 5, 4 => 6], 3 => [1 => 4, 2 => 5, 3 => 6, 4 => 7]],
                3 => [1 => [1 => 2, 2 => 4, 3 => 5, 4 => 6], 2 => [1 => 4, 2 => 5, 3 => 6, 4 => 7], 3 => [1 => 5, 2 => 6, 3 => 7, 4 => 8]],
                4 => [1 => [1 => 3, 2 => 5, 3 => 6, 4 => 7], 2 => [1 => 5, 2 => 6, 3 => 7, 4 => 8], 3 => [1 => 6, 2 => 7, 3 => 8, 4 => 9]],
                5 => [1 => [1 => 4, 2 => 6, 3 => 7, 4 => 8], 2 => [1 => 6, 2 => 7, 3 => 8, 4 => 9], 3 => [1 => 7, 2 => 8, 3 => 9, 4 => 9]],
            ],
            'tablaB' => [
                1 => [1 => [1 => 1, 2 => 2, 3 => 2], 2 => [1 => 1, 2 => 2, 3 => 3]],
                2 => [1 => [1 => 1, 2 => 2, 3 => 3], 2 => [1 => 2, 2 => 3, 3 => 4]],
                3 => [1 => [1 => 3, 2 => 4, 3 => 5], 2 => [1 => 4, 2 => 5, 3 => 5]],
                4 => [1 => [1 => 4, 2 => 5, 3 => 5], 2 => [1 => 5, 2 => 6, 3 => 7]],
                5 => [1 => [1 => 6, 2 => 7, 3 => 8], 2 => [1 => 7, 2 => 8, 3 => 8]],
                6 => [1 => [1 => 7, 2 => 8, 3 => 8], 2 => [1 => 8, 2 => 9, 3 => 9]],
            ],
            'tablaC' => [
                1  => [1 => 1, 2 => 1, 3 => 1, 4 => 2, 5 => 3, 6 => 3, 7 => 4, 8 => 5, 9 => 6, 10 => 7, 11 => 7, 12 => 7],
                2  => [1 => 1, 2 => 2, 3 => 2, 4 => 3, 5 => 4, 6 => 4, 7 => 5, 8 => 6, 9 => 6, 10 => 7, 11 => 7, 12 => 8],
                3  => [1 => 2, 2 => 3, 3 => 3, 4 => 3, 5 => 4, 6 => 5, 7 => 6, 8 => 7, 9 => 7, 10 => 8, 11 => 8, 12 => 8],
                4  => [1 => 3, 2 => 4, 3 => 4, 4 => 4, 5 => 5, 6 => 6, 7 => 7, 8 => 8, 9 => 8, 10 => 9, 11 => 9, 12 => 9],
                5  => [1 => 4, 2 => 4, 3 => 4, 4 => 5, 5 => 6, 6 => 7, 7 => 8, 8 => 8, 9 => 9, 10 => 9, 11 => 9, 12 => 9],
                6  => [1 => 6, 2 => 6, 3 => 6, 4 => 7, 5 => 8, 6 => 8, 7 => 9, 8 => 9, 9 => 10, 10 => 10, 11 => 10, 12 => 10],
                7  => [1 => 7, 2 => 7, 3 => 7, 4 => 8, 5 => 9, 6 => 9, 7 => 9, 8 => 10, 9 => 10, 10 => 11, 11 => 11, 12 => 11],
                8  => [1 => 8, 2 => 8, 3 => 8, 4 => 9, 5 => 10, 6 => 10, 7 => 10, 8 => 10, 9 => 10, 10 => 11, 11 => 11, 12 => 11],
                9  => [1 => 9, 2 => 9, 3 => 9, 4 => 10, 5 => 10, 6 => 10, 7 => 11, 8 => 11, 9 => 11, 10 => 12, 11 => 12, 12 => 12],
                10 => [1 => 10, 2 => 10, 3 => 10, 4 => 11, 5 => 11, 6 => 11, 7 => 11, 8 => 12, 9 => 12, 10 => 12, 11 => 12, 12 => 12],
                11 => [1 => 11, 2 => 11, 3 => 11, 4 => 11, 5 => 12, 6 => 12, 7 => 12, 8 => 12, 9 => 12, 10 => 12, 11 => 12, 12 => 12],
                12 => [1 => 12, 2 => 12, 3 => 12, 4 => 12, 5 => 12, 6 => 12, 7 => 12, 8 => 12, 9 => 12, 10 => 12, 11 => 12, 12 => 12],
            ],
        ];
    }

    private function textoCuello($valor): string
    {
        return match ((int) $valor) {
            1 => 'Neutro',
            2 => 'Flexión/Extensión >20°',
            3 => 'Con torsión/inclinación',
            default => 'No definido',
        };
    }

    private function textoAjusteCuello($valor): string
    {
        return (int) $valor === 1 ? 'El cuello está girado o flexionado lateralmente' : 'Sin ajuste adicional';
    }

    private function textoTronco($valor): string
    {
        return match ((int) $valor) {
            1 => 'Recto',
            2 => 'Flexión 0–20°',
            3 => 'Flexión 20–60°',
            4 => 'Flexión >60°',
            5 => 'Postura severamente comprometida',
            default => 'No definido',
        };
    }

    private function textoAjusteTronco($valor): string
    {
        return (int) $valor === 1 ? 'El tronco está girado o inclinado lateralmente' : 'Sin ajuste adicional';
    }

    private function textoPiernas($valor): string
    {
        return match ((int) $valor) {
            1 => 'Soporte bilateral',
            2 => 'Peso desigual',
            3 => 'En cuclillas',
            4 => 'Apoyo inestable',
            default => 'No definido',
        };
    }

    private function textoCarga($valor): string
    {
        return match ((int) $valor) {
            0 => '< 5 kg',
            1 => '5–10 kg',
            2 => '> 10 kg',
            default => 'No definido',
        };
    }

    private function textoBrazo($valor): string
    {
        return match ((int) $valor) {
            1 => '20° ext a 20° flex',
            2 => '20°–45°',
            3 => '45°–90°',
            4 => '>90°',
            5 => 'Elevado con ajuste adicional',
            6 => 'Muy elevado con ajuste adicional',
            default => 'No definido',
        };
    }

    private function textoAntebrazo($valor): string
    {
        return match ((int) $valor) {
            1 => '60°–100°',
            2 => 'Fuera de rango',
            default => 'No definido',
        };
    }

    private function textoMuneca($valor): string
    {
        return match ((int) $valor) {
            1 => 'Neutra',
            2 => 'Flexión/extensión >15°',
            3 => 'Con desviación',
            default => 'No definido',
        };
    }

    private function textoAjusteMuneca($valor): string
    {
        return (int) $valor === 1 ? 'La muñeca está girada o desviada' : 'Sin ajuste adicional';
    }

    private function textoAgarre($valor): string
    {
        return match ((int) $valor) {
            0 => 'Bueno',
            1 => 'Regular',
            2 => 'Malo',
            3 => 'Muy malo',
            default => 'No definido',
        };
    }

    private function textoActividad($valor): string
    {
        return match ((int) $valor) {
            0 => 'Sin repetición importante',
            1 => 'Repetitiva leve / estática moderada',
            2 => 'Repetitiva moderada',
            3 => 'Repetitiva intensa / cambios bruscos',
            default => 'No definido',
        };
    }

private function cleanWordText($value): string
{
    $text = (string) ($value ?? '');

    // Quita etiquetas HTML
    $text = strip_tags($text);

    // Fuerza UTF-8 válido
    $text = mb_convert_encoding($text, 'UTF-8', 'UTF-8');

    // Quita caracteres inválidos para XML/DOCX
    $text = preg_replace('/[^\x09\x0A\x0D\x20-\x{D7FF}\x{E000}-\x{FFFD}]/u', '', $text);

    // Normaliza saltos
    $text = str_replace(["\r\n", "\r"], "\n", $text);

    return trim($text ?? '');
}

private function safeDocxText($value): string
{
    $text = (string) ($value ?? '');

    $text = strip_tags($text);

    $converted = @iconv('UTF-8', 'UTF-8//IGNORE', $text);
    if ($converted !== false) {
        $text = $converted;
    }

    $text = mb_convert_encoding($text, 'UTF-8', 'UTF-8');

    $text = preg_replace('/[^\x09\x0A\x0D\x20-\x{D7FF}\x{E000}-\x{FFFD}]/u', '', $text);

    $text = str_replace(["\r\n", "\r"], "\n", $text);

    return trim($text ?? '');
}



    public function excel($id, RebaReportService $reportService)
{
    $reba = $reportService->findOrFail((int) $id);
    $data = $reportService->build($reba);

    $empresa = $reba->evaluacion->empresa->nombre ?? 'empresa';
    $fecha = now()->format('Y-m-d');
    $codigo = 'ERG-' . now()->format('Ymd-His');

    $nombreArchivo = 'reporte_' .
        $this->limpiarNombreArchivo($empresa) .
        '_REBA_' .
        $codigo .
        '_' .
        $fecha .
        '.xlsx';

    return Excel::download(
        new RebaExport($data),
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

public function word($id, RebaReportService $reportService)
{
    $reba = $reportService->findOrFail((int) $id);
    $data = $reportService->build($reba);

    $tempDir = storage_path('app/temp');

    if (!is_dir($tempDir)) {
        mkdir($tempDir, 0775, true);
    }

    $fileName = 'reba_' . $reba->id . '.docx';
    $filePath = $tempDir . DIRECTORY_SEPARATOR . $fileName;

    $phpWord = new \PhpOffice\PhpWord\PhpWord();
    $phpWord->setDefaultFontName('Arial');
    $phpWord->setDefaultFontSize(10);

    $section = $phpWord->addSection();

    $section->addText('Reporte de Evaluación REBA', ['bold' => true, 'size' => 16]);
    $section->addTextBreak(1);

    $section->addText('Prueba base correcta');
    $section->addTextBreak(1);

    $section->addText('Datos generales', ['bold' => true]);

    foreach ($data['general'] as $label => $value) {
        $texto = $this->safeDocxText(ucfirst(str_replace('_', ' ', $label)) . ': ' . (string) $value);
        $section->addText($texto);
    }

    $section->addTextBreak(1);

    $section->addText('Resultado', ['bold' => true]);
    $section->addText($this->safeDocxText('Nivel de riesgo: ' . (string) $data['nivel_riesgo']));
    $section->addText($this->safeDocxText('Acción requerida: ' . (string) $data['accion_requerida']));

    $section->addTextBreak(1);

    $section->addText('Puntuaciones', ['bold' => true]);

    foreach ($data['scores'] as $score) {
        $linea = $this->safeDocxText((string) $score['label'] . ': ' . (string) $score['value']);
        $section->addText($linea);
    }

    $section->addTextBreak(1);

    $section->addText('Detalle (primeras 5 filas)', ['bold' => true]);

    foreach (array_slice($data['detalles'], 0, 5) as $detalle) {
        $linea = 'Sección: ' . $this->safeDocxText($detalle['seccion'])
            . ' | Concepto: ' . $this->safeDocxText($detalle['concepto'])
            . ' | Valor: ' . $this->safeDocxText($detalle['valor'])
            . ' | Puntaje: ' . $this->safeDocxText($detalle['puntaje']);

        $section->addText($linea);
    }

    $writer = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
    $writer->save($filePath);

    if (function_exists('ob_get_length') && ob_get_length()) {
        ob_end_clean();
    }

    return response()->download($filePath, $fileName, [
        'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    ])->deleteFileAfterSend(true);
}
}