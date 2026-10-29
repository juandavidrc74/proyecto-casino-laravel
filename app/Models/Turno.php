<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Turno extends Model
{
    protected $fillable = ['fecha', 'hora_inicio', 'hora_fin'];

    public function asignaciones(): HasMany
    {
        return $this->hasMany(Asignacion::class);
    }

    public function inicio(): Carbon
    {
        return Carbon::parse($this->fecha.' '.$this->hora_inicio, config('app.timezone'));
    }

    public function fin(): Carbon
    {
        return Carbon::parse($this->fecha.' '.$this->hora_fin, config('app.timezone'));
    }

    public function haTerminado(): bool
    {
        return now(config('app.timezone'))->greaterThanOrEqualTo($this->fin());
    }
}
