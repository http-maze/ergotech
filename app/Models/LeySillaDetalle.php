<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LeySillaDetalle extends Model
{
    use HasFactory;

    protected $table = 'ley_silla_detalles';

    protected $fillable = [
        'ley_silla_evaluacion_id',
        'seccion',
        'concepto',
        'valor',
        'puntaje',
    ];

    public function leySillaEvaluacion()
    {
        return $this->belongsTo(LeySillaEvaluacion::class, 'ley_silla_evaluacion_id');
    }
}