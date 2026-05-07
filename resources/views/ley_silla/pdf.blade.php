<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte Ley Silla</title>

    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            color: #1f2937;
        }

        .header {
            background: #0369a1;
            color: white;
            padding: 18px;
            border-radius: 8px;
            margin-bottom: 18px;
        }

        .title {
            font-size: 22px;
            font-weight: bold;
            margin: 0;
        }

        .subtitle {
            font-size: 12px;
            margin-top: 5px;
        }

        .section {
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            margin-bottom: 15px;
            padding: 12px;
        }

        .section-title {
            font-size: 15px;
            font-weight: bold;
            color: #0369a1;
            margin-bottom: 8px;
        }

        .grid {
            width: 100%;
            border-collapse: collapse;
        }

        .grid td {
            padding: 6px;
            border-bottom: 1px solid #e5e7eb;
        }

        .label {
            font-weight: bold;
            color: #475569;
        }

        .cards {
            width: 100%;
            margin-bottom: 15px;
        }

        .card {
            width: 32%;
            display: inline-block;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 10px;
            margin-right: 1%;
            vertical-align: top;
            background: #f8fafc;
        }

        .card-title {
            font-size: 11px;
            color: #64748b;
        }

        .card-value {
            font-size: 18px;
            font-weight: bold;
            margin-top: 4px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            background: #f1f5f9;
            text-align: left;
            padding: 7px;
            border: 1px solid #e5e7eb;
        }

        td {
            padding: 7px;
            border: 1px solid #e5e7eb;
        }

        .footer {
            margin-top: 20px;
            font-size: 10px;
            color: #64748b;
            text-align: center;
        }
    </style>
</head>
<body>

    <div class="header">
        <p class="title">Reporte de Evaluación Ley Silla</p>
        <p class="subtitle">
            Evaluación de cumplimiento sobre disponibilidad de sillas con respaldo, descanso y bipedestación prolongada.
        </p>
    </div>

    <div class="cards">
        <div class="card">
            <div class="card-title">Resultado</div>
            <div class="card-value">{{ $leySilla->resultado_cumplimiento }}</div>
        </div>

        <div class="card">
            <div class="card-title">Nivel de riesgo</div>
            <div class="card-value">{{ $leySilla->nivel_riesgo }}</div>
        </div>

        <div class="card">
            <div class="card-title">Puntaje</div>
            <div class="card-value">{{ $leySilla->puntaje_total }} / 12</div>
        </div>
    </div>

    <div class="section">
        <div class="section-title">Datos generales</div>

        <table class="grid">
            <tr>
                <td class="label">Empresa</td>
                <td>{{ $leySilla->evaluacion->empresa->nombre ?? 'N/A' }}</td>
                <td class="label">Sucursal</td>
                <td>{{ $leySilla->evaluacion->sucursal->nombre ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td class="label">Puesto</td>
                <td>{{ $leySilla->evaluacion->puesto->nombre ?? 'N/A' }}</td>
                <td class="label">Trabajador</td>
                <td>{{ $leySilla->evaluacion->trabajador->nombre ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td class="label">Área evaluada</td>
                <td>{{ $leySilla->evaluacion->area_evaluada ?? 'N/A' }}</td>
                <td class="label">Actividad</td>
                <td>{{ $leySilla->evaluacion->actividad ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td class="label">Horas de pie</td>
                <td>{{ $leySilla->horas_de_pie }} horas</td>
                <td class="label">Bipedestación prolongada</td>
                <td>{{ $leySilla->bipedestacion_prolongada ? 'Sí' : 'No' }}</td>
            </tr>
        </table>
    </div>

    <div class="section">
        <div class="section-title">Detalle de cumplimiento</div>

        <table>
            <thead>
                <tr>
                    <th>Sección</th>
                    <th>Concepto</th>
                    <th>Valor</th>
                    <th>Puntaje</th>
                </tr>
            </thead>
            <tbody>
                @foreach($leySilla->detalles as $detalle)
                    <tr>
                        <td>{{ $detalle->seccion }}</td>
                        <td>{{ $detalle->concepto }}</td>
                        <td>{{ $detalle->valor }}</td>
                        <td>{{ $detalle->puntaje ?? '-' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="section">
        <div class="section-title">Recomendaciones</div>
        <p>{{ $leySilla->recomendaciones }}</p>
    </div>

    @if($leySilla->observaciones)
        <div class="section">
            <div class="section-title">Observaciones</div>
            <p>{{ $leySilla->observaciones }}</p>
        </div>
    @endif

    <div class="footer">
        Reporte generado por ErgoTech - {{ now()->format('d/m/Y H:i') }}
    </div>

</body>
</html>