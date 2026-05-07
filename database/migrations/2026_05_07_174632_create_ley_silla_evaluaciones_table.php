<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ley_silla_evaluaciones', function (Blueprint $table) {
            $table->id();

            $table->foreignId('evaluacion_id')
                ->constrained('evaluaciones')
                ->onDelete('cascade');

            $table->string('tipo_puesto')->nullable();
            $table->decimal('horas_de_pie', 5, 2)->nullable();
            $table->boolean('bipedestacion_prolongada')->default(false);

            $table->boolean('cuenta_con_silla')->default(false);
            $table->boolean('silla_con_respaldo')->default(false);
            $table->boolean('silla_en_area_cercana')->default(false);
            $table->boolean('sillas_suficientes')->default(false);
            $table->boolean('silla_en_buen_estado')->default(false);

            $table->boolean('permite_sentarse')->default(false);
            $table->boolean('permite_pausas')->default(false);
            $table->boolean('pausas_definidas')->default(false);
            $table->boolean('alternancia_postural')->default(false);

            $table->boolean('reglamento_actualizado')->default(false);
            $table->boolean('evidencia_documental')->default(false);
            $table->boolean('capacitacion_trabajadores')->default(false);

            $table->integer('puntaje_total')->default(0);
            $table->string('resultado_cumplimiento')->nullable();
            $table->string('nivel_riesgo')->nullable();
            $table->text('recomendaciones')->nullable();
            $table->text('observaciones')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ley_silla_evaluaciones');
    }
};