<?php

namespace App\Http\Controllers;

use App\Http\Requests\TurnoRequest;
use App\Models\Empleado;
use App\Models\Mesa;
use App\Models\Turno;

class TurnoController extends Controller
{
    public function index()
    {
        $turnos = Turno::orderByDesc('fecha')->orderBy('hora_inicio')->get();

        return view('turnos.index', compact('turnos'));
    }

    public function create()
    {
        $turno = new Turno;

        return view('turnos.create', compact('turno'));
    }

    public function store(TurnoRequest $request)
    {
        Turno::create($request->validated());

        return redirect()->route('turnos.index')->with('status', 'Turno registrado correctamente.');
    }

    public function show(Turno $turno)
    {
        $turno->load('asignaciones.mesa.juego', 'asignaciones.crupier.user', 'asignaciones.supervisor.user');
        $mesas = Mesa::with('juego')->orderBy('codigo')->get();
        $crupiers = Empleado::whereHas('user', fn ($q) => $q->where('rol', 'crupier'))->orderBy('nombre_completo')->get();
        $supervisores = Empleado::whereHas('user', fn ($q) => $q->where('rol', 'supervisor'))->orderBy('nombre_completo')->get();

        return view('turnos.show', compact('turno', 'mesas', 'crupiers', 'supervisores'));
    }

    public function edit(Turno $turno)
    {
        if ($turno->asignaciones()->exists()) {
            return back()->withErrors(['turno' => 'No se puede editar un turno con asignaciones.']);
        }

return view('turnos.edit', compact('turno'));
    }

    public function update(TurnoRequest $request, Turno $turno)
    {
        $turno->update($request->validated());

        return redirect()->route('turnos.show', $turno)->with('status', 'Turno actualizado correctamente.');
    }

    public function destroy(Turno $turno)
    {
        if ($turno->asignaciones()->exists()) {
            return back()->withErrors(['turno' => 'No se puede borrar un turno con asignaciones.']);
        } $turno->delete();

        return redirect()->route('turnos.index')->with('status', 'Turno borrado correctamente.');
    }
}
