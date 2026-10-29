<?php

namespace App\Http\Controllers;

use App\Http\Requests\MesaRequest;
use App\Models\Juego;
use App\Models\Mesa;

class MesaController extends Controller
{
    public function index()
    {
        $mesas = Mesa::with('juego')->orderBy('codigo')->get();

        return view('mesas.index', compact('mesas'));
    }

    public function create()
    {
        $mesa = new Mesa;
        $juegos = Juego::orderBy('nombre')->get();

        return view('mesas.create', compact('mesa', 'juegos'));
    }

    public function store(MesaRequest $request)
    {
        Mesa::create($request->validated());

        return redirect()->route('mesas.index')->with('status', 'Mesa registrada correctamente.');
    }

    public function edit(Mesa $mesa)
    {
        $juegos = Juego::orderBy('nombre')->get();

        return view('mesas.edit', compact('mesa', 'juegos'));
    }

    public function update(MesaRequest $request, Mesa $mesa)
    {
        $mesa->update($request->validated());

        return redirect()->route('mesas.index')->with('status', 'Mesa actualizada correctamente.');
    }

    public function destroy(Mesa $mesa)
    {
        if ($mesa->asignaciones()->exists()) {
            return back()->withErrors(['mesa' => 'No se puede borrar una mesa con asignaciones registradas.']);
        } $mesa->delete();

        return redirect()->route('mesas.index')->with('status', 'Mesa borrada correctamente.');
    }
}
