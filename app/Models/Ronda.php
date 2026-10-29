<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ronda extends Model
{
    protected $fillable = ['mesa_id', 'asignacion_id', 'numero', 'hora_inicio', 'hora_cierre', 'resultado'];

    protected function casts(): array
    {
        return ['hora_inicio' => 'datetime', 'hora_cierre' => 'datetime'];
    }

    public function mesa(): BelongsTo
    {
        return $this->belongsTo(Mesa::class);
    }

    public function asignacion(): BelongsTo
    {
        return $this->belongsTo(Asignacion::class);
    }

    public function apuestas(): HasMany
    {
        return $this->hasMany(Apuesta::class);
    }
}
