<?php

namespace Tests\Feature;

use App\Models\Asignacion;
use App\Models\Juego;
use App\Models\Mesa;
use App\Models\Turno;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MesasTest extends TestCase
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
        return array_merge(['codigo' => 'M-1', 'juego_id' => Juego::where('nombre', 'Ruleta')->value('id'), 'apuesta_minima' => '1.00', 'apuesta_maxima' => '100.00'], $a);
    }

    private function asignada(): Mesa
    {
        $mesa = Mesa::create($this->payload());
        $turno = Turno::create(['fecha' => '2026-10-30', 'hora_inicio' => '08:00', 'hora_fin' => '10:00']);
        $c = User::factory()->crupier()->create()->empleado()->create(['documento' => 'C-1', 'nombre_completo' => 'Crupier', 'salario_mensual' => '100.00']);
        $s = User::factory()->supervisor()->create()->empleado()->create(['documento' => 'S-1', 'nombre_completo' => 'Supervisor', 'salario_mensual' => '100.00']);
        Asignacion::create(['turno_id' => $turno->id, 'mesa_id' => $mesa->id, 'crupier_id' => $c->id, 'supervisor_id' => $s->id]);

        return $mesa;
    }

    public function test_cu_01_admin_crea_edita_y_borra_mesa(): void
    {
        $this->admin();
        $j = Juego::where('nombre', 'Blackjack')->value('id');
        $this->post(route('mesas.store'), $this->payload())->assertRedirect(route('mesas.index'));
        $mesa = Mesa::firstOrFail();
        $this->put(route('mesas.update', $mesa), $this->payload(['codigo' => 'M-2', 'juego_id' => $j]))->assertRedirect(route('mesas.index'));
        $this->assertDatabaseHas('mesas', ['id' => $mesa->id, 'codigo' => 'M-2', 'juego_id' => $j]);
        $this->delete(route('mesas.destroy', $mesa))->assertRedirect(route('mesas.index'));
        $this->assertDatabaseMissing('mesas', ['id' => $mesa->id]);
    }

    public function test_rn_03_codigo_unico_y_minimo_no_mayor_que_maximo(): void
    {
        $this->admin();
        $this->post(route('mesas.store'), $this->payload());
        $this->post(route('mesas.store'), $this->payload(['codigo' => 'M-1']))->assertSessionHasErrors('codigo');
        $this->post(route('mesas.store'), $this->payload(['codigo' => 'M-2', 'apuesta_minima' => '101', 'apuesta_maxima' => '100']))->assertSessionHasErrors('apuesta_maxima');
        $this->post(route('mesas.store'), $this->payload(['codigo' => 'M-3', 'apuesta_minima' => '0']))->assertSessionHasErrors('apuesta_minima');
    }

    public function test_cu_01_no_borra_ni_cambia_juego_de_mesa_con_asignaciones(): void
    {
        $this->admin();
        $mesa = $this->asignada();
        $j = Juego::where('nombre', 'Blackjack')->value('id');
        $this->put(route('mesas.update', $mesa), $this->payload(['juego_id' => $j]))->assertSessionHasErrors('juego_id');
        $this->from(route('mesas.index'))->delete(route('mesas.destroy', $mesa))->assertSessionHasErrors('mesa');
        $this->assertDatabaseHas('mesas',['id' => $mesa->id]);
    }
}
