<?php

use App\Enums\MotivoSuspension;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('suspensiones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('jugador_id')->constrained('jugadores')->cascadeOnDelete();
            $table->enum('motivo', array_column(MotivoSuspension::cases(), 'value'));
            $table->timestamp('inicio');
            $table->timestamp('fin')->nullable();
            $table->timestamps();
            $table->index(['jugador_id', 'motivo', 'inicio', 'fin']);
        });

        Schema::create('abonos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('jugador_id')->constrained('jugadores')->cascadeOnDelete();
            $table->decimal('monto', 14, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('abonos');
        Schema::dropIfExists('suspensiones');
    }
};
