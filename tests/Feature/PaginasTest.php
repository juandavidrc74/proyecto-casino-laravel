<?php

namespace Tests\Feature;

use App\Enums\EstadoApuesta;
use App\Models\Juego;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\CreaCasino;
use Tests\TestCase;

class PaginasTest extends TestCase
{
    use CreaCasino, RefreshDatabase;

    public function test_fa_00_paginas_de_cada_rol_cargan_y_las_de_otros_roles_se_bloquean(): void
    {
        $this->travelTo(now()->setDate(2026, 10, 29)->setTime(9, 0));
        $juego = Juego::firstOrFail();
        $crupierEmpleado = $this->crearEmpleado('crupier', 'C1', $juego);
        $supervisorEmpleado = $this->crearEmpleado('supervisor', 'S1', $juego);
        $mesa = $this->crearMesa($juego);
        $turno = $this->crearTurno();
        $turnoEditable = $this->crearTurno();
        $asignacion = $this->crearAsignacion($turno, $mesa, $crupierEmpleado, $supervisorEmpleado);
        $jugador = $this->crearJugador();
        $rondaCerrada = $this->crearRonda($asignacion, 1, true);
        $apuestaCerrada = $this->crearApuesta($rondaCerrada, $jugador, 'Rojo', '10.00', '1.50', EstadoApuesta::Ganada);
        $apuestaCerrada->update(['premio' => '15.00', 'resuelta_en' => now()]);
        $rondaAbierta = $this->crearRonda($asignacion, 2);
        $apuestaAbierta = $this->crearApuesta($rondaAbierta, $jugador);
        $admin = User::factory()->administrador()->create();
        $jugadorUser = User::factory()->jugador()->create();

        $adminPages = [
            route('empleados.index'), route('empleados.create'), route('empleados.edit', $supervisorEmpleado),
            route('jugadores.index'), route('jugadores.create'), route('jugadores.edit', $jugador),
            route('mesas.index'), route('mesas.create'), route('mesas.edit', $mesa),
            route('turnos.index'), route('turnos.create'), route('turnos.edit', $turnoEditable), route('turnos.show', $turno),
        ];
        foreach ($adminPages as $url) {
            $this->actingAs($admin)->get($url)->assertOk();
        }
        $this->actingAs($admin)->get(route('ganancias.index'))->assertOk();
        foreach ([$crupierEmpleado->user, $supervisorEmpleado->user, $jugadorUser] as $otroRol) {
            foreach ($adminPages as $url) {
                $this->actingAs($otroRol)->get($url)->assertForbidden();
            }
        }
        $this->actingAs($admin)->get(route('mi-mesa'))->assertForbidden();
        $this->actingAs($admin)->get(route('conceptos.index'))->assertForbidden();

        $crupierPages = [route('mi-mesa'), route('apuestas.edit', $apuestaAbierta)];
        foreach ($crupierPages as $url) {
            $this->actingAs($crupierEmpleado->user)->get($url)->assertOk();
        }
        $this->actingAs($crupierEmpleado->user)->get(route('empleados.index'))->assertForbidden();
        $this->actingAs($crupierEmpleado->user)->get(route('conceptos.index'))->assertForbidden();
        $this->actingAs($crupierEmpleado->user)->get(route('ganancias.index'))->assertForbidden();

        $this->actingAs($supervisorEmpleado->user)->get(route('conceptos.index'))->assertOk();
        $this->actingAs($supervisorEmpleado->user)->get(route('ganancias.index'))->assertOk();
        $this->actingAs($supervisorEmpleado->user)->get(route('mesas.index'))->assertForbidden();
        $this->actingAs($supervisorEmpleado->user)->get(route('mi-mesa'))->assertForbidden();

        $this->actingAs($jugadorUser)->get(route('inicio'))->assertOk();
        $this->actingAs($jugadorUser)->get(route('empleados.index'))->assertForbidden()->assertSee('No tienes permiso para ver esta página.');
        $this->actingAs($jugadorUser)->get(route('mi-mesa'))->assertForbidden();
        $this->actingAs($jugadorUser)->get(route('conceptos.index'))->assertForbidden();
        $this->actingAs($jugadorUser)->get(route('ganancias.index'))->assertForbidden();
    }

    public function test_fa_00_paginas_f6_y_f7_responden_segun_el_perfil_y_el_rol(): void
    {
        $juego = Juego::firstOrFail();
        $jugador = $this->crearJugador('J-F7');
        $empleado = $this->crearEmpleado('crupier', 'E-F7', $juego);
        $perfilEmpleado = $empleado->user->jugador()->create([
            'codigo_cliente' => 'JE-F7',
            'documento' => 'E-F7',
            'nombre_completo' => 'Empleado jugador',
            'edad' => 30,
            'saldo' => '100.00',
        ]);
        $admin = User::factory()->administrador()->create();
        $sinPerfil = User::factory()->crupier()->create();
        $jugador->suspensiones()->create(['motivo' => 'pausa_voluntaria', 'inicio' => now()->subMinute(), 'fin' => now()->addDay()]);

        $this->actingAs($admin)->get(route('suspensiones.index'))->assertOk();
        $this->get(route('jugadores.abonos.index', $perfilEmpleado))->assertOk();
        $this->get(route('jugadores.show', $jugador))->assertOk();
        $this->get(route('notificaciones.index'))->assertOk();
        $this->get(route('mi-cuenta'))->assertForbidden();
        $this->get(route('mi-historial'))->assertForbidden();

        $this->actingAs($jugador->user)->get(route('mi-cuenta'))->assertOk()->assertDontSee('Solicitar pausa de 24 horas');
        $this->get(route('notificaciones.index'))->assertOk();
        $this->get(route('mi-historial'))->assertOk();
        $this->get(route('jugadores.show', $perfilEmpleado))->assertForbidden();
        $this->get(route('suspensiones.index'))->assertForbidden();

        $this->actingAs($empleado->user)->get(route('mi-cuenta'))->assertOk();
        $this->get(route('mi-historial'))->assertOk();
        $this->get(route('notificaciones.index'))->assertOk();
        $this->get(route('jugadores.show', $jugador))->assertForbidden();

        $this->actingAs($sinPerfil)->get(route('mi-cuenta'))->assertForbidden();
        $this->get(route('mi-historial'))->assertForbidden();
    }
}
