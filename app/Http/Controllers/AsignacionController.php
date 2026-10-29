<?php

namespace App\Http\Controllers;

use App\Http\Requests\AsignacionStoreRequest;
use App\Models\Asignacion;
use App\Models\Turno;
use App\Services\AsignacionService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AsignacionController extends Controller
{
    public function store(AsignacionStoreRequest $request, Turno $turno, AsignacionService $service)
    {
        $service->crear($turno, $request->validated());

        return redirect()->route('turnos.show', $turno)->with('status', 'Asignación registrada correctamente.');
    }

    public function destroy(Turno $turno, Asignacion $asignacion)
    {
        abort_unless((int) $asignacion->turno_id === (int) $turno->id, 404);
        DB::transaction(function () use ($asignacion) {
            $asignacion = Asignacion::lockForUpdate()->findOrFail($asignacion->id);
            if ($asignacion->rondas()->exists()) {
                throw ValidationException::withMessages(['asignacion' => 'No se puede borrar una asignación que tiene rondas registradas.']);
            }
            $asignacion->delete();
        });

        return redirect()->route('turnos.show', $turno)->with('status', 'Asignación borrada correctamente.');
    }
}
