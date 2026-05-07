<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ley_silla_detalles', function (Blueprint $table) {
            $table->id();

            $table->foreignId('ley_silla_evaluacion_id')
                ->constrained('ley_silla_evaluaciones')
                ->onDelete('cascade');

            $table->string('seccion');
            $table->string('concepto');
            $table->string('valor')->nullable();
            $table->integer('puntaje')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ley_silla_detalles');
    }
};