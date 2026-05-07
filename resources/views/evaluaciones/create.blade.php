<x-app-layout>
    <div class="max-w-5xl mx-auto py-8 px-6">
        <div class="bg-white shadow-lg rounded-2xl overflow-hidden border border-gray-200">
            <div class="bg-sky-600 text-white px-6 py-4">
                <h2 class="text-2xl font-bold">Nueva evaluación</h2>
                <p class="text-sm text-blue-100 mt-1">
                    Captura los datos generales y después selecciona el método de evaluación.
                </p>
            </div>

            <div class="p-6">
                @if(session('error'))
                    <div class="mb-4 rounded-lg bg-red-100 border border-red-300 text-red-700 px-4 py-3">
                        {{ session('error') }}
                    </div>
                @endif

                @if($errors->any())
                    <div class="mb-4 rounded-lg bg-red-100 border border-red-300 text-red-700 px-4 py-3">
                        <ul class="list-disc pl-5 space-y-1">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form action="{{ route('evaluaciones.seleccionarMetodo') }}" method="POST" class="space-y-6">
                    @csrf

                    <div>
                        <h3 class="text-lg font-semibold text-gray-800 mb-4 border-b pb-2">Datos generales</h3>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Empresa</label>
                                <select name="empresa_id" class="w-full rounded-lg border-gray-300 focus:ring-blue-500 focus:border-blue-500" required>
                                    <option value="">Seleccione una empresa</option>
                                    @foreach($empresas as $empresa)
                                        <option value="{{ $empresa->id }}" {{ old('empresa_id') == $empresa->id ? 'selected' : '' }}>
                                            {{ $empresa->nombre }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Sucursal</label>
                                <select name="sucursal_id" class="w-full rounded-lg border-gray-300 focus:ring-blue-500 focus:border-blue-500" required>
                                    <option value="">Seleccione una sucursal</option>
                                    @foreach($sucursales as $sucursal)
                                        <option value="{{ $sucursal->id }}" {{ old('sucursal_id') == $sucursal->id ? 'selected' : '' }}>
                                            {{ $sucursal->nombre }} @if($sucursal->empresa) - {{ $sucursal->empresa->nombre }} @endif
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Puesto</label>
                                <select name="puesto_id" class="w-full rounded-lg border-gray-300 focus:ring-blue-500 focus:border-blue-500" required>
                                    <option value="">Seleccione un puesto</option>
                                    @foreach($puestos as $puesto)
                                        <option value="{{ $puesto->id }}" {{ old('puesto_id') == $puesto->id ? 'selected' : '' }}>
                                            {{ $puesto->nombre }}
                                            @if($puesto->sucursal)
                                                - {{ $puesto->sucursal->nombre }}
                                            @endif
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Trabajador</label>
                                <select name="trabajador_id" class="w-full rounded-lg border-gray-300 focus:ring-blue-500 focus:border-blue-500" required>
                                    <option value="">Seleccione un trabajador</option>
                                    @foreach($trabajadores as $trabajador)
                                        <option value="{{ $trabajador->id }}" {{ old('trabajador_id') == $trabajador->id ? 'selected' : '' }}>
                                            {{ trim(($trabajador->nombre ?? '') . ' ' . ($trabajador->apellido_paterno ?? '') . ' ' . ($trabajador->apellido_materno ?? '')) }}
                                            @if($trabajador->puesto)
                                                - {{ $trabajador->puesto->nombre }}
                                            @endif
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Fecha de evaluación</label>
                                <input
                                    type="date"
                                    name="fecha_evaluacion"
                                    value="{{ old('fecha_evaluacion', date('Y-m-d')) }}"
                                    class="w-full rounded-lg border-gray-300 focus:ring-blue-500 focus:border-blue-500"
                                    required
                                >
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Método</label>
                                <select name="metodo" class="w-full rounded-lg border-gray-300 focus:ring-blue-500 focus:border-blue-500" required>
                                    <option value="">Seleccione un método</option>
                                    <option value="REBA" {{ old('metodo') == 'REBA' ? 'selected' : '' }}>REBA</option>
                                    <option value="RULA" {{ old('metodo') == 'RULA' ? 'selected' : '' }}>RULA</option>
                                    <option value="OWAS" {{ old('metodo') == 'OWAS' ? 'selected' : '' }}>OWAS</option>
                                    <option value="NIOSH" {{ old('metodo') == 'NIOSH' ? 'selected' : '' }}>NIOSH</option>
                                    <option value="NOM-036" {{ old('metodo') == 'NOM-036' ? 'selected' : '' }}>NOM-036</option>
                                    <option value="LEY SILLA" {{ old('metodo') == 'LEY SILLA' ? 'selected' : '' }}>LEY SILLA</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Área evaluada</label>
                                <input
                                    type="text"
                                    name="area_evaluada"
                                    value="{{ old('area_evaluada') }}"
                                    class="w-full rounded-lg border-gray-300 focus:ring-blue-500 focus:border-blue-500"
                                    placeholder="Ej. Producción, laboratorio, empaque"
                                >
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Actividad general</label>
                                <input
                                    type="text"
                                    name="actividad_general"
                                    value="{{ old('actividad_general') }}"
                                    class="w-full rounded-lg border-gray-300 focus:ring-blue-500 focus:border-blue-500"
                                    placeholder="Ej. Ensamble, inspección, calibración"
                                >
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Observaciones generales</label>
                        <textarea
                            name="observaciones"
                            rows="4"
                            class="w-full rounded-lg border-gray-300 focus:ring-blue-500 focus:border-blue-500"
                            placeholder="Escribe observaciones generales de la evaluación..."
                        >{{ old('observaciones') }}</textarea>
                    </div>

                    <div class="flex gap-3 pt-2">
                        <button
                            type="submit"
                            class="bg-blue-700 hover:bg-blue-800 text-white font-semibold px-5 py-2.5 rounded-lg shadow"
                        >
                            Continuar
                        </button>

                        <a
                            href="{{ route('evaluaciones.index') }}"
                            class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold px-5 py-2.5 rounded-lg"
                        >
                            Cancelar
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>