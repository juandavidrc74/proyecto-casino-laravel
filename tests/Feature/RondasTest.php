<?php

namespace Tests\Feature;

use App\Models\Juego;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\CreaCasino;
use Tests\TestCase;

class RondasTest extends TestCase
{
    use CreaCasino, RefreshDatabase;

    private function mesaEnCurso(): array
    {
        $this->travelTo(now()->setDate(2026, 10, 29)->setTime(9, 0));
        $juego = Juego::where('registro_detallado', true)->firstOrFail();
        $crupier = $this->crearEmpleado('crupier', 'C1', $juego);
        $supervisor = $this->crearEmpleado('supervisor', 'S1', $juego);
        $mesa = $this->crearMesa($juego);
        $asignacion = $this->crearAsignacion($this->crearTurno(), $mesa, $crupier, $supervisor);
        $this->actingAs($crupier->user);

        return [$mesa, $asignacion, $crupier];
    }

    public function test_rn_09_numeracion_correlativa_por_mesa(): void
    {
        [$mesa, $asignacion] = $this->mesaEnCurso();
        $otraMesa = $this->crearMesa($mesa->juego, 'M2');
        $otraAsignacion = $this->crearAsignacion($asignacion->turno, $otraMesa, $this->crearEmpleado('crupier', 'C2', $mesa->juego), $this->crearEmpleado('supervisor', 'S2', $mesa->juego));
        for ($i = 1; $i <= 3; $i++) {
            $this->post(route('rondas.store', $mesa))->assertRedirect();
            $ronda = $mesa->rondas()->where('numero', $i)->firstOrFail();
            $this->post(route('rondas.cerrar', $ronda), ['resultado' => 'Rojo'])->assertRedirect();
        }
        $this->actingAs($otraAsignacion->crupier->user)->post(route('rondas.store', $otraMesa))->assertRedirect();
        $this->assertDatabaseHas('rondas', ['mesa_id' => $otraMesa->id, 'numero' => 1]);
        $this->assertDatabaseCount('rondas', 4);
    }

    public function test_d15_no_abre_segunda_ronda_abierta(): void
    {
        [$mesa, $asignacion] = $this->mesaEnCurso();
        $ronda = $this->crearRonda($asignacion, 1);
        $this->post(route('rondas.store', $mesa))->assertSessionHasErrors('ronda');
        $this->assertDatabaseCount('rondas', 1);
        $this->assertNotNull($ronda->fresh());
    }

    public function test_cu_02_anula_ronda_abierta_sin_apuestas_y_no_con_apuestas(): void
    {
        [$mesa, $asignacion] = $this->mesaEnCurso();
        $ronda = $this->crearRonda($asignacion, 1);
        $this->delete(route('rondas.destroy', $ronda))->assertRedirect();
        $this->assertDatabaseMissing('rondas', ['id' => $ronda->id]);
        $ronda = $this->crearRonda($asignacion, 1);
        $this->crearApuesta($ronda, $this->crearJugador());
        $this->delete(route('rondas.destroy', $ronda))->assertSessionHasErrors('ronda');
    }

    public function test_d14_crupier_sin_asignacion_en_curso_recibe_403(): void
    {
        $user = User::factory()->crupier()->create();
        $this->actingAs($user)->get(route('mi-mesa'))->assertOk()->assertSee('No tienes una mesa asignada');
        $this->post(route('rondas.store', $this->crearMesa(Juego::firstOrFail())))->assertForbidden();
    }

    public function test_d14_crupier_de_otra_mesa_recibe_403(): void
    {
        [$mesa, $asignacion] = $this->mesaEnCurso();
        $otra = $this->crearMesa($mesa->juego, 'M2');
        $ronda = $this->crearRonda($asignacion, 1);
        $this->post(route('rondas.store', $otra))->assertForbidden();
        $this->post(route('rondas.cerrar', $ronda), ['resultado' => 'Rojo'])->assertRedirect();
        $tercera = $this->crearRonda($asignacion, 2);
        $this->actingAs($this->crearEmpleado('crupier', 'C2', $mesa->juego)->user)->post(route('rondas.cerrar', $tercera), ['resultado' => 'Rojo'])->assertForbidden();
    }

    public function test_d14_fuera_del_turno_no_opera(): void
    {
        [$mesa] = $this->mesaEnCurso();
        $this->travelTo(now()->setTime(10, 0));
        $this->post(route('rondas.store', $mesa))->assertForbidden();
    }

    public function test_rn_09_no_se_borra_asignacion_con_rondas(): void
    {
        [$mesa, $asignacion] = $this->mesaEnCurso();
        $this->crearRonda($asignacion, 1);
        $this->actingAs(User::factory()->administrador()->create())->from(route('turnos.show', $asignacion->turno))->delete(route('turnos.asignaciones.destroy', [$asignacion->turno, $asignacion]))->assertSessionHasErrors('asignacion');
    }
}
