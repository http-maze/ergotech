<x-app-layout>
    <div class="max-w-7xl mx-auto py-8 px-6">

        {{-- ENCABEZADO --}}
        <div class="flex items-center justify-between mb-8">
            <div>
                <h1 class="text-3xl font-bold text-slate-900">
                    Reporte de Evaluación Ley Silla
                </h1>
                <p class="text-slate-500 mt-1">
                    Resultado de cumplimiento generado por ErgoTech
                </p>
            </div>

            <div class="flex items-center gap-3">
                <a href="{{ route('ley_silla.pdf', $leySilla->id) }}"
                   class="px-5 py-3 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-semibold shadow">
                    Descargar PDF
                </a>

                <a href="{{ route('evaluaciones.index') }}"
                   class="px-5 py-3 rounded-xl bg-white hover:bg-slate-50 text-slate-700 font-semibold border border-slate-300 shadow-sm">
                    Volver
                </a>
            </div>
        </div>

        {{-- CONTENIDO EN 2 COLUMNAS --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            {{-- COLUMNA IZQUIERDA --}}
            <div class="lg:col-span-2 space-y-6">

                {{-- DATOS GENERALES --}}
                <div class="bg-white rounded-2xl shadow border border-gray-200 overflow-hidden">
                    <div class="px-6 py-4 border-b bg-white">
                        <h2 class="text-xl font-bold text-slate-900">Datos generales</h2>
                    </div>

                    <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <p class="text-sm text-slate-500">Empresa</p>
                            <p class="font-semibold">{{ $leySilla->evaluacion->empresa->nombre ?? 'N/A' }}</p>
                        </div>

                        <div>
                            <p class="text-sm text-slate-500">Sucursal</p>
                            <p class="font-semibold">{{ $leySilla->evaluacion->sucursal->nombre ?? 'N/A' }}</p>
                        </div>

                        <div>
                            <p class="text-sm text-slate-500">Puesto</p>
                            <p class="font-semibold">{{ $leySilla->evaluacion->puesto->nombre ?? 'N/A' }}</p>
                        </div>

                        <div>
                            <p class="text-sm text-slate-500">Trabajador</p>
                            <p class="font-semibold">{{ $leySilla->evaluacion->trabajador->nombre ?? 'N/A' }}</p>
                        </div>

                        <div>
                            <p class="text-sm text-slate-500">Fecha</p>
                            <p class="font-semibold">{{ $leySilla->evaluacion->fecha_evaluacion ?? 'N/A' }}</p>
                        </div>

                        <div>
                            <p class="text-sm text-slate-500">Evaluador</p>
                            <p class="font-semibold">{{ $leySilla->evaluacion->usuario->name ?? 'N/A' }}</p>
                        </div>

                        <div>
                            <p class="text-sm text-slate-500">Área evaluada</p>
                            <p class="font-semibold">{{ $leySilla->evaluacion->area_evaluada ?? 'N/A' }}</p>
                        </div>

                        <div>
                            <p class="text-sm text-slate-500">Actividad</p>
                            <p class="font-semibold">{{ $leySilla->evaluacion->actividad ?? 'N/A' }}</p>
                        </div>

                        <div class="md:col-span-2">
                            <p class="text-sm text-slate-500">Observaciones</p>
                            <p class="font-semibold">{{ $leySilla->observaciones ?? 'Sin observaciones' }}</p>
                        </div>
                    </div>
                </div>

                {{-- DETALLE --}}
                <div class="bg-white rounded-2xl shadow border border-gray-200 overflow-hidden">
                    <div class="px-6 py-4 border-b bg-white">
                        <h2 class="text-xl font-bold text-slate-900">Detalle de la evaluación</h2>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-slate-50">
                                <tr>
                                    <th class="px-4 py-3 text-left">Sección</th>
                                    <th class="px-4 py-3 text-left">Concepto</th>
                                    <th class="px-4 py-3 text-left">Valor</th>
                                    <th class="px-4 py-3 text-left">Puntaje</th>
                                </tr>
                            </thead>

                            <tbody class="divide-y divide-gray-200">
                                @foreach($leySilla->detalles as $detalle)
                                    <tr>
                                        <td class="px-4 py-3">{{ $detalle->seccion }}</td>
                                        <td class="px-4 py-3">{{ $detalle->concepto }}</td>
                                        <td class="px-4 py-3">{{ $detalle->valor }}</td>
                                        <td class="px-4 py-3">{{ $detalle->puntaje ?? '-' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>

            {{-- COLUMNA DERECHA --}}
            <div class="space-y-6">

                {{-- NIVEL DE RIESGO --}}
                <div class="rounded-2xl border border-yellow-300 bg-yellow-50 p-6 shadow-sm">
                    <p class="text-xs font-bold text-yellow-700 uppercase">
                        Nivel de riesgo
                    </p>

                    <p class="text-3xl font-bold text-yellow-700 mt-3">
                        {{ $leySilla->nivel_riesgo }}
                    </p>

                    <p class="text-sm text-slate-700 mt-4">
                        Resultado:
                        <span class="font-bold">{{ $leySilla->resultado_cumplimiento }}</span>
                    </p>

                    <p class="text-sm text-slate-700 mt-2">
                        Puntaje:
                        <span class="font-bold">{{ $leySilla->puntaje_total }} / 12</span>
                    </p>
                </div>

                {{-- PUNTUACIONES --}}
                <div class="bg-white rounded-2xl shadow border border-gray-200 overflow-hidden">
                    <div class="px-6 py-4 border-b bg-white">
                        <h2 class="text-xl font-bold text-slate-900">Puntuaciones</h2>
                    </div>

                    <div class="p-6 grid grid-cols-1 gap-4">
                        <div class="rounded-xl bg-blue-50 border border-blue-100 p-5 text-center">
                            <p class="text-xs font-bold text-blue-700 uppercase">
                                Puntaje total
                            </p>
                            <p class="text-3xl font-bold text-slate-900 mt-2">
                                {{ $leySilla->puntaje_total }}
                            </p>
                        </div>

                        <div class="rounded-xl bg-blue-50 border border-blue-100 p-5 text-center">
                            <p class="text-xs font-bold text-blue-700 uppercase">
                                Horas de pie
                            </p>
                            <p class="text-3xl font-bold text-slate-900 mt-2">
                                {{ $leySilla->horas_de_pie }}
                            </p>
                        </div>

                        <div class="rounded-xl bg-blue-50 border border-blue-100 p-5 text-center">
                            <p class="text-xs font-bold text-blue-700 uppercase">
                                Bipedestación prolongada
                            </p>
                            <p class="text-2xl font-bold text-slate-900 mt-2">
                                {{ $leySilla->bipedestacion_prolongada ? 'Sí' : 'No' }}
                            </p>
                        </div>
                    </div>
                </div>

                {{-- RECOMENDACIONES --}}
                <div class="bg-white rounded-2xl shadow border border-gray-200 overflow-hidden">
                    <div class="px-6 py-4 border-b bg-white">
                        <h2 class="text-xl font-bold text-slate-900">Recomendaciones</h2>
                    </div>

                    <div class="p-6">
                        <p class="text-slate-700 leading-relaxed">
                            {{ $leySilla->recomendaciones }}
                        </p>
                    </div>
                </div>

            </div>
        </div>
    </div>
</x-app-layout>