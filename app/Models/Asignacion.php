<?php

namespace App\Models;

use App\Enums\Concepto;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Asignacion extends Model
{
    protected $table = 'asignaciones';

    protected $fillable = ['turno_id', 'mesa_id', 'crupier_id', 'supervisor_id', 'concepto', 'concepto_registrado_en'];

    protected function casts(): array
    {
        return ['concepto' => Concepto::class, 'concepto_registrado_en' => 'datetime'];
    }

    public static function enCursoDelCrupier(Empleado $crupier, ?Mesa $mesa = null): ?self
    {
        $ahora = now(config('app.timezone'));
        $asignaciones = static::with('turno')
            ->where('crupier_id', $crupier->id)
            ->when($mesa, fn ($query) => $query->where('mesa_id', $mesa->id))
            ->whereHas('turno', fn ($query) => $query->whereDate('fecha', $ahora->toDateString()))
            ->get();

        return $asignaciones->first(
            fn (self $asignacion) => $asignacion->turno->inicio()->lessThanOrEqualTo($ahora)
                && $asignacion->turno->fin()->greaterThan($ahora)
        );
    }

    public function turno(): BelongsTo
    {
        return $this->belongsTo(Turno::class);
    }

    public function mesa(): BelongsTo
    {
        return $this->belongsTo(Mesa::class);
    }

    public function crupier(): BelongsTo
    {
        return $this->belongsTo(Empleado::class, 'crupier_id');
    }

    public function supervisor(): BelongsTo
    {
        return $this->belongsTo(Empleado::class, 'supervisor_id');
    }

    public function rondas(): HasMany
    {
        return $this->hasMany(Ronda::class);
    }
}
