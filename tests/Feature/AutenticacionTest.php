<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class AutenticacionTest extends TestCase
{
    use RefreshDatabase;

    private function respuesta(string $clave, string $nonce): string
    {
        return hash('sha256', hash('sha256', $clave).$nonce);
    }

    public function test_rf_aut_01_formulario_de_login_entrega_nonce_y_no_envia_la_contrasena(): void
    {
        $response = $this->get('/login')->assertOk();
        $nonce = session('login_nonce');

        $this->assertIsString($nonce);
        $this->assertSame(64, strlen($nonce));
        $response->assertSee($nonce);
        $response->assertDontSee('name="password"');
    }

    public function test_rf_aut_04_login_correcto_con_la_respuesta_al_desafio(): void
    {
        $user = User::factory()->create(['username' => 'ana', 'password' => hash('sha256', 'secreto')]);
        $nonce = str_repeat('a', 64);

        $this->withSession(['login_nonce' => $nonce])
            ->post('/login', ['username' => 'ana', 'respuesta' => $this->respuesta('secreto', $nonce)])
            ->assertRedirect(route('inicio'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_rf_aut_04_rechaza_nonce_reutilizado(): void
    {
        User::factory()->create(['username' => 'ana', 'password' => hash('sha256', 'secreto')]);
        $nonce = str_repeat('b', 64);
        $payload = ['username' => 'ana', 'respuesta' => $this->respuesta('secreto', $nonce)];

        $this->withSession(['login_nonce' => $nonce])->post('/login', $payload);
        $this->assertNull(session('login_nonce'));
        Auth::logout();

        $this->post('/login', $payload)
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('login');
    }

    public function test_rf_aut_04_rechaza_contrasena_en_claro_y_hash_sin_nonce(): void
    {
        User::factory()->create(['username' => 'ana', 'password' => hash('sha256', 'secreto')]);
        $nonce = str_repeat('c', 64);

        foreach (['secreto', hash('sha256', 'secreto')] as $respuestaIncorrecta) {
            $response = $this->withSession(['login_nonce' => $nonce])
                ->post('/login', ['username' => 'ana', 'respuesta' => $respuestaIncorrecta])
                ->assertRedirect(route('login'));

            $response->assertSessionHasErrors($respuestaIncorrecta === 'secreto' ? 'respuesta' : 'login');
        }
    }

    public function test_rf_aut_04_falla_sin_nonce_en_sesion(): void
    {
        User::factory()->create(['username' => 'ana', 'password' => hash('sha256', 'secreto')]);

        $this->post('/login', ['username' => 'ana', 'respuesta' => str_repeat('0', 64)])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('login');
    }

    public function test_rf_aut_01_rechaza_usuario_inexistente_y_respuesta_incorrecta(): void
    {
        User::factory()->create(['username' => 'existente']);
        $nonce = str_repeat('d', 64);
        $this->withSession(['login_nonce' => $nonce])
            ->post('/login', ['username' => 'no-existe', 'respuesta' => str_repeat('0', 64)])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors(['login' => 'Usuario o contraseña incorrectos.'])
            ->assertSessionHas('_old_input.username', 'no-existe');

        $this->withSession(['login_nonce' => $nonce])
            ->post('/login', ['username' => 'existente', 'respuesta' => str_repeat('0', 64)])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors(['login' => 'Usuario o contraseña incorrectos.']);

        $this->assertGuest();
    }

    public function test_rf_aut_02_rutas_protegidas_exigen_sesion(): void
    {
        $this->get('/inicio')->assertRedirect(route('login'));
    }

    public function test_rf_aut_02_login_regenera_el_id_de_sesion(): void
    {
        User::factory()->create(['username' => 'ana', 'password' => hash('sha256', 'secreto')]);
        $this->get('/login');
        $sessionId = session()->getId();
        $nonce = str_repeat('e', 64);

        $this->withSession(['login_nonce' => $nonce])
            ->post('/login', ['username' => 'ana', 'respuesta' => $this->respuesta('secreto', $nonce)])
            ->assertRedirect(route('inicio'));

        $this->assertNotSame($sessionId, session()->getId());
    }

    public function test_rf_aut_02_logout_cierra_la_sesion(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/logout')->assertRedirect(route('login'));

        $this->assertGuest();
        $this->get('/inicio')->assertRedirect(route('login'));
    }

    public function test_rf_aut_03_la_base_guarda_exactamente_sha256_hex(): void
    {
        $hash = hash('sha256', 'x');
        $user = User::factory()->create(['password' => $hash]);

        $this->assertSame($hash, $user->fresh()->password);
        $this->assertSame(64, strlen($user->fresh()->password));
        $this->assertSame($hash, $this->app['db']->table('users')->where('id', $user->id)->value('password'));
    }
}
