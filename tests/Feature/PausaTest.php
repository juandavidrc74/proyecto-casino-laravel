<?php

namespace Tests\Feature;

use App\Enums\MotivoSuspension;
use App\Models\Juego;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\CreaCasino;
use Tests\TestCase;

class PausaTest extends TestCase
{
    use CreaCasino, RefreshDatabase;

    public function test_rn_15_pausa_voluntaria_suspende_24_horas(): void
    {
        $this->travelTo(now()->setDate(2026, 10, 29)->setTime(12, 0));
        $jugador = $this->crearJugador();
        $this->actingAs($jugador->user)->post(route('mi-cuenta.pausa'))->assertRedirect(route('mi-cuenta'));
        $this->assertDatabaseHas('suspensiones', ['jugador_id' => $jugador->id, 'motivo' => 'pausa_voluntaria', 'fin' => '2026-10-30 12:00:00']);
    }

    public function test_rn_15_no_pide_pausa_con_suspension_activa(): void
    {
        $jugador = $this->crearJugador();
        $jugador->suspensiones()->create(['motivo' => MotivoSuspension::Investigacion, 'inicio' => now()->subMinute()]);
        $this->actingAs($jugador->user)->post(route('mi-cuenta.pausa'))->assertSessionHasErrors('pausa');
        $this->assertDatabaseCount('suspensiones', 1);
    }

    public function test_rn_15_solo_sobre_su_propio_perfil(): void
    {
        $jugador = $this->crearJugador();
        $otro = $this->crearJugador('J2');
        $this->actingAs($jugador->user)->get(route('mi-cuenta'))->assertOk()->assertSee($jugador->codigo_cliente)->assertDontSee($otro->codigo_cliente);
        $usuarioSinPerfil = User::factory()->create();
        $this->actingAs($usuarioSinPerfil)->get(route('mi-cuenta'))->assertForbidden();
        $this->actingAs($usuarioSinPerfil)->post(route('mi-cuenta.pausa'))->assertForbidden();
    }

    public function test_mi_cuenta_muestra_perfil_de_jugador_vinculado_a_empleado(): void
    {
        $empleado = $this->crearEmpleado('crupier', 'E1', Juego::firstOrFail());
        $jugador = $empleado->user->jugador()->create(['codigo_cliente' => 'EMP1', 'documento' => 'E1', 'nombre_completo' => 'Empleado', 'edad' => 30, 'saldo' => '100.00']);
        $this->actingAs($empleado->user)->get(route('mi-cuenta'))->assertOk()->assertSee('100.00');
    }
}
