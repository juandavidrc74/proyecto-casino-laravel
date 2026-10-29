<?php

namespace App\Services;

use App\Models\Asignacion;
use App\Models\Empleado;
use App\Models\Mesa;
use App\Models\Turno;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AsignacionService
{
    public function crear(Turno $turno, array $datos): Asignacion
    {
        return DB::transaction(function () use ($turno, $datos) {
            $mesa = Mesa::with('juego')->findOrFail($datos['mesa_id']);
            $crupier = Empleado::with('user')->findOrFail($datos['crupier_id']);
            $supervisor = Empleado::with('user')->findOrFail($datos['supervisor_id']);
            $errores = [];
            if ($crupier->user->rol->value !== 'crupier') {
                $errores['crupier_id'] = 'El empleado seleccionado no tiene el rol crupier.';
            }
            if ($supervisor->user->rol->value !== 'supervisor') {
                $errores['supervisor_id'] = 'El empleado seleccionado no tiene el rol supervisor.';
            }
            if (Asignacion::where('turno_id', $turno->id)->where('mesa_id', $mesa->id)->exists()) {
                $errores['mesa_id'] = 'La mesa ya tiene una asignación en este turno.';
            }
            if (Asignacion::where('turno_id', $turno->id)->where('crupier_id', $crupier->id)->exists()) {
                $errores['crupier_id'] = 'El crupier ya tiene una mesa en este turno.';
            }
            if (Asignacion::where('mesa_id', $mesa->id)->where('crupier_id', $crupier->id)->whereHas('turno', fn ($q) => $q->where('fecha', $turno->fecha))->exists()) {
                $errores['crupier_id'] = 'El crupier no puede repetir mesa en la misma jornada.';
            }
            if (! $crupier->juegos()->where('juegos.id', $mesa->juego_id)->exists()) {
                $errores['crupier_id'] = 'El crupier no tiene competencia en el juego de esta mesa.';
            }
            if (! $supervisor->juegos()->where('juegos.id', $mesa->juego_id)->exists()) {
                $errores['supervisor_id'] = 'El supervisor no tiene competencia en el juego de esta mesa.';
            }
            if ($errores) {
                throw ValidationException::withMessages($errores);
            }

            return Asignacion::create(['turno_id' => $turno->id, 'mesa_id' => $mesa->id, 'crupier_id' => $crupier->id, 'supervisor_id' => $supervisor->id]);
        });
    }
}
