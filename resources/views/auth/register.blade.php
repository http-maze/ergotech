<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Crear Cuenta</title>
    @vite(['resources/css/app.css'])
</head>

<body class="bg-gray-100 flex items-center justify-center min-h-screen">

    <div class="bg-white shadow-2xl rounded-2xl p-8 w-full max-w-md">

        <h2 class="text-3xl font-bold text-center text-gray-800 mb-6">
            Crear cuenta 
        </h2>

        <form method="POST" action="{{ route('register') }}" class="space-y-5">
            @csrf

            <!-- Nombre -->
            <div>
                <label class="block text-gray-600 mb-1">Nombre</label>
                <input type="text" name="name" value="{{ old('name') }}" required autofocus
                    class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:outline-none">
                @error('name')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Email -->
            <div>
                <label class="block text-gray-600 mb-1">Correo electrónico</label>
                <input type="email" name="email" value="{{ old('email') }}" required
                    class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:outline-none">
                @error('email')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Password -->
            <div>
                <label class="block text-gray-600 mb-1">Contraseña</label>
                <input type="password" name="password" required
                    class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:outline-none">
                @error('password')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Confirm Password -->
            <div>
                <label class="block text-gray-600 mb-1">Confirmar contraseña</label>
                <input type="password" name="password_confirmation" required
                    class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:outline-none">
                @error('password_confirmation')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Políticas -->
            <div class="flex items-start space-x-2">
                <input type="checkbox"
                       name="accepted_policies"
                       required
                       class="mt-1 rounded">
                <span class="text-sm text-gray-600">
                    Acepto las políticas de seguridad y privacidad
                </span>
            </div>

            <!-- Botón -->
            <button type="submit"
                class="w-full bg-blue-600 text-white py-3 rounded-xl font-semibold hover:bg-blue-700 transition duration-300">
                Registrarse
            </button>

        </form>

        <div class="my-6 border-t"></div>

        <div class="text-center">
            <a href="{{ route('login') }}"
               class="text-blue-600 hover:underline font-medium">
                ¿Ya tienes cuenta? Inicia sesión
            </a>
        </div>

    </div>

</body>
</html>
