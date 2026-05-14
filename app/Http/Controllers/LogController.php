<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\File;

class LogController extends Controller
{
    public function index()
    {
        $logPath = storage_path('logs/laravel.log');

        $logs = collect();

        if (File::exists($logPath)) {
            $contenido = File::get($logPath);
            $lineas = explode("\n", $contenido);

            $logs = collect($lineas)
                ->filter(fn($linea) => trim($linea) !== '')
                ->reverse()
                ->take(300)
                ->values();
        }

        return view('logs.index', compact('logs'));
    }
}