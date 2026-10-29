<?php

namespace Tests\Feature;

use App\Models\Asignacion;
use App\Models\Juego;
use App\Models\Mesa;
use App\Models\Turno;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TurnosTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $u = User::factory()->administrador()->create();
        $this->actingAs($u);

        return $u;
    }

    private function payload(array $a = []): array
    {
        return array_merge(['fecha' => '2026-10-30', 'hora_inicio' => '08:00', 'hora_fin' => '10:00'], $a);
    }

    private function turnoConAsignacion(): Turno
    {
        $t = Turno::create($this->payload());
        $m = Mesa::create(['codigo' => 'M1', 'juego_id' => Juego::first()->id, 'apuesta_minima' => '1', 'apuesta_maxima' => '10']);
        $c = User::factory()->crupier()->create()->empleado()->create(['documento' => 'C1', 'nombre_completo' => 'C', 'salario_mensual' => '10']);
        $s = User::factory()->supervisor()->create()->empleado()->create(['documento' => 'S1', 'nombre_completo' => 'S', 'salario_mensual' => '10']);
        Asignacion::create(['turno_id' => $t->id, 'mesa_id' => $m->id, 'crupier_id' => $c->id, 'supervisor_id' => $s->id]);

        return $t;
    }

    public function test_rn_06_turno_de_2_horas_se_acepta_y_de_2_horas_1_minuto_se_rechaza(): void
    {
        $this->admin();
        $this->post(route('turnos.store'), $this->payload())->assertRedirect(route('turnos.index'));
        $this->post(route('turnos.store'), $this->payload(['fecha' => '2026-10-31', 'hora_fin' => '10:01']))->assertSessionHasErrors('hora_fin');
    }

    public function test_d10_turno_no_cruza_medianoche(): void
    {
        $this->admin();
        $this->post(route('turnos.store'), $this->payload(['hora_inicio' => '23:00', 'hora_fin' => '01:00']))->assertSessionHasErrors('hora_fin');
    }

    public function test_d10_turnos_del_mismo_dia_no_se_solapan_pero_pueden_ser_contiguos(): void
    {
        $this->admin();
        $this->post(route('turnos.store'), $this->payload())->assertRedirect(route('turnos.index'));
        $this->post(route('turnos.store'), $this->payload(['hora_inicio' => '10:00', 'hora_fin' => '12:00']))->assertRedirect(route('turnos.index'));
        $this->post(route('turnos.store'), $this->payload(['hora_inicio' => '09:59', 'hora_fin' => '11:00']))->assertSessionHasErrors('hora_inicio');
    }

    public function test_d10_al_editar_el_propio_turno_no_cuenta_como_solape(): void
    {
        $this->admin();
        $this->post(route('turnos.store'), $this->payload())->assertRedirect(route('turnos.index'));
        $t = Turno::firstOrFail();
        $this->put(route('turnos.update', $t), $this->payload())->assertRedirect(route('turnos.show', $t));
    }

    public function test_rn_06_turno_con_asignaciones_no_se_edita_ni_borra(): void
    {
        $this->admin();
        $t = $this->turnoConAsignacion();
        $this->put(route('turnos.update', $t), $this->payload(['hora_inicio' => '10:00', 'hora_fin' => '11:00']))->assertSessionHasErrors('fecha');
        $this->from(route('turnos.index'))->delete(route('turnos.destroy', $t))->assertSessionHasErrors('turno');
        $this->assertDatabaseHas('turnos', ['id' => $t->id]);
    }
}
