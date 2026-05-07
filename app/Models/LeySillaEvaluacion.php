<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LeySillaEvaluacion extends Model
{
    use HasFactory;

    protected $table = 'ley_silla_evaluaciones';

    protected $fillable = [
        'evaluacion_id',
        'tipo_puesto',
        'horas_de_pie',
        'bipedestacion_prolongada',
        'cuenta_con_silla',
        'silla_con_respaldo',
        'silla_en_area_cercana',
        'sillas_suficientes',
        'silla_en_buen_estado',
        'permite_sentarse',
        'permite_pausas',
        'pausas_definidas',
        'alternancia_postural',
        'reglamento_actualizado',
        'evidencia_documental',
        'capacitacion_trabajadores',
        'puntaje_total',
        'resultado_cumplimiento',
        'nivel_riesgo',
        'recomendaciones',
        'observaciones',
    ];

    protected $casts = [
        'bipedestacion_prolongada' => 'boolean',
        'cuenta_con_silla' => 'boolean',
        'silla_con_respaldo' => 'boolean',
        'silla_en_area_cercana' => 'boolean',
        'sillas_suficientes' => 'boolean',
        'silla_en_buen_estado' => 'boolean',
        'permite_sentarse' => 'boolean',
        'permite_pausas' => 'boolean',
        'pausas_definidas' => 'boolean',
        'alternancia_postural' => 'boolean',
        'reglamento_actualizado' => 'boolean',
        'evidencia_documental' => 'boolean',
        'capacitacion_trabajadores' => 'boolean',
        'horas_de_pie' => 'float',
    ];

    public function evaluacion()
    {
        return $this->belongsTo(Evaluacion::class, 'evaluacion_id');
    }

    public function detalles()
    {
        return $this->hasMany(LeySillaDetalle::class, 'ley_silla_evaluacion_id');
    }
}