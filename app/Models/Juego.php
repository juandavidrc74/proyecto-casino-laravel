<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Juego extends Model
{
    protected $fillable = ['nombre', 'registro_detallado'];

    protected function casts(): array
    {
        return ['registro_detallado' => 'boolean'];
    }

    public function empleados(): BelongsToMany
    {
        return $this->belongsToMany(Empleado::class, 'competencias');
    }

    public function mesas(): HasMany
    {
        return $this->hasMany(Mesa::class);
    }
}
