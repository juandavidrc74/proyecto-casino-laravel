<?php

namespace App\Http\Controllers;

use App\Enums\Concepto;
use App\Http\Requests\ConceptoStoreRequest;
use App\Models\Asignacion;
use App\Models\User;
use App\Notifications\ConceptoSospechaFraude;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ConceptoController extends Controller
{
    public function index()
    {
        $empleado = auth()->user()->empleado;
        abort_unless($empleado, 403);
        $asignaciones = Asignacion::with(['turno', 'mesa', 'crupier'])->where('supervisor_id', $empleado->id)->join('turnos', 'asignaciones.turno_id', '=', 'turnos.id')->orderByDesc('turnos.fecha')->orderByDesc('turnos.hora_inicio')->select('asignaciones.*')->get();

        return view('conceptos.index', compact('asignaciones'));
    }

    public function store(ConceptoStoreRequest $request, Asignacion $asignacion)
    {
        $empleado = auth()->user()->empleado;
        abort_unless($empleado && (int) $asignacion->supervisor_id === (int) $empleado->id, 403);
        DB::transaction(function () use ($request, $asignacion) {
            $fila = Asignacion::whereKey($asignacion->id)->lockForUpdate()->firstOrFail();
            if ($fila->concepto !== null) {
                throw ValidationException::withMessages(['concepto' => 'El concepto ya fue registrado.']);
            }
            if (! $fila->turno->haTerminado()) {
                throw ValidationException::withMessages(['concepto' => 'Solo se puede registrar el concepto después de terminar el turno.']);
            }
            $fila->update(['concepto' => Concepto::from($request->validated('concepto')), 'concepto_registrado_en' => now(config('app.timezone'))]);
            if ($fila->concepto === Concepto::SospechaFraude) {
                $fila->load(['mesa', 'turno', 'crupier', 'supervisor']);
                $datos = [
                    'mensaje' => 'Se registró una sospecha de fraude en la mesa '.$fila->mesa->codigo.'.',
                    'mesa' => $fila->mesa->codigo,
                    'fecha' => $fila->turno->fecha,
                    'turno' => $fila->turno->hora_inicio.'–'.$fila->turno->hora_fin,
                    'crupier' => $fila->crupier->nombre_completo,
                    'supervisor' => $fila->supervisor->nombre_completo,
                ];
                DB::afterCommit(function () use ($datos) {
                    foreach (User::where('rol', 'administrador')->get() as $administrador) {
                        $administrador->notify(new ConceptoSospechaFraude($datos));
                    }
                });
            }
        });

        return redirect()->route('conceptos.index')->with('status', 'Concepto registrado correctamente.');
    }
}
