<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('competencias', function (Blueprint $table) {
            $table->foreignId('empleado_id')->constrained()->cascadeOnDelete();
            $table->foreignId('juego_id')->constrained()->cascadeOnDelete();
            $table->primary(['empleado_id', 'juego_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('competencias');
    }
};
