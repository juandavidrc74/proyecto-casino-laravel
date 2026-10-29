<?php

namespace Tests\Feature;

use App\Enums\Rol;
use App\Models\Jugador;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JugadoresTest extends TestCase
{
    use RefreshDatabase;

    private function administrador(): User
    {
        $admin = User::factory()->administrador()->create();
        $this->actingAs($admin);

        return $admin;
    }

    private function datosJugador(array $cambios = []): array
    {
        return array_merge([
            'codigo_cliente' => 'CLI-100', 'documento' => 'DOC-100', 'nombre_completo' => 'Luis Jugador', 'edad' => 28,
            'saldo' => '250.75', 'username' => 'luis', 'password_hash' => str_repeat('d', 64),
        ], $cambios);
    }

    public function test_rn_01_admin_registra_jugador_con_todos_los_datos(): void
    {
        $this->administrador();
        $hash = str_repeat('e', 64);
        $this->post(route('jugadores.store'), $this->datosJugador(['password_hash' => $hash]))->assertRedirect(route('jugadores.index'));
        $jugador = Jugador::where('codigo_cliente', 'CLI-100')->firstOrFail();
        $this->assertSame('DOC-100', $jugador->documento);
        $this->assertSame('Luis Jugador', $jugador->nombre_completo);
        $this->assertSame(28, $jugador->edad);
        $this->assertSame('250.75', $jugador->saldo);
        $this->assertSame($hash, $jugador->user->password);
        $this->assertSame(Rol::Jugador, $jugador->user->rol);
    }

    public function test_rn_01_codigo_y_documento_son_unicos(): void
    {
        $this->administrador();
        $this->post(route('jugadores.store'), $this->datosJugador());
        $this->post(route('jugadores.store'), $this->datosJugador(['codigo_cliente' => 'CLI-100', 'documento' => 'DOC-200', 'username' => 'otro']))
            ->assertSessionHasErrors('codigo_cliente');
        $this->post(route('jugadores.store'), $this->datosJugador(['codigo_cliente' => 'CLI-200', 'documento' => 'DOC-100', 'username' => 'otro']))
            ->assertSessionHasErrors('documento');
    }

    public function test_rn_01_campos_obligatorios(): void
    {
        $this->administrador();
        $this->post(route('jugadores.store'), [])->assertSessionHasErrors([
            'codigo_cliente', 'documento', 'nombre_completo', 'edad', 'saldo', 'username', 'password_hash',
        ]);
    }

    public function test_rn_02_rechaza_menor_de_18_y_acepta_18(): void
    {
        $this->administrador();
        $this->post(route('jugadores.store'), $this->datosJugador(['edad' => 17]))->assertSessionHasErrors('edad');
        $this->post(route('jugadores.store'), $this->datosJugador(['edad' => 18]))->assertRedirect(route('jugadores.index'));
        $this->assertDatabaseHas('jugadores', ['codigo_cliente' => 'CLI-100', 'edad' => 18]);
    }

    public function test_d12_editar_jugador_no_cambia_el_saldo(): void
    {
        $this->administrador();
        $this->post(route('jugadores.store'), $this->datosJugador());
        $jugador = Jugador::firstOrFail();
        $this->put(route('jugadores.update', $jugador), [
            'codigo_cliente' => 'CLI-101', 'documento' => 'DOC-101', 'nombre_completo' => 'Luis Actualizado', 'edad' => 29,
            'username' => 'luis', 'password_hash' => '', 'saldo' => '99999.99',
        ])->assertRedirect(route('jugadores.index'));
        $this->assertSame('250.75', $jugador->fresh()->saldo);
    }

    public function test_rn_16_empleado_puede_tener_perfil_de_jugador_con_su_misma_cuenta(): void
    {
        $this->administrador();
        $user = User::factory()->crupier()->create(['username' => 'empleado1']);
        $empleado = $user->empleado()->create(['documento' => 'EMP-16', 'nombre_completo' => 'Empleado Vinculado', 'salario_mensual' => '1500.00']);
        $this->post(route('jugadores.store'), ['empleado_id' => $empleado->id, 'codigo_cliente' => 'CLI-16', 'edad' => 40, 'saldo' => '80.00'])
            ->assertRedirect(route('jugadores.index'));
        $jugador = Jugador::where('codigo_cliente', 'CLI-16')->firstOrFail();
        $this->assertSame($user->id, $jugador->user_id);
        $this->assertSame('EMP-16', $jugador->documento);
        $this->assertSame('Empleado Vinculado', $jugador->nombre_completo);
        $this->assertSame('empleado1', $jugador->user->username);
        $this->assertSame(Rol::Crupier, $jugador->user->rol);
        $hashAnterior = $jugador->user->password;
        $this->put(route('jugadores.update', $jugador), [
            'codigo_cliente' => 'CLI-16B', 'edad' => 41, 'username' => 'cuenta-alterada',
            'password_hash' => str_repeat('f', 64), 'documento' => 'DOC-ALTERADO', 'nombre_completo' => 'Nombre alterado',
        ])->assertRedirect(route('jugadores.index'));
        $this->assertSame('empleado1', $jugador->user->fresh()->username);
        $this->assertSame($hashAnterior, $jugador->user->fresh()->password);
        $this->assertSame('EMP-16', $jugador->fresh()->documento);
        $this->assertSame('Empleado Vinculado', $jugador->fresh()->nombre_completo);
    }

    public function test_rn_16_un_usuario_no_tiene_dos_perfiles_de_jugador(): void
    {
        $this->administrador();
        $user = User::factory()->supervisor()->create();
        $empleado = $user->empleado()->create(['documento' => 'EMP-17', 'nombre_completo' => 'Empleado', 'salario_mensual' => '900.00']);
        $payload = ['empleado_id' => $empleado->id, 'codigo_cliente' => 'CLI-17', 'edad' => 24, 'saldo' => '0.00'];
        $this->post(route('jugadores.store'), $payload)->assertRedirect(route('jugadores.index'));
        $this->post(route('jugadores.store'), array_merge($payload, ['codigo_cliente' => 'CLI-18']))->assertSessionHasErrors('empleado_id');
        $this->assertDatabaseCount('jugadores', 1);
    }

    public function test_rn_16_documento_de_empleado_ya_usado_por_otro_jugador_da_error_de_validacion(): void
    {
        $this->administrador();
        $this->post(route('jugadores.store'), $this->datosJugador(['documento' => 'EMP-X']));
        $user = User::factory()->crupier()->create();
        $empleado = $user->empleado()->create(['documento' => 'EMP-X', 'nombre_completo' => 'Empleado X', 'salario_mensual' => '900.00']);
        $this->post(route('jugadores.store'), ['empleado_id' => $empleado->id, 'codigo_cliente' => 'CLI-EMP-X', 'edad' => 30, 'saldo' => '0.00'])
            ->assertSessionHasErrors('empleado_id');
        $this->assertDatabaseCount('jugadores', 1);
    }
}
