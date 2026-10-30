<?php

namespace Tests\Feature;

use App\Models\Juego;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\CreaCasino;
use Tests\TestCase;

class ApuestasTest extends TestCase
{
    use CreaCasino, RefreshDatabase;

    private function rondaAbierta(): array
    {
        $this->travelTo(now()->setDate(2026, 10, 29)->setTime(9, 0));
        $juego = Juego::where('registro_detallado', true)->firstOrFail();
        $crupier = $this->crearEmpleado('crupier', 'C1', $juego);
        $supervisor = $this->crearEmpleado('supervisor', 'S1', $juego);
        $mesa = $this->crearMesa($juego);
        $asignacion = $this->crearAsignacion($this->crearTurno(), $mesa, $crupier, $supervisor);
        $ronda = $this->crearRonda($asignacion, 1);
        $this->actingAs($crupier->user);

        return [$ronda, $this->crearJugador()];
    }

    public function test_rn_10_apuesta_guarda_jugador_ronda_tipo_monto_y_factor(): void
    {
        [$ronda, $jugador] = $this->rondaAbierta();
        $this->post(route('apuestas.store', $ronda), ['codigo_cliente' => $jugador->codigo_cliente, 'tipo_apuesta' => 'Rojo', 'monto' => '10.00', 'factor_pago' => '1.50'])->assertRedirect();
        $this->assertDatabaseHas('apuestas', ['ronda_id' => $ronda->id, 'jugador_id' => $jugador->id, 'tipo_apuesta' => 'Rojo', 'monto' => '10.00', 'factor_pago' => '1.50', 'estado' => 'Pendiente']);
    }

    public function test_rn_11_monto_igual_al_minimo_y_al_maximo_se_acepta(): void
    {
        [$ronda, $jugador] = $this->rondaAbierta();
        foreach (['10.00', '100.00'] as $monto) {
            $this->post(route('apuestas.store', $ronda), ['codigo_cliente' => $jugador->codigo_cliente, 'tipo_apuesta' => 'Rojo', 'monto' => $monto, 'factor_pago' => '1.50'])->assertRedirect();
        }
        $this->assertDatabaseCount('apuestas', 2);
    }

    public function test_rn_11_monto_menor_al_minimo_o_mayor_al_maximo_se_rechaza(): void
    {
        [$ronda, $jugador] = $this->rondaAbierta();
        foreach (['9.99', '100.01'] as $monto) {
            $this->post(route('apuestas.store', $ronda), ['codigo_cliente' => $jugador->codigo_cliente, 'tipo_apuesta' => 'Rojo', 'monto' => $monto, 'factor_pago' => '1.50'])->assertSessionHasErrors('monto');
        }
    }

    public function test_cu_03_apuesta_rechazada_conserva_lo_escrito_en_el_formulario(): void
    {
        [$ronda, $jugador] = $this->rondaAbierta();
        $this->from(route('mi-mesa'))
            ->followingRedirects()
            ->post(route('apuestas.store', $ronda), ['ronda_formulario' => $ronda->id, 'codigo_cliente' => $jugador->codigo_cliente, 'tipo_apuesta' => 'Rojo', 'monto' => '9.99', 'factor_pago' => '1.50'])
            ->assertSee('value="'.$jugador->codigo_cliente.'"', false)
            ->assertSee('value="Rojo"', false)
            ->assertSee('value="9.99"', false)
            ->assertSee('value="1.50"', false);
        $this->assertDatabaseCount('apuestas', 0);
    }

    public function test_rn_11_saldo_insuficiente_se_rechaza(): void
    {
        [$ronda, $jugador] = $this->rondaAbierta();
        $jugador->update(['saldo' => '9.99']);
        $this->post(route('apuestas.store', $ronda), ['codigo_cliente' => $jugador->codigo_cliente, 'tipo_apuesta' => 'Rojo', 'monto' => '10.00', 'factor_pago' => '1.50'])->assertSessionHasErrors('monto');
    }

    public function test_d03_registrar_descuenta_modificar_ajusta_y_anular_devuelve_el_saldo(): void
    {
        [$ronda, $jugador] = $this->rondaAbierta();
        $this->post(route('apuestas.store', $ronda), ['codigo_cliente' => $jugador->codigo_cliente, 'tipo_apuesta' => 'Rojo', 'monto' => '20.00', 'factor_pago' => '1.50']);
        $apuesta = $ronda->apuestas()->firstOrFail();
        $this->assertSame('980.00', $jugador->fresh()->saldo);
        $this->put(route('apuestas.update', $apuesta), ['tipo_apuesta' => 'Negro', 'monto' => '30.00', 'factor_pago' => '2.00'])->assertRedirect();
        $this->assertSame('970.00', $jugador->fresh()->saldo);
        $this->delete(route('apuestas.destroy', $apuesta))->assertRedirect();
        $this->assertSame('1000.00', $jugador->fresh()->saldo);
    }

    public function test_fa_03_no_se_borra_jugador_con_apuestas(): void
    {
        [$ronda, $jugador] = $this->rondaAbierta();
        $this->crearApuesta($ronda, $jugador);
        $this->actingAs(User::factory()->administrador()->create())->from(route('jugadores.index'))->delete(route('jugadores.destroy', $jugador))->assertSessionHasErrors('jugador');
    }

    public function test_rf_rol_02_jugador_y_supervisor_no_operan_rondas_ni_apuestas(): void
    {
        [$ronda, $jugador] = $this->rondaAbierta();
        foreach ([User::factory()->jugador()->create(), User::factory()->supervisor()->create()] as $user) {
            $this->actingAs($user)->get(route('mi-mesa'))->assertForbidden();
            $this->post(route('apuestas.store', $ronda), ['codigo_cliente' => $jugador->codigo_cliente, 'tipo_apuesta' => 'Rojo', 'monto' => '10.00', 'factor_pago' => '1.50'])->assertForbidden();
        }
    }
}
