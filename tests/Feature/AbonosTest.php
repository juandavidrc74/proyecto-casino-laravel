<?php

namespace Tests\Feature;

use App\Enums\EstadoApuesta;
use App\Enums\MotivoSuspension;
use App\Models\Juego;
use App\Models\User;
use App\Services\AutorizacionJugadorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\Feature\Concerns\CreaCasino;
use Tests\TestCase;

class AbonosTest extends TestCase
{
    use CreaCasino, RefreshDatabase;

    public function test_rn_17_empleado_con_deuda_mayor_a_dos_salarios_queda_suspendido(): void
    {
        $jugador = $this->empleadoJugador();
        $this->registrarNeto($jugador, EstadoApuesta::Perdida, '200.01', '0.00');
        app(AutorizacionJugadorService::class)->evaluarTrasApuesta($jugador);
        $this->assertDatabaseHas('suspensiones', ['jugador_id' => $jugador->id, 'motivo' => 'perdida_empleado', 'fin' => null]);
    }

    public function test_rn_17_abono_que_baja_la_deuda_reactiva_automaticamente(): void
    {
        $jugador = $this->empleadoJugador();
        $this->registrarNeto($jugador, EstadoApuesta::Perdida, '250.00', '0.00');
        app(AutorizacionJugadorService::class)->evaluarTrasApuesta($jugador);
        app(AutorizacionJugadorService::class)->registrarAbono($jugador, '50.00');
        $this->assertNotNull($jugador->suspensiones()->where('motivo', MotivoSuspension::PerdidaEmpleado->value)->first()->fresh()->fin);
    }

    public function test_rn_17_abono_que_no_baja_del_limite_mantiene_la_suspension(): void
    {
        $jugador = $this->empleadoJugador();
        $this->registrarNeto($jugador, EstadoApuesta::Perdida, '250.00', '0.00');
        app(AutorizacionJugadorService::class)->evaluarTrasApuesta($jugador);
        app(AutorizacionJugadorService::class)->registrarAbono($jugador, '20.00');
        $this->assertNull($jugador->suspensiones()->where('motivo', MotivoSuspension::PerdidaEmpleado->value)->first()->fresh()->fin);
    }

    public function test_d07_abono_mayor_que_la_deuda_o_a_no_empleado_se_rechaza(): void
    {
        $jugador = $this->empleadoJugador();
        $this->registrarNeto($jugador, EstadoApuesta::Perdida, '25.00', '0.00');
        $this->expectException(ValidationException::class);
        app(AutorizacionJugadorService::class)->registrarAbono($jugador, '25.01');
    }

    public function test_d07_admin_consulta_deuda_y_registra_abono(): void
    {
        $jugador = $this->empleadoJugador();
        $this->registrarNeto($jugador, EstadoApuesta::Perdida, '250.00', '0.00');
        $administrador = User::factory()->administrador()->create();
        $this->actingAs($administrador)->get(route('jugadores.abonos.index', $jugador))
            ->assertOk()->assertSee('250.00')->assertSee('200.00');
        $this->post(route('jugadores.abonos.store', $jugador), ['monto' => '50.00'])->assertRedirect();
        $this->assertDatabaseHas('abonos', ['jugador_id' => $jugador->id, 'monto' => '50.00']);
    }

    public function test_d07_abono_se_rechaza_para_jugador_no_empleado(): void
    {
        $jugador = $this->crearJugador();
        $this->actingAs(User::factory()->administrador()->create())
            ->get(route('jugadores.abonos.index', $jugador))->assertForbidden();
    }

    public function test_rn_18_empleado_con_ganancia_mayor_a_un_salario_queda_en_investigacion(): void
    {
        $jugador = $this->empleadoJugador();
        $this->registrarNeto($jugador, EstadoApuesta::Ganada, '100.00', '200.01');
        app(AutorizacionJugadorService::class)->evaluarTrasApuesta($jugador);
        $this->assertDatabaseHas('suspensiones', ['jugador_id' => $jugador->id, 'motivo' => 'investigacion', 'fin' => null]);
    }

    public function test_rn_18_en_investigacion_no_apuesta(): void
    {
        $jugador = $this->empleadoJugador();
        $jugador->suspensiones()->create(['motivo' => MotivoSuspension::Investigacion, 'inicio' => now()->subMinute()]);
        $this->assertNotNull(app(AutorizacionJugadorService::class)->motivoBloqueo($jugador));
    }

    public function test_d08_admin_finaliza_investigacion_y_vuelve_a_apostar(): void
    {
        $jugador = $this->empleadoJugador();
        $suspension = $jugador->suspensiones()->create(['motivo' => MotivoSuspension::Investigacion, 'inicio' => now()->subHour()]);
        $this->actingAs(User::factory()->administrador()->create())->post(route('suspensiones.finalizar', $suspension))->assertRedirect();
        $this->assertNotNull($suspension->fresh()->fin);
        $this->assertNull(app(AutorizacionJugadorService::class)->motivoBloqueo($jugador));
    }

    public function test_d06_tras_finalizar_la_investigacion_la_ganancia_se_cuenta_de_nuevo(): void
    {
        $jugador = $this->empleadoJugador();
        $suspension = $jugador->suspensiones()->create(['motivo' => MotivoSuspension::Investigacion, 'inicio' => now()->subHour(), 'fin' => now()]);
        $this->registrarNeto($jugador, EstadoApuesta::Ganada, '100.00', '200.00', now()->subMinute());
        $this->assertSame('0.00', app(AutorizacionJugadorService::class)->gananciaEmpleadoDesdeUltimaInvestigacion($jugador));
        $this->registrarNeto($jugador, EstadoApuesta::Ganada, '100.00', '200.00', now()->addMinute());
        $this->assertSame('100.00', app(AutorizacionJugadorService::class)->gananciaEmpleadoDesdeUltimaInvestigacion($jugador));
    }

    public function test_rn_17_y_rn_18_no_aplican_a_jugadores_que_no_son_empleados(): void
    {
        $jugador = $this->crearJugador();
        $this->registrarNeto($jugador, EstadoApuesta::Perdida, '10000.00', '0.00');
        app(AutorizacionJugadorService::class)->evaluarTrasApuesta($jugador);
        $this->assertSame(0, $jugador->suspensiones()->whereIn('motivo', ['perdida_empleado', 'investigacion'])->count());
    }

    public function test_rf_rol_02_solo_el_administrador_ve_suspensiones_y_registra_abonos(): void
    {
        $jugador = $this->empleadoJugador();
        $this->actingAs($jugador->user)->get(route('suspensiones.index'))->assertForbidden();
        $this->actingAs($jugador->user)->post(route('jugadores.abonos.store', $jugador), ['monto' => '1.00'])->assertForbidden();
        $this->actingAs(User::factory()->administrador()->create())->get(route('suspensiones.index'))->assertOk();
    }

    private function empleadoJugador()
    {
        $empleado = $this->crearEmpleado('crupier', 'E'.uniqid(), Juego::firstOrFail());

        return $empleado->user->jugador()->create(['codigo_cliente' => 'J'.uniqid(), 'documento' => $empleado->documento, 'nombre_completo' => 'Empleado', 'edad' => 30, 'saldo' => '1000.00']);
    }

    private function registrarNeto($jugador, EstadoApuesta $estado, string $monto, string $premio, $fecha = null): void
    {
        $juego = Juego::firstOrFail();
        $crupier = $this->crearEmpleado('crupier', 'C'.uniqid(), $juego);
        $supervisor = $this->crearEmpleado('supervisor', 'S'.uniqid(), $juego);
        $mesa = $this->crearMesa($juego, 'M'.uniqid());
        $ronda = $this->crearRonda($this->crearAsignacion($this->crearTurno(), $mesa, $crupier, $supervisor), 1, true);
        $apuesta = $this->crearApuesta($ronda, $jugador, 'Rojo', $monto, '1.00', $estado);
        $apuesta->update(['premio' => $premio, 'resuelta_en' => $fecha ?? now()]);
    }
}
