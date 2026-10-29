<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Empleado extends Model
{
    protected $fillable = ['user_id', 'documento', 'nombre_completo', 'salario_mensual'];

    protected function casts(): array
    {
        return ['salario_mensual' => 'decimal:2'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function jugador(): HasOne
    {
        return $this->hasOne(Jugador::class, 'user_id', 'user_id');
    }

    public function juegos(): BelongsToMany
    {
        return $this->belongsToMany(Juego::class, 'competencias');
    }

    public function asignacionesComoCrupier(): HasMany
    {
        return $this->hasMany(Asignacion::class, 'crupier_id');
    }

    public function asignacionesComoSupervisor(): HasMany
    {
        return $this->hasMany(Asignacion::class, 'supervisor_id');
    }
}
