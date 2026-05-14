<?php

namespace App\Http\Controllers;

use App\Models\Evaluacion;
use App\Models\NioshDetalle;
use App\Models\NioshEvaluacion;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Exports\NioshExport;
use Maatwebsite\Excel\Facades\Excel;

class NioshController extends Controller
{
    public function index()
    {
        $nioshEvaluaciones = NioshEvaluacion::with([
            'evaluacion.empresa',
            'evaluacion.sucursal',
            'evaluacion.puesto',
            'evaluacion.trabajador',
            'evaluacion.usuario',
        ])->latest()->paginate(10);

        return view('niosh.index', compact('nioshEvaluaciones'));
    }

    public function create($evaluacion)
    {
        $evaluacion = Evaluacion::with([
            'empresa',
            'sucursal',
            'puesto',
            'trabajador',
            'metodo',
            'usuario',
        ])->findOrFail($evaluacion);

        return view('niosh.create', compact('evaluacion'));
    }

    public function store(Request $request, $evaluacion)
    {
        $evaluacion = Evaluacion::with([
            'empresa',
            'sucursal',
            'puesto',
            'trabajador',
            'metodo',
            'usuario',
        ])->findOrFail($evaluacion);

        $request->validate([
            'distancia_horizontal' => 'required|numeric|min:1',
            'altura_inicial' => 'required|numeric|min:0',
            'desplazamiento_vertical' => 'required|numeric|min:1',
            'angulo_asimetria' => 'required|numeric|min:0',
            'frecuencia_levantamiento' => 'required|numeric|min:0.01',
            'duracion' => 'required|string|in:corta,moderada,larga',
            'calidad_agarre' => 'required|string|in:bueno,regular,malo',
            'peso_objeto' => 'required|numeric|min:0.01',
        ]);

        $H = (float) $request->distancia_horizontal;
        $V = (float) $request->altura_inicial;
        $D = (float) $request->desplazamiento_vertical;
        $A = (float) $request->angulo_asimetria;
        $F = (float) $request->frecuencia_levantamiento;
        $duracion = $request->duracion;
        $agarre = $request->calidad_agarre;
        $peso = (float) $request->peso_objeto;

        $LC = 23.00;

        $HM = $this->calcularHM($H);
        $VM = $this->calcularVM($V);
        $DM = $this->calcularDM($D);
        $AM = $this->calcularAM($A);
        $FM = $this->calcularFM($F, $duracion, $V);
        $CM = $this->calcularCM($agarre, $V);

        $RWL = round($LC * $HM * $VM * $DM * $AM * $FM * $CM, 2);
        $IL = $RWL > 0 ? round($peso / $RWL, 2) : 0.00;
        $nivelRiesgo = $this->clasificarRiesgo($IL);

        $niosh = NioshEvaluacion::create([
            'evaluacion_id' => $evaluacion->id,
            'distancia_horizontal' => $H,
            'altura_inicial' => $V,
            'desplazamiento_vertical' => $D,
            'angulo_asimetria' => $A,
            'frecuencia_levantamiento' => $F,
            'duracion' => $duracion,
            'calidad_agarre' => $agarre,
            'peso_objeto' => $peso,
            'constante_carga' => $LC,
            'hm' => $HM,
            'vm' => $VM,
            'dm' => $DM,
            'am' => $AM,
            'fm' => $FM,
            'cm' => $CM,
            'rwl' => $RWL,
            'indice_levantamiento' => $IL,
            'nivel_riesgo' => $nivelRiesgo,
        ]);

        $detalles = [
            [
                'seccion' => 'Datos de entrada',
                'concepto' => 'Distancia horizontal (H)',
                'valor' => $H . ' cm',
                'resultado' => (string) $HM,
            ],
            [
                'seccion' => 'Datos de entrada',
                'concepto' => 'Altura inicial (V)',
                'valor' => $V . ' cm',
                'resultado' => (string) $VM,
            ],
            [
                'seccion' => 'Datos de entrada',
                'concepto' => 'Desplazamiento vertical (D)',
                'valor' => $D . ' cm',
                'resultado' => (string) $DM,
            ],
            [
                'seccion' => 'Datos de entrada',
                'concepto' => 'Ángulo de asimetría (A)',
                'valor' => $A . '°',
                'resultado' => (string) $AM,
            ],
            [
                'seccion' => 'Datos de entrada',
                'concepto' => 'Frecuencia de levantamiento',
                'valor' => $F . ' lev/min',
                'resultado' => (string) $FM,
            ],
            [
                'seccion' => 'Datos de entrada',
                'concepto' => 'Duración',
                'valor' => ucfirst($duracion),
                'resultado' => ucfirst($duracion),
            ],
            [
                'seccion' => 'Datos de entrada',
                'concepto' => 'Calidad de agarre',
                'valor' => ucfirst($agarre),
                'resultado' => (string) $CM,
            ],
            [
                'seccion' => 'Resultado',
                'concepto' => 'Constante de carga (LC)',
                'valor' => '23 kg',
                'resultado' => '23',
            ],
            [
                'seccion' => 'Resultado',
                'concepto' => 'Peso del objeto',
                'valor' => $peso . ' kg',
                'resultado' => (string) $peso,
            ],
            [
                'seccion' => 'Resultado',
                'concepto' => 'Límite de peso recomendado (RWL)',
                'valor' => 'LC × HM × VM × DM × AM × FM × CM',
                'resultado' => $RWL . ' kg',
            ],
            [
                'seccion' => 'Resultado',
                'concepto' => 'Índice de levantamiento (IL)',
                'valor' => $peso . ' / ' . ($RWL > 0 ? $RWL : '0'),
                'resultado' => (string) $IL,
            ],
            [
                'seccion' => 'Resultado',
                'concepto' => 'Nivel de riesgo',
                'valor' => 'Clasificación final',
                'resultado' => $nivelRiesgo,
            ],
        ];

        foreach ($detalles as $detalle) {
            NioshDetalle::create([
                'niosh_evaluacion_id' => $niosh->id,
                'seccion' => $detalle['seccion'] ?? '',
                'concepto' => $detalle['concepto'] ?? '',
                'valor' => isset($detalle['valor']) ? (string) $detalle['valor'] : null,
                'resultado' => isset($detalle['resultado']) ? (string) $detalle['resultado'] : null,
            ]);
        }

        return redirect()->route('niosh.show', $niosh->id)
            ->with('success', 'Evaluación NIOSH creada correctamente.');
    }

    public function show($id)
    {
        $niosh = NioshEvaluacion::with([
            'evaluacion.empresa',
            'evaluacion.sucursal',
            'evaluacion.puesto',
            'evaluacion.trabajador',
            'evaluacion.usuario',
            'detalles'
        ])->findOrFail($id);

        return view('niosh.show', compact('niosh'));
    }

   public function pdf($id)
{
    $niosh = NioshEvaluacion::with([
        'evaluacion.empresa',
        'evaluacion.sucursal',
        'evaluacion.puesto',
        'evaluacion.trabajador',
        'evaluacion.usuario',
        'detalles'
    ])->findOrFail($id);

    $pdf = Pdf::loadView('niosh.pdf', compact('niosh'))
        ->setPaper('a4', 'portrait');

    $empresa = $niosh->evaluacion->empresa->nombre ?? 'empresa';
    $fecha = now()->format('Y-m-d');
    $codigo = 'ERG-' . now()->format('Ymd-His');

    $nombreArchivo = 'reporte_' .
        $this->limpiarNombreArchivo($empresa) .
        '_NIOSH_' .
        $codigo .
        '_' .
        $fecha .
        '.pdf';

    return $pdf->download($nombreArchivo);
}

    private function calcularHM($H)
    {
        if ($H < 25) {
            $H = 25;
        }

        $hm = 25 / $H;
        return round(min($hm, 1), 3);
    }

    private function calcularVM($V)
    {
        $vm = 1 - (0.003 * abs($V - 75));
        $vm = max($vm, 0);
        return round(min($vm, 1), 3);
    }

    private function calcularDM($D)
    {
        if ($D < 25) {
            $D = 25;
        }

        $dm = 0.82 + (4.5 / $D);
        $dm = max($dm, 0);
        return round(min($dm, 1), 3);
    }

    private function calcularAM($A)
    {
        $am = 1 - (0.0032 * $A);
        $am = max($am, 0);
        return round(min($am, 1), 3);
    }

    private function calcularFM($frecuencia, $duracion, $altura)
    {
        if ($duracion === 'corta') {
            if ($frecuencia <= 0.2) return 1.00;
            if ($frecuencia <= 0.5) return 0.97;
            if ($frecuencia <= 1) return 0.94;
            if ($frecuencia <= 2) return 0.91;
            if ($frecuencia <= 3) return 0.88;
            if ($frecuencia <= 4) return 0.84;
            if ($frecuencia <= 5) return 0.80;
            if ($frecuencia <= 6) return 0.75;
            if ($frecuencia <= 7) return 0.70;
            if ($frecuencia <= 8) return 0.60;
            if ($frecuencia <= 9) return 0.52;
            if ($frecuencia <= 10) return 0.45;
            if ($frecuencia <= 11) return 0.41;
            if ($frecuencia <= 12) return 0.37;
            if ($frecuencia <= 13) return 0.34;
            if ($frecuencia <= 14) return 0.31;
            if ($frecuencia <= 15) return 0.28;
            return 0.25;
        }

        if ($duracion === 'moderada') {
            if ($frecuencia <= 0.2) return 0.95;
            if ($frecuencia <= 0.5) return 0.92;
            if ($frecuencia <= 1) return 0.88;
            if ($frecuencia <= 2) return 0.84;
            if ($frecuencia <= 3) return 0.79;
            if ($frecuencia <= 4) return 0.72;
            if ($frecuencia <= 5) return 0.60;
            if ($frecuencia <= 6) return 0.50;
            if ($frecuencia <= 7) return 0.42;
            if ($frecuencia <= 8) return 0.35;
            if ($frecuencia <= 9) return 0.30;
            if ($frecuencia <= 10) return 0.26;
            if ($frecuencia <= 11) return 0.23;
            if ($frecuencia <= 12) return 0.21;
            return 0.00;
        }

        if ($duracion === 'larga') {
            if ($frecuencia <= 0.2) return 0.85;
            if ($frecuencia <= 0.5) return 0.81;
            if ($frecuencia <= 1) return 0.75;
            if ($frecuencia <= 2) return 0.65;
            if ($frecuencia <= 3) return 0.55;
            if ($frecuencia <= 4) return 0.45;
            if ($frecuencia <= 5) return 0.35;
            if ($frecuencia <= 6) return 0.27;
            if ($frecuencia <= 7) return 0.22;
            if ($frecuencia <= 8) return 0.18;
            return 0.00;
        }

        return 1.00;
    }

    private function calcularCM($agarre, $altura)
    {
        $agarre = strtolower(trim($agarre));

        if ($altura < 75) {
            return match ($agarre) {
                'bueno' => 1.00,
                'regular' => 0.95,
                'malo' => 0.90,
                default => 0.90,
            };
        }

        return match ($agarre) {
            'bueno' => 1.00,
            'regular' => 0.95,
            'malo' => 0.90,
            default => 0.90,
        };
    }

    private function clasificarRiesgo($indice)
    {
        if ($indice <= 1) {
            return 'Bajo';
        }

        if ($indice <= 3) {
            return 'Medio';
        }

        return 'Alto';
    }

 public function excel($id)
{
    $niosh = NioshEvaluacion::with([
        'evaluacion.empresa',
        'evaluacion.sucursal',
        'evaluacion.puesto',
        'evaluacion.trabajador',
        'evaluacion.usuario',
        'detalles',
    ])->findOrFail($id);

    $trabajador = trim(
        ($niosh->evaluacion->trabajador->nombre ?? '') . ' ' .
        ($niosh->evaluacion->trabajador->apellido_paterno ?? '') . ' ' .
        ($niosh->evaluacion->trabajador->apellido_materno ?? '')
    );

    $data = [
        'id' => $niosh->id,
        'general' => [
            'empresa' => $niosh->evaluacion->empresa->nombre ?? 'N/A',
            'sucursal' => $niosh->evaluacion->sucursal->nombre ?? 'N/A',
            'puesto' => $niosh->evaluacion->puesto->nombre ?? 'N/A',
            'trabajador' => $trabajador ?: 'N/A',
            'fecha' => $niosh->evaluacion->fecha_evaluacion ?? 'N/A',
            'evaluador' => $niosh->evaluacion->usuario->name ?? 'N/A',
            'area_evaluada' => $niosh->evaluacion->area_evaluada ?? 'N/A',
            'actividad' => $niosh->evaluacion->actividad ?? 'N/A',
            'observaciones' => $niosh->evaluacion->observaciones ?? 'Sin observaciones',
        ],
        'scores' => [
            ['label' => 'HM', 'value' => (string) ($niosh->hm ?? 'N/A')],
            ['label' => 'VM', 'value' => (string) ($niosh->vm ?? 'N/A')],
            ['label' => 'DM', 'value' => (string) ($niosh->dm ?? 'N/A')],
            ['label' => 'AM', 'value' => (string) ($niosh->am ?? 'N/A')],
            ['label' => 'FM', 'value' => (string) ($niosh->fm ?? 'N/A')],
            ['label' => 'CM', 'value' => (string) ($niosh->cm ?? 'N/A')],
            ['label' => 'RWL', 'value' => (string) ($niosh->rwl ?? 'N/A')],
            ['label' => 'Índice de levantamiento', 'value' => (string) ($niosh->indice_levantamiento ?? 'N/A')],
        ],
        'nivel_riesgo' => $niosh->nivel_riesgo ?? 'N/A',
        'accion_requerida' => 'Índice de levantamiento: ' . ($niosh->indice_levantamiento ?? 'N/A'),
        'detalles' => $niosh->detalles->map(function ($detalle) {
            return [
                'seccion' => $detalle->seccion,
                'concepto' => ucfirst(str_replace('_', ' ', $detalle->concepto)),
                'valor' => $detalle->valor,
                'puntaje' => $detalle->resultado ?? '',
            ];
        })->values()->toArray(),
    ];

   $empresa = $niosh->evaluacion->empresa->nombre ?? 'empresa';
$fecha = now()->format('Y-m-d');
$codigo = 'ERG-' . now()->format('Ymd-His');

$nombreArchivo = 'reporte_' .
    $this->limpiarNombreArchivo($empresa) .
    '_NIOSH_' .
    $codigo .
    '_' .
    $fecha .
    '.xlsx';

return Excel::download(
    new NioshExport($data),
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
