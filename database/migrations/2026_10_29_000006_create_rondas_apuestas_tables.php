<?php

use App\Enums\EstadoApuesta;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rondas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mesa_id')->constrained('mesas')->restrictOnDelete();
            $table->foreignId('asignacion_id')->constrained('asignaciones')->restrictOnDelete();
            $table->unsignedInteger('numero');
            $table->timestamp('hora_inicio');
            $table->timestamp('hora_cierre')->nullable();
            $table->string('resultado')->nullable();
            $table->timestamps();
            $table->unique(['mesa_id', 'numero']);
            $table->index(['mesa_id', 'hora_cierre']);
        });

        Schema::create('apuestas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ronda_id')->constrained('rondas')->restrictOnDelete();
            $table->foreignId('jugador_id')->constrained('jugadores')->restrictOnDelete();
            $table->string('tipo_apuesta');
            $table->decimal('monto', 14, 2);
            $table->decimal('factor_pago', 8, 2);
            $table->enum('estado', array_column(EstadoApuesta::cases(), 'value'));
            $table->decimal('premio', 14, 2)->default(0);
            $table->timestamp('resuelta_en')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('apuestas');
        Schema::dropIfExists('rondas');
    }
};
