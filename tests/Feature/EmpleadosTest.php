<?php

namespace Tests\Feature;

use App\Enums\Rol;
use App\Models\Empleado;
use App\Models\Juego;
use App\Models\Jugador;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmpleadosTest extends TestCase
{
    use RefreshDatabase;

    private function administrador(): User
    {
        $admin = User::factory()->administrador()->create();
        $this->actingAs($admin);

        return $admin;
    }

    private function datosEmpleado(array $cambios = []): array
    {
        return array_merge([
            'documento' => 'EMP-100', 'nombre_completo' => 'Ana Empleada', 'rol' => 'crupier', 'salario_mensual' => '1200.50',
            'competencias' => [Juego::where('nombre', 'Ruleta')->value('id')], 'username' => 'ana', 'password_hash' => str_repeat('a', 64),
        ], $cambios);
    }

    public function test_d05_la_migracion_crea_ruleta_blackjack_y_poker_con_su_registro_detallado(): void
    {
        $this->assertSame([
            'Ruleta' => 1, 'Blackjack' => 1, 'Póker' => 0,
        ], Juego::orderBy('id')->pluck('registro_detallado', 'nombre')->map(fn ($valor) => (int) $valor)->all());
    }

    public function test_fa_02_admin_crea_empleado_con_cuenta_y_competencias(): void
    {
        $this->administrador();
        $hash = str_repeat('b', 64);
        $response = $this->post(route('empleados.store'), $this->datosEmpleado(['password_hash' => $hash]));
        $response->assertRedirect(route('empleados.index'));
        $user = User::where('username', 'ana')->firstOrFail();
        $this->assertSame($hash, $user->password);
        $this->assertSame('crupier', $user->rol->value);
        $empleado = Empleado::where('documento', 'EMP-100')->firstOrFail();
        $this->assertSame([$this->idJuego('Ruleta')], $empleado->juegos()->pluck('juegos.id')->map(fn ($id) => (int) $id)->all());
    }

    public function test_fa_02_valida_documento_y_username_unicos_salario_positivo_y_rol_de_empleado(): void
    {
        $this->administrador();
        $this->post(route('empleados.store'), $this->datosEmpleado())->assertRedirect(route('empleados.index'));
        $this->post(route('empleados.store'), $this->datosEmpleado(['documento' => 'EMP-100', 'username' => 'otro']))->assertSessionHasErrors('documento');
        $this->post(route('empleados.store'), $this->datosEmpleado(['documento' => 'EMP-200', 'username' => 'ana']))->assertSessionHasErrors('username');
        $this->post(route('empleados.store'), $this->datosEmpleado(['documento' => 'EMP-300', 'username' => 'bajo', 'salario_mensual' => '0']))->assertSessionHasErrors('salario_mensual');
        $this->post(route('empleados.store'), $this->datosEmpleado(['documento' => 'EMP-400', 'username' => 'roljug', 'rol' => 'jugador']))->assertSessionHasErrors('rol');
    }

    public function test_fa_02_rechaza_contrasena_que_no_es_sha256_hex(): void
    {
        $this->administrador();
        $this->post(route('empleados.store'), $this->datosEmpleado(['password_hash' => 'clave en claro']))
            ->assertSessionHasErrors('password_hash');
        $this->assertDatabaseCount('users', 1);
    }

    public function test_fa_02_editar_sin_contrasena_conserva_la_anterior_y_actualiza_competencias(): void
    {
        $this->administrador();
        $this->post(route('empleados.store'), $this->datosEmpleado());
        $empleado = Empleado::firstOrFail();
        $hash = $empleado->user->password;
        $blackjack = $this->idJuego('Blackjack');
        $this->put(route('empleados.update', $empleado), $this->datosEmpleado([
            'documento' => 'EMP-101', 'username' => 'ana2', 'competencias' => [$blackjack], 'password_hash' => '',
        ]))->assertRedirect(route('empleados.index'));
        $this->assertSame($hash, $empleado->user->fresh()->password);
        $this->assertSame('ana2', $empleado->user->fresh()->username);
        $this->assertSame([$blackjack], $empleado->fresh()->juegos()->pluck('juegos.id')->map(fn ($id) => (int) $id)->all());
    }

    public function test_d13_editar_empleado_actualiza_su_perfil_de_jugador(): void
    {
        $this->administrador();
        $this->post(route('empleados.store'), $this->datosEmpleado());
        $empleado = Empleado::firstOrFail();
        $empleado->jugador()->create(['codigo_cliente' => 'CLI-D13', 'documento' => 'EMP-100', 'nombre_completo' => 'Ana Empleada', 'edad' => 25, 'saldo' => '10.00']);
        $this->put(route('empleados.update', $empleado), $this->datosEmpleado(['documento' => 'EMP-101', 'nombre_completo' => 'Ana Nueva', 'username' => 'ana', 'password_hash' => '']))
            ->assertRedirect(route('empleados.index'));
        $this->assertDatabaseHas('jugadores', ['user_id' => $empleado->user_id, 'documento' => 'EMP-101', 'nombre_completo' => 'Ana Nueva']);
    }

    public function test_fa_02_borrar_empleado_borra_su_cuenta_y_competencias(): void
    {
        $this->administrador();
        $this->post(route('empleados.store'), $this->datosEmpleado());
        $empleado = Empleado::firstOrFail();
        $userId = $empleado->user_id;
        $this->delete(route('empleados.destroy', $empleado))->assertRedirect(route('empleados.index'));
        $this->assertDatabaseMissing('empleados', ['id' => $empleado->id]);
        $this->assertDatabaseMissing('competencias', ['empleado_id' => $empleado->id]);
        $this->assertDatabaseMissing('users', ['id' => $userId]);
    }

    public function test_fa_02_no_se_borra_a_si_mismo(): void
    {
        $admin = $this->administrador();
        $empleado = $admin->empleado()->create(['documento' => 'ADM-1', 'nombre_completo' => 'Admin', 'salario_mensual' => '100.00']);
        $this->delete(route('empleados.destroy', $empleado))->assertSessionHasErrors('empleado');
        $this->assertDatabaseHas('empleados', ['id' => $empleado->id]);
    }

    public function test_fa_02_no_se_borra_empleado_con_perfil_de_jugador(): void
    {
        $this->administrador();
        $this->post(route('empleados.store'), $this->datosEmpleado());
        $empleado = Empleado::firstOrFail();
        $empleado->jugador()->create(['codigo_cliente' => 'CLI-1', 'documento' => $empleado->documento, 'nombre_completo' => $empleado->nombre_completo, 'edad' => 30, 'saldo' => '10.00']);
        $this->from(route('empleados.index'))->delete(route('empleados.destroy', $empleado))
            ->assertSessionHasErrors(['empleado' => 'Borra antes el perfil de jugador.']);
        $this->assertDatabaseHas('empleados', ['id' => $empleado->id]);
    }

    public function test_rf_rol_02_solo_el_administrador_gestiona_empleados_y_jugadores(): void
    {
        $admin = User::factory()->administrador()->create();
        $this->actingAs($admin)->post(route('empleados.store'), $this->datosEmpleado());
        $empleado = Empleado::firstOrFail();
        $this->actingAs($admin)->post(route('jugadores.store'), [
            'codigo_cliente' => 'CLI-X', 'documento' => 'DOC-X', 'nombre_completo' => 'Jugador X', 'edad' => 25,
            'saldo' => '0', 'username' => 'jug-x', 'password_hash' => str_repeat('c', 64),
        ]);
        $jugador = Jugador::where('codigo_cliente', 'CLI-X')->firstOrFail();
        $casos = [
            ['GET', route('empleados.index')], ['GET', route('empleados.create')], ['POST', route('empleados.store')],
            ['GET', route('empleados.edit', $empleado)], ['PUT', route('empleados.update', $empleado)], ['DELETE', route('empleados.destroy', $empleado)],
            ['GET', route('jugadores.index')], ['GET', route('jugadores.create')], ['POST', route('jugadores.store')],
            ['GET', route('jugadores.edit', $jugador)], ['PUT', route('jugadores.update', $jugador)], ['DELETE', route('jugadores.destroy', $jugador)],
        ];
        foreach ([Rol::Crupier, Rol::Supervisor, Rol::Jugador] as $rol) {
            $this->actingAs(User::factory()->create(['rol' => $rol]));
            foreach ($casos as [$metodo, $url]) {
                $this->call($metodo, $url)->assertForbidden();
            }
        }
    }

    public function test_fa_00_el_menu_del_administrador_muestra_empleados_y_jugadores_y_otros_roles_no(): void
    {
        $this->actingAs(User::factory()->administrador()->create())->get(route('inicio'))->assertOk()->assertSeeText('Empleados')->assertSeeText('Jugadores');
        foreach ([Rol::Crupier, Rol::Supervisor, Rol::Jugador] as $rol) {
            $this->actingAs(User::factory()->create(['rol' => $rol]))->get(route('inicio'))->assertOk()->assertDontSeeText('Empleados')->assertDontSeeText('Jugadores');
        }
    }

    private function idJuego(string $nombre): int
    {
        return (int) Juego::where('nombre', $nombre)->value('id');
    }
}
