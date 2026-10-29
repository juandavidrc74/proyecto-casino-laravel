<?php

namespace App\Models;

use App\Enums\EstadoApuesta;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Apuesta extends Model
{
    protected $fillable = ['ronda_id', 'jugador_id', 'tipo_apuesta', 'monto', 'factor_pago', 'estado', 'premio', 'resuelta_en'];

    protected function casts(): array
    {
        return ['monto' => 'decimal:2', 'factor_pago' => 'decimal:2', 'premio' => 'decimal:2', 'estado' => EstadoApuesta::class, 'resuelta_en' => 'datetime'];
    }

    public function ronda(): BelongsTo
    {
        return $this->belongsTo(Ronda::class);
    }

    public function jugador(): BelongsTo
    {
        return $this->belongsTo(Jugador::class);
    }
}
