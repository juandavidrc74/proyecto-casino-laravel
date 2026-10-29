<?php

namespace App\Http\Controllers;

use App\Models\Asignacion;

class MiMesaController extends Controller
{
    public function __invoke()
    {
        $empleado = auth()->user()->empleado;
        $asignacion = $empleado ? Asignacion::enCursoDelCrupier($empleado) : null;
        $rondas = $asignacion
            ? $asignacion->mesa->rondas()
                ->with('apuestas.jugador')
                ->where(function ($query) use ($asignacion) {
                    $query->where('asignacion_id', $asignacion->id)
                        ->orWhereNull('hora_cierre');
                })
                ->orderByDesc('numero')
                ->get()
            : collect();
        $rondaAbierta = $rondas->first(fn ($r) => $r->hora_cierre === null);

        return view('mi-mesa.index', compact('asignacion', 'rondaAbierta', 'rondas'));
    }
}
