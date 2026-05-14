<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\TwoFactorController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\EmpresaController;
use App\Http\Controllers\SucursalController;
use App\Http\Controllers\PuestoController;
use App\Http\Controllers\TrabajadorController;
use App\Http\Controllers\EvaluacionController;
use App\Http\Controllers\ReporteController;
use App\Http\Controllers\RebaController;
use App\Http\Controllers\RulaController;
use App\Http\Controllers\OwasController;
use App\Http\Controllers\Nom036Controller;
use App\Http\Controllers\NioshController;
use App\Http\Controllers\LeySillaController;
use App\Http\Controllers\ErgonomiaGeneralController;
use App\Http\Controllers\LogController;

// =========================
// INICIO
// =========================
Route::get('/', function () {
    return redirect('/login');
});

// =========================
// 2FA
// =========================
Route::get('/two-factor', [TwoFactorController::class, 'index'])->name('2fa.index');
Route::post('/two-factor', [TwoFactorController::class, 'store'])->name('2fa.store');

// =========================
// DASHBOARD
// =========================
Route::middleware(['auth'])->get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

// =========================
// PERFIL
// =========================
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// =========================
// ADMIN Y EVALUADOR
// =========================
Route::middleware(['auth', 'rol:admin,evaluador'])->group(function () {

    Route::resource('empresas', EmpresaController::class);
    Route::resource('sucursales', SucursalController::class);
    Route::resource('puestos', PuestoController::class);
    Route::resource('trabajadores', TrabajadorController::class);

    Route::resource('evaluaciones', EvaluacionController::class);

    Route::post('/evaluaciones/seleccionar-metodo', [EvaluacionController::class, 'seleccionarMetodo'])
        ->name('evaluaciones.seleccionarMetodo');

    // =========================
    // REBA
    // =========================
    Route::get('/reba', [RebaController::class, 'index'])->name('reba.index');
    Route::get('/reba/create/{evaluacion}', [RebaController::class, 'create'])->name('reba.create');
    Route::post('/reba/store/{evaluacion}', [RebaController::class, 'store'])->name('reba.store');
    Route::get('/reba/{id}/pdf', [RebaController::class, 'pdf'])->name('reba.pdf');
    Route::get('/reba/{id}/excel', [RebaController::class, 'excel'])->name('reba.excel');
    Route::get('/reba/{id}/word', [RebaController::class, 'word'])->name('reba.word');
    Route::get('/reba/{id}', [RebaController::class, 'show'])->name('reba.show');

    // =========================
    // RULA
    // =========================
    Route::get('/rula', [RulaController::class, 'index'])->name('rula.index');
    Route::get('/rula/create/{evaluacion}', [RulaController::class, 'create'])->name('rula.create');
    Route::post('/rula/calcular', [RulaController::class, 'calcular'])->name('rula.calcular');
    Route::post('/rula/store/{evaluacion}', [RulaController::class, 'store'])->name('rula.store');
    Route::get('/rula/{id}', [RulaController::class, 'show'])->name('rula.show');
    Route::get('/rula/{id}/pdf', [RulaController::class, 'pdf'])->name('rula.pdf');
    Route::get('/rula/{id}/excel', [RulaController::class, 'excel'])->name('rula.excel');

    // =========================
    // OWAS
    // =========================
    Route::get('/owas', [OwasController::class, 'index'])->name('owas.index');
    Route::get('/owas/create/{evaluacion}', [OwasController::class, 'create'])->name('owas.create');
    Route::post('/owas/store/{evaluacion}', [OwasController::class, 'store'])->name('owas.store');
    Route::get('/owas/{id}', [OwasController::class, 'show'])->name('owas.show');
    Route::get('/owas/{id}/pdf', [OwasController::class, 'pdf'])->name('owas.pdf');
    Route::get('/owas/{id}/excel', [OwasController::class, 'excel'])->name('owas.excel');

    // =========================
    // NIOSH
    // =========================
    Route::get('/niosh', [NioshController::class, 'index'])->name('niosh.index');
    Route::get('/niosh/create/{evaluacion}', [NioshController::class, 'create'])->name('niosh.create');
    Route::post('/niosh/store/{evaluacion}', [NioshController::class, 'store'])->name('niosh.store');
    Route::get('/niosh/{id}', [NioshController::class, 'show'])->name('niosh.show');
    Route::get('/niosh/{id}/pdf', [NioshController::class, 'pdf'])->name('niosh.pdf');
    Route::get('/niosh/{id}/excel', [NioshController::class, 'excel'])->name('niosh.excel');

    // =========================
    // NOM-036
    // =========================
    Route::get('/nom036/{evaluacion}/create', [Nom036Controller::class, 'create'])->name('nom036.create');
    Route::post('/nom036/{evaluacion}/store', [Nom036Controller::class, 'store'])->name('nom036.store');
    Route::get('/nom036/{id}', [Nom036Controller::class, 'show'])->name('nom036.show');
    Route::get('/nom036/{id}/pdf', [Nom036Controller::class, 'pdf'])->name('nom036.pdf');
    Route::get('/nom036/{id}/excel', [Nom036Controller::class, 'excel'])->name('nom036.excel');

    // =========================
    // LEY SILLA
    // =========================
    Route::get('/ley-silla/create/{evaluacion}', [LeySillaController::class, 'create'])->name('ley_silla.create');
    Route::post('/ley-silla/store/{evaluacion}', [LeySillaController::class, 'store'])->name('ley_silla.store');
    Route::get('/ley-silla/{id}', [LeySillaController::class, 'show'])->name('ley_silla.show');
    Route::get('/ley-silla/{id}/pdf', [LeySillaController::class, 'pdf'])->name('ley_silla.pdf');

    // =========================
    // ERGONOMÍA GENERAL
    // =========================
    Route::get('/ergonomia-general/create/{evaluacion}', [ErgonomiaGeneralController::class, 'create'])
        ->name('ergonomia_general.create');

    Route::post('/ergonomia-general/store/{evaluacion}', [ErgonomiaGeneralController::class, 'store'])
        ->name('ergonomia_general.store');

    Route::get('/ergonomia-general/{id}', [ErgonomiaGeneralController::class, 'show'])
        ->name('ergonomia_general.show');

    Route::get('/ergonomia-general/{id}/pdf', [ErgonomiaGeneralController::class, 'pdf'])
        ->name('ergonomia_general.pdf');
});

// =========================
// REPORTES
// =========================
Route::middleware(['auth', 'rol:admin,evaluador,visitante'])->group(function () {
    Route::get('/reportes', [ReporteController::class, 'index'])->name('reportes.index');
    Route::get('/reportes/excel', [ReporteController::class, 'excel'])->name('reportes.excel');
});

// =========================
// SOLO ADMIN
// =========================
Route::middleware(['auth', 'rol:admin'])->group(function () {

    Route::resource('usuarios', UserController::class);

    Route::get('/logs', [LogController::class, 'index'])
        ->name('logs.index')
        ->middleware('permission:ver logs');
});

// =========================
// PRUEBA
// =========================
Route::get('/prueba-rol', function () {
    return Auth::user()->rol?->nombre;
})->middleware('auth');

// =========================
// AUTH
// =========================
require __DIR__ . '/auth.php';