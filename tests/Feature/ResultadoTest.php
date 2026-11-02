<?php

namespace Tests\Feature;

use App\Enums\EstadoApuesta;
use App\Models\Juego;
use App\Models\Turno;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\CreaCasino;
use Tests\TestCase;

class ResultadoTest extends TestCase
{
    use CreaCasino, RefreshDatabase;

    public function test_rn_12_cierre_procesa_ganadas_y_perdidas_con_premio_y_saldo(): void
    {
        $this->travelTo(now()->setDate(2026, 10, 29)->setTime(9, 0));
        $juego = Juego::where('registro_detallado', true)->firstOrFail();
        $crupier = $this->crearEmpleado('crupier', 'C1', $juego);
        $supervisor = $this->crearEmpleado('supervisor', 'S1', $juego);
        $mesa = $this->crearMesa($juego);
        $asignacion = $this->crearAsignacion($this->crearTurno(), $mesa, $crupier, $supervisor);
        $ronda = $this->crearRonda($asignacion, 1);
        $jugadorGana = $this->crearJugador('J1');
        $jugadorPierde = $this->crearJugador('J2');
        $this->crearApuesta($ronda, $jugadorGana, 'Rojo', '10.00', '1.50');
        $this->crearApuesta($ronda, $jugadorPierde, 'Negro', '10.00', '2.00');
        $this->actingAs($crupier->user)->post(route('rondas.cerrar', $ronda), ['resultado' => 'Rojo', 'tipos_ganadores' => ['Rojo']])->assertRedirect();
        $this->assertSame('1005.00', $jugadorGana->fresh()->saldo);
        $this->assertSame('990.00', $jugadorPierde->fresh()->saldo);
        $this->assertDatabaseHas('apuestas', ['jugador_id' => $jugadorGana->id, 'estado' => 'Ganada', 'premio' => '15.00']);
        $this->assertDatabaseHas('apuestas', ['jugador_id' => $jugadorPierde->id, 'estado' => 'Perdida', 'premio' => '0.00']);
    }

    public function test_rn_12_ronda_cerrada_no_se_vuelve_a_procesar(): void
    {
        $this->travelTo(now()->setDate(2026, 10, 29)->setTime(9, 0));
        $juego = Juego::where('registro_detallado', true)->firstOrFail();
        $crupier = $this->crearEmpleado('crupier', 'C1', $juego);
        $supervisor = $this->crearEmpleado('supervisor', 'S1', $juego);
        $mesa = $this->crearMesa($juego);
        $asignacion = $this->crearAsignacion($this->crearTurno(), $mesa, $crupier, $supervisor);
        $ronda = $this->crearRonda($asignacion, 1);
        $jugador = $this->crearJugador();
        $this->crearApuesta($ronda, $jugador);
        $this->actingAs($crupier->user)->post(route('rondas.cerrar', $ronda), ['resultado' => 'Rojo', 'tipos_ganadores' => ['Rojo']])->assertRedirect();
        $this->post(route('rondas.cerrar', $ronda), ['resultado' => 'Rojo', 'tipos_ganadores' => ['Rojo']])->assertSessionHasErrors('resultado');
        $this->assertSame('1005.00', $jugador->fresh()->saldo);
    }

    public function test_rn_13_poker_rechaza_apuesta_con_ronda_abierta_y_acepta_tras_el_resultado(): void
    {
        $this->travelTo(now()->setDate(2026, 10, 29)->setTime(9, 0));
        $juego = Juego::where('registro_detallado', false)->firstOrFail();
        $crupier = $this->crearEmpleado('crupier', 'C1', $juego);
        $supervisor = $this->crearEmpleado('supervisor', 'S1', $juego);
        $mesa = $this->crearMesa($juego);
        $asignacion = $this->crearAsignacion($this->crearTurno(), $mesa, $crupier, $supervisor);
        $ronda = $this->crearRonda($asignacion, 1);
        $jugador = $this->crearJugador();
        $payload = ['codigo_cliente' => $jugador->codigo_cliente, 'tipo_apuesta' => 'Mano', 'monto' => '10.00', 'factor_pago' => '2.00', 'estado' => 'Ganada'];
        $this->actingAs($crupier->user)->post(route('apuestas.store', $ronda), $payload)->assertSessionHasErrors('ronda');
        $this->post(route('rondas.cerrar', $ronda), ['resultado' => 'Resultado'])->assertRedirect();
        $this->post(route('apuestas.store', $ronda), $payload)->assertRedirect();
        $this->assertSame('1010.00', $jugador->fresh()->saldo);
        $this->assertDatabaseHas('apuestas', ['ronda_id' => $ronda->id, 'estado' => 'Ganada', 'premio' => '20.00']);
    }

    public function test_rn_13_apuesta_de_poker_se_procesa_al_registrarla(): void
    {
        $this->travelTo(now()->setDate(2026, 10, 29)->setTime(9, 0));
        $juego = Juego::where('registro_detallado', false)->firstOrFail();
        $crupier = $this->crearEmpleado('crupier', 'C1', $juego);
        $supervisor = $this->crearEmpleado('supervisor', 'S1', $juego);
        $mesa = $this->crearMesa($juego);
        $asignacion = $this->crearAsignacion($this->crearTurno(), $mesa, $crupier, $supervisor);
        $ronda = $this->crearRonda($asignacion, 1, true);
        $jugador = $this->crearJugador();
        $this->actingAs($crupier->user)->post(route('apuestas.store', $ronda), ['codigo_cliente' => $jugador->codigo_cliente, 'tipo_apuesta' => 'Mano', 'monto' => '10.00', 'factor_pago' => '2.00', 'estado' => 'Perdida'])->assertRedirect();
        $this->assertSame('990.00', $jugador->fresh()->saldo);
        $this->assertSame(EstadoApuesta::Perdida, $ronda->apuestas()->first()->estado);
    }

    public function test_d14_poker_no_registra_apuestas_en_rondas_de_turnos_anteriores(): void
    {
        $this->travelTo(now()->setDate(2026, 10, 29)->setTime(9, 0));
        $juego = Juego::where('registro_detallado', false)->firstOrFail();
        $crupierAnterior = $this->crearEmpleado('crupier', 'C1', $juego);
        $crupier = $this->crearEmpleado('crupier', 'C2', $juego);
        $supervisor = $this->crearEmpleado('supervisor', 'S1', $juego);
        $mesa = $this->crearMesa($juego);
        $turnoAnterior = Turno::create(['fecha' => '2026-10-28', 'hora_inicio' => '08:00', 'hora_fin' => '10:00']);
        $rondaAnterior = $this->crearRonda($this->crearAsignacion($turnoAnterior, $mesa, $crupierAnterior, $supervisor), 1, true);
        $this->crearAsignacion($this->crearTurno(), $mesa, $crupier, $supervisor);
        $jugador = $this->crearJugador();
        $payload = ['codigo_cliente' => $jugador->codigo_cliente, 'tipo_apuesta' => 'Mano', 'monto' => '10.00', 'factor_pago' => '2.00', 'estado' => 'Ganada'];
        $this->actingAs($crupier->user)->post(route('apuestas.store', $rondaAnterior), $payload)->assertForbidden();
        $this->assertDatabaseCount('apuestas', 0);
        $this->assertSame('1000.00', $jugador->fresh()->saldo);
    }
}
