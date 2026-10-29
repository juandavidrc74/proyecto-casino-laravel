<?php

namespace Tests\Feature;

use App\Enums\Rol;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class RolesTest extends TestCase
{
    use RefreshDatabase;

    public function test_rf_rol_02_middleware_rechaza_otro_rol_con_403_y_permite_el_rol_correcto(): void
    {
        Route::middleware(['web', 'auth', 'rol:administrador'])->get('/prueba-rol-temporal', fn () => 'permitido');

        $this->actingAs(User::factory()->jugador()->create())
            ->get('/prueba-rol-temporal')->assertForbidden();

        $this->actingAs(User::factory()->administrador()->create())
            ->get('/prueba-rol-temporal')->assertOk()->assertSeeText('permitido');
    }

    public function test_rf_rol_01_inicio_muestra_el_rol_del_usuario(): void
    {
        foreach (Rol::cases() as $rol) {
            $user = User::factory()->create(['rol' => $rol]);

            $this->actingAs($user)
                ->get('/inicio')
                ->assertOk()
                ->assertSeeText("Sesión iniciada como {$user->username} ({$rol->etiqueta()})");
        }
    }

    public function test_seeder_sin_contrasena_demo_no_crea_usuarios(): void
    {
        config(['casino.demo_password' => '']);

        $this->artisan('db:seed', ['--class' => DatabaseSeeder::class])
            ->expectsOutputToContain('CASINO_DEMO_PASSWORD está vacío')
            ->assertExitCode(0);

        $this->assertDatabaseCount('users', 0);
    }

    public function test_seeder_con_contrasena_demo_crea_un_usuario_por_rol(): void
    {
        $demo = bin2hex(random_bytes(16));
        config(['casino.demo_password' => $demo]);

        $this->artisan('db:seed', ['--class' => DatabaseSeeder::class])->assertExitCode(0);

        $this->assertDatabaseCount('users', 9);
        $this->assertDatabaseHas('users', ['username' => 'admin', 'password' => hash('sha256', $demo), 'rol' => 'administrador']);
        $this->assertDatabaseHas('users', ['username' => 'crupier1', 'rol' => 'crupier']);
        $this->assertDatabaseHas('users', ['username' => 'crupier2', 'rol' => 'crupier']);
        $this->assertDatabaseHas('users', ['username' => 'crupier3', 'rol' => 'crupier']);
        $this->assertDatabaseHas('users', ['username' => 'supervisor1', 'rol' => 'supervisor']);
        $this->assertDatabaseHas('users', ['username' => 'supervisor2', 'rol' => 'supervisor']);
        $this->assertDatabaseHas('users', ['username' => 'jugador1', 'rol' => 'jugador']);
        $this->assertDatabaseHas('users', ['username' => 'jugador2', 'rol' => 'jugador']);
        $this->assertDatabaseHas('users', ['username' => 'jugador3', 'rol' => 'jugador']);
    }
}
