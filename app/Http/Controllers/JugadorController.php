<?php

namespace App\Http\Controllers;

use App\Enums\EstadoApuesta;
use App\Enums\Rol;
use App\Http\Requests\JugadorStoreRequest;
use App\Http\Requests\JugadorUpdateRequest;
use App\Models\Empleado;
use App\Models\Jugador;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class JugadorController extends Controller
{
    public function index()
    {
        $jugadores = Jugador::with(['user.empleado'])->orderBy('codigo_cliente')->get();

        return view('jugadores.index', compact('jugadores'));
    }

    public function show(Jugador $jugador)
    {
        $jugador->load('user.empleado');
        $apuestasResueltas = $jugador->apuestas()->whereIn('estado', [EstadoApuesta::Ganada->value, EstadoApuesta::Perdida->value])->get(['monto', 'premio']);
        $apostado = $apuestasResueltas->reduce(fn (string $total, $apuesta) => bcadd($total, $apuesta->monto, 2), '0.00');
        $premios = $apuestasResueltas->reduce(fn (string $total, $apuesta) => bcadd($total, $apuesta->premio, 2), '0.00');
        $totales = ['resueltas' => $apuestasResueltas->count(), 'apostado' => $apostado, 'premios' => $premios, 'neto' => bcsub($premios, $apostado, 2)];
        $apuestas = $jugador->apuestas()->with(['ronda.mesa.juego'])->orderByDesc('created_at')->orderByDesc('id')->simplePaginate(20)->withQueryString();
        $suspensiones = $jugador->suspensiones()->orderByDesc('inicio')->get();
        $abonos = $jugador->user->empleado ? $jugador->abonos()->latest()->get() : collect();

        return view('jugadores.show', compact('jugador', 'totales', 'apuestas', 'suspensiones', 'abonos'));
    }

    public function create()
    {
        $jugador = new Jugador;
        $empleado = request()->query('empleado') ? Empleado::findOrFail(request()->query('empleado')) : null;

        return view('jugadores.create', compact('jugador', 'empleado'));
    }

    public function store(JugadorStoreRequest $request)
    {
        $datos = $request->validated();
        DB::transaction(function () use ($datos) {
            if (! empty($datos['empleado_id'])) {
                $empleado = Empleado::findOrFail($datos['empleado_id']);
                $empleado->jugador()->create([
                    'codigo_cliente' => $datos['codigo_cliente'], 'documento' => $empleado->documento,
                    'nombre_completo' => $empleado->nombre_completo, 'edad' => $datos['edad'], 'saldo' => $datos['saldo'],
                ]);

                return;
            }
            $user = User::create(['username' => $datos['username'], 'password' => $datos['password_hash'], 'rol' => Rol::Jugador]);
            $user->jugador()->create([
                'codigo_cliente' => $datos['codigo_cliente'], 'documento' => $datos['documento'], 'nombre_completo' => $datos['nombre_completo'],
                'edad' => $datos['edad'], 'saldo' => $datos['saldo'],
            ]);
        });

        return redirect()->route('jugadores.index')->with('status', 'Jugador registrado correctamente.');
    }

    public function edit(Jugador $jugador)
    {
        $jugador->load('user.empleado');

        return view('jugadores.edit', compact('jugador'));
    }

    public function update(JugadorUpdateRequest $request, Jugador $jugador)
    {
        $datos = $request->validated();
        DB::transaction(function () use ($datos, $jugador) {
            $cambios = ['codigo_cliente' => $datos['codigo_cliente'], 'edad' => $datos['edad']];
            $esEmpleado = $jugador->user->empleado()->exists();
            if (! $esEmpleado) {
                $cambios['documento'] = $datos['documento'];
                $cambios['nombre_completo'] = $datos['nombre_completo'];
            }
            $jugador->update($cambios);
            if (! $esEmpleado) {
                $usuario = ['username' => $datos['username']];
                if (! empty($datos['password_hash'])) {
                    $usuario['password'] = $datos['password_hash'];
                }
                $jugador->user->update($usuario);
            }
        });

        return redirect()->route('jugadores.index')->with('status', 'Jugador actualizado correctamente.');
    }

    public function destroy(Jugador $jugador)
    {
        DB::transaction(function () use ($jugador) {
            $jugador = Jugador::whereKey($jugador->id)->lockForUpdate()->firstOrFail();
            if ($jugador->apuestas()->exists()) {
                throw ValidationException::withMessages(['jugador' => 'No se puede borrar un jugador que tiene apuestas registradas.']);
            }
            $usuario = $jugador->user;
            $esJugadorIndependiente = $usuario->tieneRol(Rol::Jugador);
            $jugador->delete();
            if ($esJugadorIndependiente) {
                $usuario->delete();
            }
        });

        return redirect()->route('jugadores.index')->with('status', 'Perfil de jugador borrado correctamente.');
    }
}
