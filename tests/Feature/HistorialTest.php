<?php

namespace Tests\Feature;

use App\Enums\EstadoApuesta;
use App\Enums\MotivoSuspension;
use App\Models\Juego;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\CreaCasino;
use Tests\TestCase;

class HistorialTest extends TestCase
{
    use CreaCasino, RefreshDatabase;

    public function test_fa_03_admin_ve_el_historial_completo_de_un_jugador(): void
    {
        $this->travelTo(now()->setDate(2026, 10, 29)->setTime(9, 0));
        $empleado = $this->crearEmpleado('crupier', 'E-H', Juego::firstOrFail());
        $jugador = $empleado->user->jugador()->create(['codigo_cliente' => 'J-H', 'documento' => 'E-H', 'nombre_completo' => 'Cliente de historial', 'edad' => 35, 'saldo' => '1000.00']);
        $apuestaGanada = $this->crearApuesta($this->ronda(), $jugador, 'Rojo', '10.00', '1.50', EstadoApuesta::Ganada);
        $apuestaGanada->update(['premio' => '15.00', 'resuelta_en' => now()]);
        $apuestaPerdida = $this->crearApuesta($apuestaGanada->ronda, $jugador, 'Negro', '20.00', '2.00', EstadoApuesta::Perdida);
        $apuestaPerdida->update(['premio' => '0.00', 'resuelta_en' => now()->addMinute()]);
        $jugador->suspensiones()->create(['motivo' => MotivoSuspension::PerdidaEmpleado, 'inicio' => now()->subDay(), 'fin' => now()->subHour()]);
        $jugador->abonos()->create(['monto' => '5.00']);

        $this->actingAs(User::factory()->administrador()->create())
            ->get(route('jugadores.show', $jugador))
            ->assertOk()
            ->assertSee('Cliente de historial')
            ->assertSee('J-H')
            ->assertSee('Apuestas resueltas')
            ->assertSee('2', false)
            ->assertSee('30.00')
            ->assertSee('15.00')
            ->assertSee('-15.00')
            ->assertSee('Pérdida de empleado')
            ->assertSee('Abonos')
            ->assertSee('5.00')
            ->assertSee('Ganada')
            ->assertSee('Perdida');
    }

    public function test_fa_03_jugador_ve_solo_su_historial(): void
    {
        $propio = $this->crearJugador('J-PROPIO');
        $otro = $this->crearJugador('J-AJENO');

        $this->actingAs($propio->user)->get(route('mi-historial'))
            ->assertOk()
            ->assertSee('J-PROPIO')
            ->assertDontSee('J-AJENO');
    }

    public function test_fa_03_jugador_no_accede_a_jugadores_show(): void
    {
        $jugador = $this->crearJugador();
        $otro = $this->crearJugador('J2');

        $this->actingAs($jugador->user)->get(route('jugadores.show', $otro))->assertForbidden();
    }

    public function test_fa_03_empleado_con_perfil_ve_su_historial(): void
    {
        $empleado = $this->crearEmpleado('supervisor', 'E-1', Juego::firstOrFail());
        $empleado->user->jugador()->create(['codigo_cliente' => 'JE-1', 'documento' => 'E-1', 'nombre_completo' => 'Empleado con perfil', 'edad' => 30, 'saldo' => '100.00']);

        $this->actingAs($empleado->user)->get(route('mi-historial'))
            ->assertOk()
            ->assertSee('Empleado con perfil')
            ->assertSee('JE-1');
    }

    public function test_fa_03_cuenta_sin_perfil_recibe_403(): void
    {
        $sinPerfil = User::factory()->crupier()->create();

        $this->actingAs($sinPerfil)->get(route('mi-historial'))->assertForbidden();
    }

    private function ronda()
    {
        $juego = Juego::where('registro_detallado', true)->firstOrFail();
        $crupier = $this->crearEmpleado('crupier', 'C-H', $juego);
        $supervisor = $this->crearEmpleado('supervisor', 'S-H', $juego);
        $mesa = $this->crearMesa($juego, 'M-H');

        return $this->crearRonda($this->crearAsignacion($this->crearTurno(), $mesa, $crupier, $supervisor), 1);
    }
}
