<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>ErgoTech</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-gray-100">

<!-- SIDEBAR -->
<aside class="w-64 bg-blue-700 min-h-screen text-white fixed">

    <div class="p-6 text-2xl font-bold border-b border-blue-500">
        ERGOTECH
    </div>

    <nav class="p-4 space-y-3 text-sm">

        <p class="text-blue-300 text-xs uppercase tracking-wider"> Principal</p>
        <a href="{{ route('dashboard') }}" class="block px-4 py-2 rounded hover:bg-blue-600"> Dashboard</a>

        <p class="text-blue-300 text-xs uppercase tracking-wider mt-4">Gestión</p>
        <a href="{{ route('empresas.index') }}" class="block px-4 py-2 rounded hover:bg-blue-600"> Empresas</a>
        <a href="#" class="block px-4 py-2 rounded hover:bg-blue-600"> Puestos</a>

        <p class="text-blue-300 text-xs uppercase tracking-wider mt-4">Sistema</p>
        <a href="{{ route('usuarios.index') }}" class="block px-4 py-2 rounded hover:bg-blue-600"> Usuarios</a>

        <p class="text-blue-300 text-xs uppercase tracking-wider mt-4">Evaluación</p>
        <a href="{{ route('evaluaciones.index') }}" class="block px-4 py-2 rounded hover:bg-blue-600"> Evaluaciones</a>

        <p class="text-blue-300 text-xs uppercase tracking-wider mt-4">Cuenta</p>
        <a href="{{ route('profile.edit') }}" class="block px-4 py-2 rounded hover:bg-blue-600"> Perfil</a>

    </nav>

    <div class="absolute bottom-0 w-full p-5 border-t border-blue-600">
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button class="w-full text-left px-4 py-2 rounded hover:bg-red-500 hover:text-white text-red-200">
                 Cerrar sesión
            </button>
        </form>
    </div>

</aside>

<!-- CONTENIDO -->
<div class="ml-64 min-h-screen flex flex-col">

    <!-- HEADER SUPERIOR -->
    <header class="bg-white shadow px-8 py-4 flex justify-between items-center">
        <h1 class="text-xl font-semibold text-gray-800">
            Panel Administrador
        </h1>

        <div class="text-sm text-gray-600">
            {{ Auth::user()->name }}
        </div>
    </header>

    <!-- CONTENIDO DINÁMICO -->
    <main class="p-8">
        {{ $slot }}
    </main>

</div>

</body>
</html>