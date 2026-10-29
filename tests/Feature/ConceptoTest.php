<?php

namespace Tests\Feature;

use App\Enums\Concepto;
use App\Models\Asignacion;
use App\Models\Juego;
use App\Models\Mesa;
use App\Models\Turno;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ConceptoTest extends TestCase
{
    use RefreshDatabase;

    private function datos(): array
    {
        $j = Juego::firstOrFail();
        $c = User::factory()->crupier()->create()->empleado()->create(['documento' => 'C', 'nombre_completo' => 'Crupier', 'salario_mensual' => '100']);
        $s = User::factory()->supervisor()->create();
        $se = $s->empleado()->create(['documento' => 'S', 'nombre_completo' => 'Supervisora', 'salario_mensual' => '100']);
        $otro = User::factory()->supervisor()->create();
        $otro->empleado()->create(['documento' => 'S2', 'nombre_completo' => 'Otra', 'salario_mensual' => '100']);
        $m = Mesa::create(['codigo' => 'M', 'juego_id' => $j->id, 'apuesta_minima' => '1', 'apuesta_maxima' => '10']);
        $t = Turno::create(['fecha' => '2026-10-30', 'hora_inicio' => '08:00', 'hora_fin' => '10:00']);
        $a = Asignacion::create(['turno_id' => $t->id, 'mesa_id' => $m->id, 'crupier_id' => $c->id, 'supervisor_id' => $se->id]);

        return [$s, $otro, $a];
    }

    public function test_rn_07_supervisor_asignado_registra_concepto_al_terminar_el_turno(): void
    {
        [$s,$otro,$a] = $this->datos();
        $this->travelTo(Carbon::parse('2026-10-30 10:00:00', 'America/Bogota'));
        $this->actingAs($s)->post(route('conceptos.store', $a), ['concepto' => Concepto::GeneraGanancias->value])->assertRedirect(route('conceptos.index'));
        $this->assertDatabaseHas('asignaciones', ['id' => $a->id, 'concepto' => 'Genera ganancias']);
        $this->assertNotNull($a->fresh()->concepto_registrado_en);
    }

    public function test_d11_rechaza_concepto_antes_del_fin_del_turno(): void
    {
        [$s,$otro,$a] = $this->datos();
        $this->travelTo(Carbon::parse('2026-10-30 09:59:00', 'America/Bogota'));
        $this->actingAs($s)->post(route('conceptos.store', $a), ['concepto' => Concepto::GeneraPerdidas->value])->assertSessionHasErrors('concepto');
    }

    public function test_d11_rechaza_segundo_concepto(): void
    {
        [$s,$otro,$a] = $this->datos();
        $a->update(['concepto' => Concepto::ComportamientoNeutro, 'concepto_registrado_en' => now()]);
        $this->travelTo(Carbon::parse('2026-10-30 10:00:00', 'America/Bogota'));
        $this->actingAs($s)->post(route('conceptos.store', $a), ['concepto' => Concepto::GeneraGanancias->value])->assertSessionHasErrors('concepto');
    }

    public function test_rn_07_rechaza_valor_no_permitido(): void
    {
        [$s,$otro,$a] = $this->datos();
        $this->travelTo(Carbon::parse('2026-10-30 10:00:00', 'America/Bogota'));
        $this->actingAs($s)->post(route('conceptos.store', $a), ['concepto' => 'Inventado'])->assertSessionHasErrors('concepto');
    }

    public function test_d11_otro_supervisor_recibe_403(): void
    {
        [$s,$otro,$a] = $this->datos();
        $this->travelTo(Carbon::parse('2026-10-30 10:00:00', 'America/Bogota'));
        $this->actingAs($otro)->post(route('conceptos.store', $a), ['concepto' => Concepto::GeneraGanancias->value])->assertForbidden();
    }

    public function test_rf_rol_02_solo_supervisores_ven_mis_conceptos(): void
    {
        $this->datos();
        $this->actingAs(User::factory()->administrador()->create())->get(route('conceptos.index'))->assertForbidden();
        $this->actingAs(User::factory()->crupier()->create())->get(route('conceptos.index'))->assertForbidden();
    }
}
