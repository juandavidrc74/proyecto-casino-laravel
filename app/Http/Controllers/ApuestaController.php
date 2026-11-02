<?php

namespace App\Http\Controllers;

use App\Enums\EstadoApuesta;
use App\Http\Requests\ApuestaRequest;
use App\Models\Apuesta;
use App\Models\Asignacion;
use App\Models\Ronda;
use App\Services\ApuestaService;
use Illuminate\Validation\ValidationException;

class ApuestaController extends Controller
{
    public function store(ApuestaRequest $request, Ronda $ronda, ApuestaService $service)
    {
        $this->autorizarRonda($ronda);
        $datos = $request->validated();
        if (! $ronda->mesa->juego->registro_detallado && ! isset($datos['estado'])) {
            throw ValidationException::withMessages(['estado' => 'Indica si la apuesta fue Ganada o Perdida.']);
        }
        $service->registrar($ronda, $datos);

        return redirect()->route('mi-mesa')->with('status', 'Apuesta registrada correctamente.');
    }

    public function edit(Apuesta $apuesta)
    {
        $this->autorizarRonda($apuesta->ronda);
        abort_unless(
            $apuesta->estado === EstadoApuesta::Pendiente && $apuesta->ronda->hora_cierre === null,
            404
        );

        return view('apuestas.edit', compact('apuesta'));
    }

    public function update(ApuestaRequest $request, Apuesta $apuesta, ApuestaService $service)
    {
        $this->autorizarRonda($apuesta->ronda);
        $service->modificar($apuesta, $request->validated());

        return redirect()->route('mi-mesa')->with('status', 'Apuesta modificada correctamente.');
    }

    public function destroy(Apuesta $apuesta, ApuestaService $service)
    {
        $this->autorizarRonda($apuesta->ronda);
        $service->anular($apuesta);

        return redirect()->route('mi-mesa')->with('status', 'Apuesta anulada correctamente.');
    }

    private function autorizarRonda(Ronda $ronda): void
    {
        $empleado = auth()->user()->empleado;
        abort_unless($empleado, 403);
        $asignacion = Asignacion::enCursoDelCrupier($empleado, $ronda->mesa);
        abort_unless($asignacion, 403);
        // D-14: una ronda abierta puede venir del turno anterior; una cerrada (póker) debe ser de este turno.
        abort_unless($ronda->hora_cierre === null || (int) $ronda->asignacion_id === (int) $asignacion->id, 403);
    }
}
