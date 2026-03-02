<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\EmpresaController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\EvaluacionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\TwoFactorController;
use App\Http\Controllers\ReporteController;
use Illuminate\Support\Facades\Auth;

/*
|--------------------------------------------------------------------------
| 2FA
|--------------------------------------------------------------------------
*/
Route::get('/two-factor', [TwoFactorController::class, 'index'])->name('2fa.index');
Route::post('/two-factor', [TwoFactorController::class, 'store'])->name('2fa.store');

/*
|--------------------------------------------------------------------------
| Inicio
|--------------------------------------------------------------------------
*/
Route::get('/', function () {
    return redirect('/login');
});

/*
|--------------------------------------------------------------------------
| Dashboard según rol
|--------------------------------------------------------------------------
*/
Route::middleware(['auth'])
    ->get('/dashboard', [DashboardController::class, 'index'])
    ->name('dashboard');

/*
|--------------------------------------------------------------------------
| Perfil
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

});

/*
|--------------------------------------------------------------------------
| ADMIN
|--------------------------------------------------------------------------
*/
Route::middleware(['auth','rol:admin'])->group(function () {

    // Empresas
    Route::resource('empresas', EmpresaController::class);

    // Usuarios (ahora incluye crear y guardar)
    Route::resource('usuarios', UserController::class);

    // Reportes
    Route::get('/reportes', [ReporteController::class, 'index'])->name('reportes.index');

});

/*
|--------------------------------------------------------------------------
| EVALUACIONES (admin y evaluador)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth'])->group(function () {

    Route::resource('evaluaciones', EvaluacionController::class);
    Route::get('evaluaciones/{evaluacion}/pdf', [EvaluacionController::class, 'pdf'])->name('evaluaciones.pdf');    

});

/*
|--------------------------------------------------------------------------
| Visitantes
|--------------------------------------------------------------------------
*/
Route::middleware(['auth','rol:visitante'])->group(function () {

    Route::get('/evaluaciones-publicas', [EvaluacionController::class, 'index'])
        ->name('evaluaciones.publicas');
        

});

/*
|--------------------------------------------------------------------------
| Historial personal
|--------------------------------------------------------------------------
*/
Route::get('/mis-evaluaciones', [EvaluacionController::class, 'historial'])
    ->name('evaluaciones.historial')
    ->middleware('auth');

/*
|--------------------------------------------------------------------------
| Prueba rol
|--------------------------------------------------------------------------
*/
Route::get('/prueba-rol', function () {
    return Auth::user()->rol->nombre;
})->middleware('auth');

require __DIR__.'/auth.php';