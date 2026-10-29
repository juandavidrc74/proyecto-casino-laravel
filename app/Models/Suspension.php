<?php

namespace App\Models;

use App\Enums\MotivoSuspension;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Suspension extends Model
{
    protected $table = 'suspensiones';

    protected $fillable = ['jugador_id', 'motivo', 'inicio', 'fin'];

    protected function casts(): array
    {
        return ['motivo' => MotivoSuspension::class, 'inicio' => 'datetime', 'fin' => 'datetime'];
    }

    public function jugador(): BelongsTo
    {
        return $this->belongsTo(Jugador::class);
    }
}
