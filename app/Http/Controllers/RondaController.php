<?php

namespace App\Http\Controllers;

use App\Http\Requests\RondaCerrarRequest;
use App\Models\Asignacion;
use App\Models\Mesa;
use App\Models\Ronda;
use App\Services\ResultadoService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RondaController extends Controller
{
    public function store(Mesa $mesa)
    {
        $asignacion = $this->asignacionActual($mesa);
        $ronda = DB::transaction(function () use ($mesa, $asignacion) {
            $mesa = Mesa::lockForUpdate()->findOrFail($mesa->id);
            if ($mesa->rondas()->whereNull('hora_cierre')->exists()) {
                throw ValidationException::withMessages(['ronda' => 'La mesa ya tiene una ronda abierta.']);
            }
            $numero = (int) $mesa->rondas()->max('numero') + 1;

            return $mesa->rondas()->create([
                'asignacion_id' => $asignacion->id,
                'numero' => $numero,
                'hora_inicio' => now(config('app.timezone')),
            ]);
        });

        return redirect()->route('mi-mesa')->with('status', 'Ronda abierta correctamente.');
    }

    public function destroy(Ronda $ronda)
    {
        $this->asignacionActual($ronda->mesa);
        DB::transaction(function () use ($ronda) {
            $ronda = Ronda::lockForUpdate()->findOrFail($ronda->id);
            if ($ronda->hora_cierre !== null || $ronda->apuestas()->exists()) {
                throw ValidationException::withMessages(['ronda' => 'Solo se anula una ronda abierta sin apuestas.']);
            }
            $ronda->delete();
        });

        return redirect()->route('mi-mesa')->with('status', 'Ronda anulada correctamente.');
    }

    public function cerrar(RondaCerrarRequest $request, Ronda $ronda, ResultadoService $service)
    {
        $this->asignacionActual($ronda->mesa);
        $service->cerrar($ronda, $request->validated('resultado'), $request->validated('tipos_ganadores', []));

        return redirect()->route('mi-mesa')->with('status', 'Ronda cerrada correctamente.');
    }

    private function asignacionActual(Mesa $mesa)
    {
        $empleado = auth()->user()->empleado;
        abort_unless($empleado, 403);
        $asignacion = Asignacion::enCursoDelCrupier($empleado, $mesa);
        abort_unless($asignacion, 403);

        return $asignacion;
    }
}
