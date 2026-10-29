<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('juegos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre')->unique();
            $table->boolean('registro_detallado');
            $table->timestamps();
        });

        $ahora = now();
        DB::table('juegos')->insert([
            ['nombre' => 'Ruleta', 'registro_detallado' => true, 'created_at' => $ahora, 'updated_at' => $ahora],
            ['nombre' => 'Blackjack', 'registro_detallado' => true, 'created_at' => $ahora, 'updated_at' => $ahora],
            ['nombre' => 'Póker', 'registro_detallado' => false, 'created_at' => $ahora, 'updated_at' => $ahora],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('juegos');
    }
};
