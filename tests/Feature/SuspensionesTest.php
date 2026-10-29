<?php

namespace Tests\Feature;

use App\Enums\EstadoApuesta;
use App\Enums\MotivoSuspension;
use App\Models\Juego;
use App\Services\AutorizacionJugadorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\CreaCasino;
use Tests\TestCase;

class SuspensionesTest extends TestCase
{
    use CreaCasino, RefreshDatabase;

    public function test_rn_14_perdida_diaria_mayor_al_umbral_suspende_24_horas(): void
    {
        $this->travelTo(now()->setDate(2026, 10, 29)->setTime(12, 0));
        config(['casino.umbral_perdida_diaria' => '100.00']);
        $jugador = $this->crearJugador();
        $this->crearResuelta($jugador, EstadoApuesta::Perdida, '100.01', '0.00');
        app(AutorizacionJugadorService::class)->evaluarTrasApuesta($jugador);
        $this->assertDatabaseHas('suspensiones', ['jugador_id' => $jugador->id, 'motivo' => 'perdida_diaria', 'fin' => '2026-10-30 12:00:00']);
        $this->assertSame('Activo', $this->crearJugador('J2')->estado());
    }

    public function test_rn_14_igual_al_umbral_no_suspende(): void
    {
        config(['casino.umbral_perdida_diaria' => '100.00']);
        $jugador = $this->crearJugador();
        $this->crearResuelta($jugador, EstadoApuesta::Perdida, '100.00', '0.00');
        app(AutorizacionJugadorService::class)->evaluarTrasApuesta($jugador);
        $this->assertDatabaseCount('suspensiones', 0);
    }

    public function test_rn_14_suspendido_no_apuesta_en_ninguna_mesa(): void
    {
        $this->travelTo(now()->setDate(2026, 10, 29)->setTime(9, 0));
        $mesa = $this->contextoMesa();
        $jugador = $this->crearJugador();
        $jugador->suspensiones()->create(['motivo' => MotivoSuspension::PausaVoluntaria, 'inicio' => now()->subMinute(), 'fin' => now()->addDay()]);
        $this->actingAs($mesa['crupier']->user)->post(route('apuestas.store', $mesa['ronda']), [
            'codigo_cliente' => $jugador->codigo_cliente, 'tipo_apuesta' => 'Rojo', 'monto' => '10.00', 'factor_pago' => '2.00',
        ])->assertSessionHasErrors('codigo_cliente');
    }

    public function test_rn_14_tras_24_horas_vuelve_a_apostar(): void
    {
        $this->travelTo(now()->setDate(2026, 10, 29)->setTime(9, 0));
        $mesa = $this->contextoMesa();
        $jugador = $this->crearJugador();
        $jugador->suspensiones()->create(['motivo' => MotivoSuspension::PerdidaDiaria, 'inicio' => now()->subDay(), 'fin' => now()]);
        $this->actingAs($mesa['crupier']->user)->post(route('apuestas.store', $mesa['ronda']), [
            'codigo_cliente' => $jugador->codigo_cliente, 'tipo_apuesta' => 'Rojo', 'monto' => '10.00', 'factor_pago' => '2.00',
        ])->assertRedirect();
    }

    public function test_d06_la_perdida_diaria_es_neta_y_por_dia_calendario(): void
    {
        $this->travelTo(now()->setDate(2026, 10, 29)->setTime(12, 0));
        $jugador = $this->crearJugador();
        $this->crearResuelta($jugador, EstadoApuesta::Perdida, '30.00', '0.00', now()->startOfDay()->addHour());
        $this->crearResuelta($jugador, EstadoApuesta::Ganada, '20.00', '15.00', now()->startOfDay()->addHours(2));
        $this->crearResuelta($jugador, EstadoApuesta::Perdida, '40.00', '0.00', now()->subDay());
        $this->assertSame('35.00', app(AutorizacionJugadorService::class)->perdidaNetaDelDia($jugador, now()));
    }

    public function test_rn_16_empleado_con_perfil_apuesta_como_cliente(): void
    {
        $this->travelTo(now()->setDate(2026, 10, 29)->setTime(9, 0));
        $mesa = $this->contextoMesa();
        $empleado = $this->crearEmpleado('crupier', 'E1', Juego::firstOrFail());
        $jugador = $empleado->user->jugador()->create(['codigo_cliente' => 'EMP1', 'documento' => 'E1', 'nombre_completo' => 'Empleado jugador', 'edad' => 30, 'saldo' => '100.00']);
        $this->actingAs($mesa['crupier']->user)->post(route('apuestas.store', $mesa['ronda']), [
            'codigo_cliente' => $jugador->codigo_cliente, 'tipo_apuesta' => 'Rojo', 'monto' => '10.00', 'factor_pago' => '2.00',
        ])->assertRedirect();
        $this->assertDatabaseHas('apuestas', ['jugador_id' => $jugador->id]);
    }

    private function crearResuelta($jugador, EstadoApuesta $estado, string $monto, string $premio, $fecha = null): void
    {
        $juego = Juego::firstOrFail();
        $crupier = $this->crearEmpleado('crupier', 'C'.uniqid(), $juego);
        $supervisor = $this->crearEmpleado('supervisor', 'S'.uniqid(), $juego);
        $mesa = $this->crearMesa($juego, 'M'.uniqid());
        $ronda = $this->crearRonda($this->crearAsignacion($this->crearTurno(), $mesa, $crupier, $supervisor), 1, true);
        $apuesta = $this->crearApuesta($ronda, $jugador, 'Rojo', $monto, '1.00', $estado);
        $apuesta->update(['premio' => $premio, 'resuelta_en' => $fecha ?? now()]);
    }

    private function contextoMesa(): array
    {
        $juego = Juego::where('registro_detallado', true)->firstOrFail();
        $crupier = $this->crearEmpleado('crupier', 'C'.uniqid(), $juego);
        $supervisor = $this->crearEmpleado('supervisor', 'S'.uniqid(), $juego);
        $mesa = $this->crearMesa($juego, 'M'.uniqid());
        $ronda = $this->crearRonda($this->crearAsignacion($this->crearTurno(), $mesa, $crupier, $supervisor), 1);

        return compact('crupier', 'ronda');
    }
}
