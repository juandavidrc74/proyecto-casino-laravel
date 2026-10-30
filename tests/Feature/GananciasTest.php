<?php

namespace Tests\Feature;

use App\Enums\EstadoApuesta;
use App\Models\Juego;
use App\Models\Turno;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\CreaCasino;
use Tests\TestCase;

class GananciasTest extends TestCase
{
    use CreaCasino, RefreshDatabase;

    public function test_cu_04_ganancias_calcula_totales_por_mesa_y_filtra_por_fecha(): void
    {
        $this->travelTo(now()->setDate(2026, 10, 29)->setTime(12, 0));
        $juego = Juego::firstOrFail();
        $crupier = $this->crearEmpleado('crupier', 'C1', $juego);
        $supervisor = $this->crearEmpleado('supervisor', 'S1', $juego);
        $mesa = $this->crearMesa($juego);
        $asignacion = $this->crearAsignacion($this->crearTurno(), $mesa, $crupier, $supervisor);
        $ronda = $this->crearRonda($asignacion, 1, true);
        $ganada = $this->crearApuesta($ronda, $this->crearJugador(), 'Rojo', '10.00', '1.50', EstadoApuesta::Ganada);
        $ganada->update([
            'premio' => '15.00',
            'resuelta_en' => '2026-10-29 09:00:00',
        ]);
        $perdida = $this->crearApuesta(
            $ronda,
            $this->crearJugador('J2'),
            'Negro',
            '20.00',
            '2.00',
            EstadoApuesta::Perdida
        );
        $perdida->update([
            'resuelta_en' => '2026-10-29 09:00:00',
        ]);
        $this->actingAs(User::factory()->administrador()->create())
            ->get(route('ganancias.index', ['desde' => '2026-10-29', 'hasta' => '2026-10-29']))
            ->assertOk()
            ->assertSee('30.00')
            ->assertSee('15.00')
            ->assertSee('20.00')
            ->assertSee('15.00');
        $this->get(route('ganancias.index', ['desde' => '2026-10-28', 'hasta' => '2026-10-28']))
            ->assertOk()
            ->assertSee('0.00');
    }

    public function test_cu_04_supervisor_solo_ve_sus_mesas(): void
    {
        $this->travelTo(now()->setDate(2026, 10, 29)->setTime(12, 0));
        $juego = Juego::firstOrFail();
        $c1 = $this->crearEmpleado('crupier', 'C1', $juego);
        $s1 = $this->crearEmpleado('supervisor', 'S1', $juego);
        $c2 = $this->crearEmpleado('crupier', 'C2', $juego);
        $s2 = $this->crearEmpleado('supervisor', 'S2', $juego);
        $m1 = $this->crearMesa($juego, 'M1');
        $m2 = $this->crearMesa($juego, 'M2');
        $this->crearAsignacion($this->crearTurno(), $m1, $c1, $s1);
        $this->crearAsignacion($this->crearTurno(), $m2, $c2, $s2);
        $this->actingAs($s1->user)
            ->get(route('ganancias.index'))
            ->assertOk()
            ->assertSee('M1')
            ->assertDontSeeText('M2');
    }

    public function test_cu_04_supervisor_no_ve_rondas_de_turnos_que_no_superviso(): void
    {
        $this->travelTo(now()->setDate(2026, 10, 29)->setTime(13, 0));
        $juego = Juego::firstOrFail();
        $mesa = $this->crearMesa($juego);
        $crupier1 = $this->crearEmpleado('crupier', 'C1', $juego);
        $supervisor1 = $this->crearEmpleado('supervisor', 'S1', $juego);
        $crupier2 = $this->crearEmpleado('crupier', 'C2', $juego);
        $supervisor2 = $this->crearEmpleado('supervisor', 'S2', $juego);
        $turno1 = Turno::create([
            'fecha' => '2026-10-29',
            'hora_inicio' => '08:00',
            'hora_fin' => '10:00',
        ]);
        $turno2 = Turno::create([
            'fecha' => '2026-10-29',
            'hora_inicio' => '10:00',
            'hora_fin' => '12:00',
        ]);
        $asignacion1 = $this->crearAsignacion($turno1, $mesa, $crupier1, $supervisor1);
        $asignacion2 = $this->crearAsignacion($turno2, $mesa, $crupier2, $supervisor2);
        $ronda1 = $this->crearRonda($asignacion1, 1, true);
        $ronda2 = $this->crearRonda($asignacion2, 2, true);
        $apuesta1 = $this->crearApuesta(
            $ronda1,
            $this->crearJugador('J1'),
            'Rojo',
            '10.00',
            '1.50',
            EstadoApuesta::Perdida
        );
        $apuesta1->update([
            'resuelta_en' => '2026-10-29 09:00:00',
        ]);
        $apuesta2 = $this->crearApuesta(
            $ronda2,
            $this->crearJugador('J2'),
            'Negro',
            '20.00',
            '1.50',
            EstadoApuesta::Perdida
        );
        $apuesta2->update([
            'resuelta_en' => '2026-10-29 11:00:00',
        ]);

        $this->actingAs($supervisor1->user)
            ->get(route('ganancias.index', ['desde' => '2026-10-29', 'hasta' => '2026-10-29']))
            ->assertOk()
            ->assertSee('10.00')
            ->assertDontSee('30.00')
            ->assertDontSee('20.00');
    }
}
