<?php

namespace App\Http\Controllers;

use App\Http\Requests\EmpleadoStoreRequest;
use App\Http\Requests\EmpleadoUpdateRequest;
use App\Models\Empleado;
use App\Models\Juego;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class EmpleadoController extends Controller
{
    public function index()
    {
        $empleados = Empleado::with(['user', 'juegos', 'jugador'])->orderBy('documento')->get();

        return view('empleados.index', compact('empleados'));
    }

    public function create()
    {
        $empleado = new Empleado;
        $juegos = Juego::orderBy('nombre')->get();

        return view('empleados.create', compact('empleado', 'juegos'));
    }

    public function store(EmpleadoStoreRequest $request)
    {
        $datos = $request->validated();
        DB::transaction(function () use ($datos) {
            $user = User::create(['username' => $datos['username'], 'password' => $datos['password_hash'], 'rol' => $datos['rol']]);
            $empleado = $user->empleado()->create([
                'documento' => $datos['documento'], 'nombre_completo' => $datos['nombre_completo'], 'salario_mensual' => $datos['salario_mensual'],
            ]);
            $empleado->juegos()->sync($datos['competencias'] ?? []);
        });

        return redirect()->route('empleados.index')->with('status', 'Empleado registrado correctamente.');
    }

    public function edit(Empleado $empleado)
    {
        $empleado->load(['user', 'juegos']);
        $juegos = Juego::orderBy('nombre')->get();

        return view('empleados.edit', compact('empleado', 'juegos'));
    }

    public function update(EmpleadoUpdateRequest $request, Empleado $empleado)
    {
        $datos = $request->validated();
        DB::transaction(function () use ($datos, $empleado) {
            $empleado->update([
                'documento' => $datos['documento'], 'nombre_completo' => $datos['nombre_completo'], 'salario_mensual' => $datos['salario_mensual'],
            ]);
            if ($empleado->jugador()->exists()) {
                $empleado->jugador()->update(['documento' => $datos['documento'], 'nombre_completo' => $datos['nombre_completo']]);
            }
            $usuario = ['username' => $datos['username'], 'rol' => $datos['rol']];
            if (! empty($datos['password_hash'])) {
                $usuario['password'] = $datos['password_hash'];
            }
            $empleado->user->update($usuario);
            $empleado->juegos()->sync($datos['competencias'] ?? []);
        });

        return redirect()->route('empleados.index')->with('status', 'Empleado actualizado correctamente.');
    }

    public function destroy(Empleado $empleado)
    {
        if ((int) $empleado->user_id === (int) auth()->id()) {
            return back()->withErrors(['empleado' => 'No puedes borrar el empleado de la sesión abierta.']);
        }
        if ($empleado->jugador()->exists()) {
            return back()->withErrors(['empleado' => 'Borra antes el perfil de jugador.']);
        }
        if ($empleado->asignacionesComoCrupier()->exists() || $empleado->asignacionesComoSupervisor()->exists()) {
            return back()->withErrors(['empleado' => 'No se puede borrar el empleado porque tiene asignaciones registradas.']);
        }
        DB::transaction(function () use ($empleado) {
            $usuario = $empleado->user;
            $empleado->juegos()->detach();
            $empleado->delete();
            $usuario->delete();
        });

        return redirect()->route('empleados.index')->with('status', 'Empleado y cuenta borrados correctamente.');
    }
}
