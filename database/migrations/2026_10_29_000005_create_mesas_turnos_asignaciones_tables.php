<?php

use App\Enums\Concepto;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mesas', function (Blueprint $table) {
            $table->id();
            $table->string('codigo')->unique();
            $table->foreignId('juego_id')->constrained('juegos')->restrictOnDelete();
            $table->decimal('apuesta_minima', 14, 2);
            $table->decimal('apuesta_maxima', 14, 2);
            $table->timestamps();
        });

        Schema::create('turnos', function (Blueprint $table) {
            $table->id();
            $table->date('fecha');
            $table->time('hora_inicio');
            $table->time('hora_fin');
            $table->timestamps();
            $table->index(['fecha', 'hora_inicio']);
        });

        Schema::create('asignaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('turno_id')->constrained('turnos')->restrictOnDelete();
            $table->foreignId('mesa_id')->constrained('mesas')->restrictOnDelete();
            $table->foreignId('crupier_id')->constrained('empleados')->restrictOnDelete();
            $table->foreignId('supervisor_id')->constrained('empleados')->restrictOnDelete();
            $table->enum('concepto', array_column(Concepto::cases(), 'value'))->nullable();
            $table->timestamp('concepto_registrado_en')->nullable();
            $table->timestamps();
            $table->unique(['turno_id', 'mesa_id']);
            $table->unique(['turno_id', 'crupier_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asignaciones');
        Schema::dropIfExists('turnos');
        Schema::dropIfExists('mesas');
    }
};
