<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Mesa extends Model
{
    protected $fillable = ['codigo', 'juego_id', 'apuesta_minima', 'apuesta_maxima'];

    protected function casts(): array
    {
        return ['apuesta_minima' => 'decimal:2', 'apuesta_maxima' => 'decimal:2'];
    }

    public function juego(): BelongsTo
    {
        return $this->belongsTo(Juego::class);
    }

    public function asignaciones(): HasMany
    {
        return $this->hasMany(Asignacion::class);
    }

    public function rondas(): HasMany
    {
        return $this->hasMany(Ronda::class);
    }
}
