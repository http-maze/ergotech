<x-app-layout>

<div class="flex justify-between items-center mb-6">

    <h2 class="text-2xl font-bold text-gray-800">
        Mis Evaluaciones
    </h2>

    <div class="flex gap-3">

        <a href="{{ route('evaluaciones.create') }}"
           class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 text-sm">
            + Nueva Evaluación
        </a>

        <button class="border border-gray-400 px-4 py-2 rounded text-sm">
            Filtrar
        </button>

    </div>
</div>

<div class="bg-white shadow rounded-lg overflow-hidden">

    <table class="w-full text-sm">

        <thead class="bg-blue-600 text-white">
            <tr>
                <th class="px-4 py-3">#</th>
                <th class="px-4 py-3">Empresa</th>
                <th class="px-4 py-3">Método</th>
                <th class="px-4 py-3">Nivel</th>
                <th class="px-4 py-3">Fecha</th>
                <th class="px-4 py-3 text-center">Acciones</th>
            </tr>
        </thead>

        <tbody class="divide-y">

            @foreach($evaluaciones as $evaluacion)
            <tr class="hover:bg-gray-50">

                <td class="px-4 py-3">#{{ $evaluacion->id }}</td>
                <td class="px-4 py-3">{{ $evaluacion->empresa->nombre ?? '-' }}</td>
                <td class="px-4 py-3">{{ $evaluacion->metodo }}</td>

                <td class="px-4 py-3">
                    <span class="px-2 py-1 rounded text-white text-xs
                        {{ $evaluacion->nivel == 'Alto' ? 'bg-red-500' :
                           ($evaluacion->nivel == 'Medio' ? 'bg-yellow-500' : 'bg-green-500') }}">
                        {{ $evaluacion->nivel }}
                    </span>
                </td>

                <td class="px-4 py-3">{{ $evaluacion->created_at->format('d/m/Y') }}</td>

                <td class="px-4 py-3 flex justify-center gap-3">

                    <a href="{{ route('evaluaciones.show', $evaluacion) }}" class="text-blue-600">👁️</a>
                    <a href="{{ route('evaluaciones.edit', $evaluacion) }}" class="text-yellow-500">✏️</a>
                    <a href="{{ route('evaluaciones.pdf', $evaluacion) }}" class="text-green-600">⬇️</a>

                    <form action="{{ route('evaluaciones.destroy', $evaluacion) }}"
                          method="POST"
                          onsubmit="return confirm('¿Eliminar evaluación?')">
                        @csrf
                        @method('DELETE')
                        <button class="text-red-600">🗑️</button>
                    </form>

                </td>
            </tr>
            @endforeach

        </tbody>
    </table>

</div>

</x-app-layout>
