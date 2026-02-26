<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Iniciar Sesión</title>
    @vite(['resources/css/app.css'])
</head>

<body class="bg-gray-100 flex items-center justify-center min-h-screen">

    <div class="bg-white shadow-2xl rounded-2xl p-8 w-full max-w-md">

        <h2 class="text-3xl font-bold text-center text-gray-800 mb-6">
            Bienvenida a Ergotech
        </h2>

        <form method="POST" action="{{ route('login') }}" class="space-y-5">
            @csrf

            <div>
                <label class="block text-gray-600 mb-1">Correo electrónico</label>
                <input type="email" name="email" required autofocus
                    class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:outline-none">
            </div>

            <div>
                <label class="block text-gray-600 mb-1">Contraseña</label>
                <input type="password" name="password" required
                    class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:outline-none">
            </div>

            <div class="flex items-center justify-between text-sm">
                <label class="flex items-center space-x-2">
                    <input type="checkbox" name="remember" class="rounded">
                    <span>Recordarme</span>
                </label>

                <a href="{{ route('password.request') }}"
                   class="text-blue-600 hover:underline">
                    ¿Olvidaste tu contraseña?
                </a>
            </div>

            <button type="submit"
                class="w-full bg-blue-600 text-white py-3 rounded-xl font-semibold hover:bg-blue-700 transition duration-300">
                Iniciar sesión
            </button>
        </form>

        <div class="my-6 border-t"></div>

        <a href="{{ route('register') }}"
           class="block w-full text-center border border-blue-600 text-blue-600 py-3 rounded-xl font-semibold hover:bg-blue-50 transition duration-300">
            Crear cuenta nueva
        </a>

    </div>

</body>
</html>

</body>
</html>
