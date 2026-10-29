<?php

namespace Tests\Feature\Concerns;

use App\Enums\EstadoApuesta;
use App\Enums\Rol;
use App\Models\Apuesta;
use App\Models\Asignacion;
use App\Models\Empleado;
use App\Models\Juego;
use App\Models\Jugador;
use App\Models\Mesa;
use App\Models\Ronda;
use App\Models\Turno;
use App\Models\User;

trait CreaCasino
{
    protected function crearEmpleado(string $rol, string $documento, Juego $juego): Empleado
    {
        $user = User::factory()->create(['rol' => Rol::from($rol)]);
        $empleado = $user->empleado()->create([
            'documento' => $documento,
            'nombre_completo' => $documento,
            'salario_mensual' => '100.00',
        ]);
        $empleado->juegos()->attach($juego);

        return $empleado;
    }

    protected function crearMesa(
        Juego $juego,
        string $codigo = 'M1',
        string $minimo = '10.00',
        string $maximo = '100.00'
    ): Mesa {
        return Mesa::create([
            'codigo' => $codigo,
            'juego_id' => $juego->id,
            'apuesta_minima' => $minimo,
            'apuesta_maxima' => $maximo,
        ]);
    }

    protected function crearTurno(): Turno
    {
        return Turno::create([
            'fecha' => '2026-10-29',
            'hora_inicio' => '08:00',
            'hora_fin' => '10:00',
        ]);
    }

    protected function crearAsignacion(Turno $turno, Mesa $mesa, Empleado $crupier, Empleado $supervisor): Asignacion
    {
        return Asignacion::create([
            'turno_id' => $turno->id,
            'mesa_id' => $mesa->id,
            'crupier_id' => $crupier->id,
            'supervisor_id' => $supervisor->id,
        ]);
    }

    protected function crearJugador(string $codigo = 'J1', string $saldo = '1000.00'): Jugador
    {
        $user = User::factory()->jugador()->create();

        return $user->jugador()->create([
            'codigo_cliente' => $codigo,
            'documento' => $codigo,
            'nombre_completo' => $codigo,
            'edad' => 30,
            'saldo' => $saldo,
        ]);
    }

    protected function crearRonda(Asignacion $asignacion, int $numero, bool $cerrada = false): Ronda
    {
        return Ronda::create([
            'mesa_id' => $asignacion->mesa_id,
            'asignacion_id' => $asignacion->id,
            'numero' => $numero,
            'hora_inicio' => now(),
            'hora_cierre' => $cerrada ? now() : null,
            'resultado' => $cerrada ? 'Rojo' : null,
        ]);
    }

    protected function crearApuesta(
        Ronda $ronda,
        Jugador $jugador,
        string $tipo = 'Rojo',
        string $monto = '10.00',
        string $factor = '1.50',
        EstadoApuesta $estado = EstadoApuesta::Pendiente
    ): Apuesta {
        $apuesta = $ronda->apuestas()->create([
            'jugador_id' => $jugador->id,
            'tipo_apuesta' => $tipo,
            'monto' => $monto,
            'factor_pago' => $factor,
            'estado' => $estado,
            'premio' => '0.00',
        ]);
        $jugador->refresh();
        $jugador->saldo = bcsub($jugador->saldo, $monto, 2);
        $jugador->save();

        return $apuesta;
    }
}
