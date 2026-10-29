<?php

namespace Tests\Feature;

use App\Enums\Rol;
use App\Models\Empleado;
use App\Models\Juego;
use App\Models\Mesa;
use App\Models\Turno;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AsignacionesTest extends TestCase
{
    use RefreshDatabase;

    private int $juego;

    private function admin(): void
    {
        $this->actingAs(User::factory()->administrador()->create());
        $this->juego = (int) Juego::where('nombre', 'Ruleta')->value('id');
    }

    private function empleado(string $rol, string $doc, bool $competente = true): Empleado
    {
        $u = User::factory()->create(['rol' => Rol::from($rol)]);
        $e = $u->empleado()->create(['documento' => $doc, 'nombre_completo' => $doc, 'salario_mensual' => '100']);
        if ($competente) {
            $e->juegos()->attach($this->juego);
        }

return $e;
    }

    private function turno(string $fecha = '2026-10-30', string $inicio = '08:00'): Turno
    {
        return Turno::create(['fecha' => $fecha, 'hora_inicio' => $inicio, 'hora_fin' => substr((string) (intval(substr($inicio, 0, 2)) + 2).':00', 0, 5)]);
    }

    private function mesa(string $codigo): Mesa
    {
        return Mesa::create(['codigo' => $codigo, 'juego_id' => $this->juego, 'apuesta_minima' => '1', 'apuesta_maxima' => '100']);
    }

    private function asignar(Turno $t, Mesa $m, Empleado $c, Empleado $s)
    {
        return $this->post(route('turnos.asignaciones.store', $t), ['mesa_id' => $m->id, 'crupier_id' => $c->id, 'supervisor_id' => $s->id]);
    }

    public function test_rn_04_una_mesa_no_tiene_dos_asignaciones_en_el_turno(): void
    {
        $this->admin();
        $t = $this->turno();
        $m = $this->mesa('M1');
        $c = $this->empleado('crupier', 'C1');
        $c2 = $this->empleado('crupier', 'C2');
        $s = $this->empleado('supervisor', 'S1');
        $this->asignar($t, $m, $c, $s)->assertRedirect(route('turnos.show', $t));
        $this->asignar($t, $m, $c2, $s)->assertSessionHasErrors('mesa_id');
    }

    public function test_rn_04_un_crupier_no_tiene_dos_mesas_en_el_turno(): void
    {
        $this->admin();
        $t = $this->turno();
        $c = $this->empleado('crupier', 'C1');
        $s = $this->empleado('supervisor', 'S1');
        $this->asignar($t, $this->mesa('M1'), $c, $s);
        $this->asignar($t, $this->mesa('M2'), $c, $s)->assertSessionHasErrors('crupier_id');
    }

    public function test_rn_05_crupier_no_repite_mesa_en_la_misma_jornada(): void
    {
        $this->admin();
        $c = $this->empleado('crupier', 'C1');
        $s = $this->empleado('supervisor', 'S1');
        $m = $this->mesa('M1');
        $this->asignar($this->turno('2026-10-30', '08:00'), $m, $c, $s);
        $this->asignar($this->turno('2026-10-30', '10:00'), $m, $c, $s)->assertSessionHasErrors('crupier_id');
    }

    public function test_rn_05_crupier_puede_repetir_mesa_otro_dia(): void
    {
        $this->admin();
        $c = $this->empleado('crupier', 'C1');
        $s = $this->empleado('supervisor', 'S1');
        $m = $this->mesa('M1');
        $this->asignar($this->turno('2026-10-30'), $m, $c, $s);
        $this->asignar($this->turno('2026-10-31'), $m, $c, $s)->assertRedirect();
        $this->assertDatabaseCount('asignaciones', 2);
    }

    public function test_rn_08_rechaza_crupier_o_supervisor_sin_competencia_en_el_juego(): void
    {
        $this->admin();
        $t = $this->turno();
        $m = $this->mesa('M1');
        $c = $this->empleado('crupier', 'C1', false);
        $s = $this->empleado('supervisor', 'S1', false);
        $this->asignar($t, $m, $c, $s)->assertSessionHasErrors(['crupier_id', 'supervisor_id']);
    }

    public function test_rn_04_rechaza_empleados_con_rol_equivocado(): void
    {
        $this->admin();
        $t = $this->turno();
        $m = $this->mesa('M1');
        $s = $this->empleado('supervisor', 'S1');
        $c = $this->empleado('crupier', 'C1');
        $this->asignar($t, $m, $s, $c)->assertSessionHasErrors(['crupier_id', 'supervisor_id']);
    }

    public function test_fa_02_no_se_borra_empleado_con_asignaciones(): void
    {
        $this->admin();
        $t = $this->turno();
        $m = $this->mesa('M1');
        $c = $this->empleado('crupier', 'C1');
        $s = $this->empleado('supervisor', 'S1');
        $this->asignar($t, $m, $c, $s);
        $this->from(route('empleados.index'))->delete(route('empleados.destroy', $c))->assertSessionHasErrors('empleado');
        $this->assertDatabaseHas('empleados', ['id' => $c->id]);
    }

    public function test_rf_rol_02_solo_el_administrador_gestiona_mesas_turnos_y_asignaciones(): void
    {
        $this->admin();
        $t = $this->turno();
        $m = $this->mesa('M1');
        $c = $this->empleado('crupier', 'C1');
        $s = $this->empleado('supervisor', 'S1');
        foreach ([Rol::Crupier, Rol::Supervisor, Rol::Jugador] as $rol) {
            $this->actingAs(User::factory()->create(['rol' => $rol]));
            $this->get(route('mesas.index'))->assertForbidden();
            $this->get(route('turnos.index'))->assertForbidden();
            $this->post(route('turnos.asignaciones.store',$t),['mesa_id' => $m->id, 'crupier_id' => $c->id, 'supervisor_id' => $s->id])->assertForbidden();
        }
    }
}
