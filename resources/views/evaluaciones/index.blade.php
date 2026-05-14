<x-app-layout>
    <div class="max-w-7xl mx-auto py-8 px-6">
        <div class="bg-white shadow-lg rounded-2xl border border-gray-200 overflow-hidden">
            <div class="bg-sky-600 text-white px-6 py-4 flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-bold">Evaluaciones</h2>
                    <p class="text-sm text-blue-100">Listado general de evaluaciones registradas</p>
                </div>

                <a href="{{ route('evaluaciones.create') }}"
                   class="bg-white hover:bg-blue-50 text-sky-700 font-semibold px-5 py-2.5 rounded-lg shadow-sm transition">
                    Nueva evaluación
                </a>
            </div>

            <div class="p-6">
                @if(session('success'))
                    <div class="mb-4 rounded-lg bg-green-100 border border-green-300 text-green-700 px-4 py-3">
                        {{ session('success') }}
                    </div>
                @endif

                @if(session('error'))
                    <div class="mb-4 rounded-lg bg-red-100 border border-red-300 text-red-700 px-4 py-3">
                        {{ session('error') }}
                    </div>
                @endif

                <div class="overflow-x-auto">
                    <table class="min-w-full border border-gray-200 rounded-lg overflow-hidden">
                        <thead class="bg-gray-100">
                            <tr>
                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">ID</th>
                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Empresa</th>
                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Sucursal</th>
                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Puesto</th>
                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Trabajador</th>
                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Método</th>
                                <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Fecha</th>
                                <th class="px-4 py-3 text-center text-sm font-semibold text-gray-700">Acciones</th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-gray-200">
                            @forelse($evaluaciones as $evaluacion)
                                @php
                                    $metodo = strtoupper($evaluacion->metodo->nombre ?? '');
                                @endphp

                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3 text-sm text-gray-700 font-medium">
                                        {{ $evaluacion->id }}
                                    </td>

                                    <td class="px-4 py-3 text-sm text-gray-700">
                                        {{ $evaluacion->empresa->nombre ?? 'N/A' }}
                                    </td>

                                    <td class="px-4 py-3 text-sm text-gray-700">
                                        {{ $evaluacion->sucursal->nombre ?? 'N/A' }}
                                    </td>

                                    <td class="px-4 py-3 text-sm text-gray-700">
                                        {{ $evaluacion->puesto->nombre ?? 'N/A' }}
                                    </td>

                                    <td class="px-4 py-3 text-sm text-gray-700">
                                        {{ trim(($evaluacion->trabajador->nombre ?? '') . ' ' . ($evaluacion->trabajador->apellido_paterno ?? '') . ' ' . ($evaluacion->trabajador->apellido_materno ?? '')) ?: 'N/A' }}
                                    </td>

                                    <td class="px-4 py-3 text-sm">
                                        @if($metodo === 'REBA')
                                            <span class="inline-block px-3 py-1 rounded-full bg-cyan-100 text-cyan-700 text-xs font-semibold">
                                                REBA
                                            </span>
                                        @elseif($metodo === 'RULA')
                                            <span class="inline-block px-3 py-1 rounded-full bg-blue-100 text-blue-700 text-xs font-semibold">
                                                RULA
                                            </span>
                                        @elseif($metodo === 'OWAS')
                                            <span class="inline-block px-3 py-1 rounded-full bg-purple-100 text-purple-700 text-xs font-semibold">
                                                OWAS
                                            </span>
                                        @elseif($metodo === 'NIOSH')
                                            <span class="inline-block px-3 py-1 rounded-full bg-emerald-100 text-emerald-700 text-xs font-semibold">
                                                NIOSH
                                            </span>
                                        @elseif($metodo === 'NOM-036' || $metodo === 'NOM036' || $metodo === 'NOM 036')
                                            <span class="inline-block px-3 py-1 rounded-full bg-amber-100 text-amber-700 text-xs font-semibold">
                                                NOM-036
                                            </span>
                                        
                                        @elseif($metodo === 'LEY SILLA')
                                            <span class="inline-block px-3 py-1 rounded-full bg-yellow-100 text-yellow-700 text-xs font-semibold">
                                                LEY SILLA
                                            </span>
                                            
                                        @else
                                            <span class="inline-block px-3 py-1 rounded-full bg-gray-100 text-gray-600 text-xs font-semibold">
                                                {{ $metodo ?: 'N/A' }}
                                            </span>
                                        @endif
                                    </td>

                                    <td class="px-4 py-3 text-sm text-gray-700">
                                        {{ $evaluacion->fecha_evaluacion ?? 'N/A' }}
                                    </td>

                                    <td class="px-4 py-3 text-center">
                                        <div class="flex flex-wrap justify-center items-center gap-2">
                                            @if($metodo === 'REBA' && $evaluacion->rebaEvaluacion)
                                                <a href="{{ route('reba.show', $evaluacion->rebaEvaluacion->id) }}"
                                                   class="inline-flex items-center justify-center w-[100px] h-[38px] bg-sky-600 hover:bg-sky-700 text-white text-sm font-semibold rounded-lg transition">
                                                    Ver
                                                </a>
                                            @elseif($metodo === 'RULA' && $evaluacion->rulaEvaluacion)
                                                <a href="{{ route('rula.show', $evaluacion->rulaEvaluacion->id) }}"
                                                   class="inline-flex items-center justify-center w-[100px] h-[38px] bg-sky-600 hover:bg-sky-700 text-white text-sm font-semibold rounded-lg transition">
                                                    Ver
                                                </a>
                                            @elseif($metodo === 'OWAS' && $evaluacion->owasEvaluacion)
                                                <a href="{{ route('owas.show', $evaluacion->owasEvaluacion->id) }}"
                                                   class="inline-flex items-center justify-center w-[100px] h-[38px] bg-sky-600 hover:bg-sky-700 text-white text-sm font-semibold rounded-lg transition">
                                                    Ver
                                                </a>
                                            @elseif($metodo === 'NIOSH' && $evaluacion->nioshEvaluacion)
                                                <a href="{{ route('niosh.show', $evaluacion->nioshEvaluacion->id) }}"
                                                   class="inline-flex items-center justify-center w-[100px] h-[38px] bg-sky-600 hover:bg-sky-700 text-white text-sm font-semibold rounded-lg transition">
                                                    Ver
                                                </a>
                                            @elseif(($metodo === 'NOM-036' || $metodo === 'NOM036' || $metodo === 'NOM 036') && $evaluacion->nom036)
                                                <a href="{{ route('nom036.show', $evaluacion->nom036->id) }}"
                                                   class="inline-flex items-center justify-center w-[100px] h-[38px] bg-sky-600 hover:bg-sky-700 text-white text-sm font-semibold rounded-lg transition">
                                                    Ver
                                                </a>

                                                @elseif($metodo === 'LEY SILLA' && $evaluacion->leySilla)
                                                    <a href="{{ route('ley_silla.show', $evaluacion->leySilla->id) }}"
                                                       class="inline-flex items-center justify-center w-[100px] h-[38px] bg-sky-600 hover:bg-sky-700 text-white text-sm font-semibold rounded-lg transition">
                                                        Ver
                                                    </a>

                                                @elseif(($metodo === 'ERGONOMIA GENERAL' || $metodo === 'ERGONOMÍA GENERAL') && $evaluacion->ergonomiaGeneral)
                                                    <a href="{{ route('ergonomia_general.show', $evaluacion->ergonomiaGeneral->id) }}"
                                                       class="inline-flex items-center justify-center w-[100px] h-[38px] bg-sky-600 hover:bg-sky-700 text-white text-sm font-semibold rounded-lg transition">
                                                        Ver
                                                    </a>    

                                                @else
                                                    <span class="inline-flex items-center justify-center w-[100px] h-[38px] bg-gray-100 text-gray-400 text-sm font-semibold rounded-lg">
                                                        Sin detalle
                                                    </span>
                                                @endif

                                            <a href="{{ route('evaluaciones.edit', $evaluacion->id) }}"
                                               class="inline-flex items-center justify-center w-[100px] h-[38px] bg-sky-100 hover:bg-sky-200 text-sky-700 text-sm font-semibold rounded-lg transition">
                                                Editar
                                            </a>

                                            <form action="{{ route('evaluaciones.destroy', $evaluacion->id) }}"
                                                  method="POST"
                                                  onsubmit="return confirm('¿Eliminar esta evaluación? Esta acción no se puede deshacer.')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                        class="inline-flex items-center justify-center w-[100px] h-[38px] bg-red-100 hover:bg-red-200 text-red-700 text-sm font-semibold rounded-lg transition">
                                                    Eliminar
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-4 py-6 text-center text-gray-500">
                                        No hay evaluaciones registradas.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>