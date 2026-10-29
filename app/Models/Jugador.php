<?php

namespace App\Models;

use App\Enums\MotivoSuspension;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Jugador extends Model
{
    protected $table = 'jugadores';

    protected $fillable = ['user_id', 'codigo_cliente', 'documento', 'nombre_completo', 'edad', 'saldo'];

    protected function casts(): array
    {
        return ['edad' => 'integer', 'saldo' => 'decimal:2'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function apuestas(): HasMany
    {
        return $this->hasMany(Apuesta::class);
    }

    public function suspensiones(): HasMany
    {
        return $this->hasMany(Suspension::class);
    }

    public function abonos(): HasMany
    {
        return $this->hasMany(Abono::class);
    }

    public function estado(): string
    {
        $ahora = now(config('app.timezone'));
        $activas = $this->suspensiones()->where('inicio', '<=', $ahora)
            ->where(function ($query) use ($ahora) {
                $query->whereNull('fin')->orWhere('fin', '>', $ahora);
            });

        if ((clone $activas)->where('motivo', MotivoSuspension::Investigacion->value)->exists()) {
            return 'Investigación';
        }

        return (clone $activas)->exists() ? 'Suspendido' : 'Activo';
    }
}
