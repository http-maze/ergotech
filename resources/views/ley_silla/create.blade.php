<x-app-layout>
    <div class="max-w-6xl mx-auto py-8 px-6">

        <div class="bg-white rounded-2xl shadow border border-gray-200 overflow-hidden">
            <div class="bg-sky-600 px-6 py-4 text-white">
                <h1 class="text-2xl font-bold">Evaluación Ley Silla</h1>
                <p class="text-sm text-sky-100 mt-1">
                    Evaluación de cumplimiento sobre descanso, sillas con respaldo y bipedestación prolongada.
                </p>
            </div>

            <div class="p-6 space-y-6">

                @if(session('error'))
                    <div class="bg-red-100 text-red-700 px-4 py-3 rounded-lg">
                        {{ session('error') }}
                    </div>
                @endif

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 bg-slate-50 p-5 rounded-xl border">
                    <div>
                        <p class="text-sm text-slate-500">Empresa</p>
                        <p class="font-semibold">{{ $evaluacion->empresa->nombre ?? 'N/A' }}</p>
                    </div>

                    <div>
                        <p class="text-sm text-slate-500">Puesto</p>
                        <p class="font-semibold">{{ $evaluacion->puesto->nombre ?? 'N/A' }}</p>
                    </div>

                    <div>
                        <p class="text-sm text-slate-500">Trabajador</p>
                        <p class="font-semibold">{{ $evaluacion->trabajador->nombre ?? 'N/A' }}</p>
                    </div>

                    <div>
                        <p class="text-sm text-slate-500">Área evaluada</p>
                        <p class="font-semibold">{{ $evaluacion->area_evaluada ?? 'N/A' }}</p>
                    </div>
                </div>

                <form method="POST" action="{{ route('ley_silla.store', $evaluacion->id) }}" class="space-y-6">
                    @csrf

                    <div class="bg-white border rounded-xl p-5">
                        <h2 class="text-lg font-bold text-slate-800 mb-4">1. Datos del puesto</h2>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-semibold text-slate-700 mb-1">
                                    Tipo de puesto
                                </label>
                                <input type="text" name="tipo_puesto"
                                       value="{{ old('tipo_puesto') }}"
                                       placeholder="Ej. Caja, mostrador, producción, atención a clientes"
                                       class="w-full rounded-lg border-gray-300 focus:ring-sky-500 focus:border-sky-500">
                            </div>

                            <div>
                                <label class="block text-sm font-semibold text-slate-700 mb-1">
                                    Horas aproximadas de pie durante la jornada
                                </label>
                                <input type="number" name="horas_de_pie"
                                       value="{{ old('horas_de_pie') }}"
                                       step="0.5" min="0" max="24"
                                       class="w-full rounded-lg border-gray-300 focus:ring-sky-500 focus:border-sky-500"
                                       required>
                                <p class="text-xs text-slate-500 mt-1">
                                    Más de 3 horas continuas se considera bipedestación prolongada.
                                </p>
                            </div>
                        </div>
                    </div>

                    @php
                        $preguntas = [
                            'Disponibilidad de silla' => [
                                'cuenta_con_silla' => '¿El puesto cuenta con silla o asiento disponible?',
                                'silla_con_respaldo' => '¿La silla cuenta con respaldo?',
                                'silla_en_area_cercana' => '¿La silla está en el puesto o en un área cercana accesible?',
                                'sillas_suficientes' => '¿Existen sillas suficientes para el personal que las requiere?',
                                'silla_en_buen_estado' => '¿La silla se encuentra en buen estado y es segura?',
                            ],
                            'Descanso y alternancia postural' => [
                                'permite_sentarse' => '¿Se permite que la persona trabajadora pueda sentarse?',
                                'permite_pausas' => '¿Se permiten pausas o descansos periódicos?',
                                'pausas_definidas' => '¿Las pausas están definidas o son reconocidas por la empresa?',
                                'alternancia_postural' => '¿La actividad permite alternar entre estar sentado y de pie?',
                            ],
                            'Documentación y control interno' => [
                                'reglamento_actualizado' => '¿El reglamento interior o política interna contempla el derecho al descanso?',
                                'evidencia_documental' => '¿Existe evidencia documental de cumplimiento?',
                                'capacitacion_trabajadores' => '¿El personal fue informado o capacitado sobre estas medidas?',
                            ],
                        ];
                    @endphp

                    @foreach($preguntas as $seccion => $items)
                        <div class="bg-white border rounded-xl p-5">
                            <h2 class="text-lg font-bold text-slate-800 mb-4">{{ $seccion }}</h2>

                            <div class="space-y-3">
                                @foreach($items as $campo => $texto)
                                    <label class="flex items-center justify-between gap-4 p-4 rounded-lg border bg-slate-50 hover:bg-slate-100">
                                        <span class="text-sm font-medium text-slate-700">
                                            {{ $texto }}
                                        </span>

                                        <input type="checkbox"
                                               name="{{ $campo }}"
                                               value="1"
                                               {{ old($campo) ? 'checked' : '' }}
                                               class="rounded border-gray-300 text-sky-600 focus:ring-sky-500">
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endforeach

                    <div class="bg-white border rounded-xl p-5">
                        <label class="block text-sm font-semibold text-slate-700 mb-1">
                            Observaciones
                        </label>
                        <textarea name="observaciones" rows="4"
                                  class="w-full rounded-lg border-gray-300 focus:ring-sky-500 focus:border-sky-500"
                                  placeholder="Describe hallazgos importantes del puesto evaluado.">{{ old('observaciones') }}</textarea>
                    </div>

                    <div class="flex justify-end gap-3">
                        <a href="{{ route('evaluaciones.index') }}"
                           class="px-5 py-2.5 rounded-lg bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold">
                            Cancelar
                        </a>

                        <button type="submit"
                                class="px-5 py-2.5 rounded-lg bg-sky-600 hover:bg-sky-700 text-white font-semibold">
                            Guardar evaluación
                        </button>
                    </div>
                </form>

            </div>
        </div>
    </div>
</x-app-layout>