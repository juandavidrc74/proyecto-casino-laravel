<?php

namespace Tests\Feature;

use App\Enums\Concepto;
use App\Enums\EstadoApuesta;
use App\Enums\MotivoSuspension;
use App\Models\Juego;
use App\Models\Turno;
use App\Models\User;
use App\Notifications\CuentaReactivada;
use App\Services\AutorizacionJugadorService;
use App\Services\ResultadoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Feature\Concerns\CreaCasino;
use Tests\TestCase;

class NotificacionesTest extends TestCase
{
    use CreaCasino, RefreshDatabase;

    public function test_fa_01_resolver_apuesta_notifica_al_jugador(): void
    {
        $this->travelTo(now()->setDate(2026, 10, 29)->setTime(9, 0));
        $contexto = $this->contexto();
        $jugador = $this->crearJugador();
        $apuesta = $this->crearApuesta($contexto['ronda'], $jugador);

        app(ResultadoService::class)->resolver($apuesta, EstadoApuesta::Ganada);

        $notificacion = $jugador->user->notifications()->firstOrFail();
        $this->assertSame('Ganada', $notificacion->data['estado']);
        $this->assertSame('15.00', $notificacion->data['premio']);
        $this->assertSame('1005.00', $notificacion->data['saldo_resultante']);
        $this->assertSame('M1', $notificacion->data['mesa']);
        $this->assertSame(1, $notificacion->data['ronda']);
        $this->assertSame('Rojo', $notificacion->data['tipo']);
        $this->assertSame('10.00', $notificacion->data['monto']);
    }

    public function test_fa_01_suspension_automatica_notifica_al_jugador_y_a_los_administradores(): void
    {
        config(['casino.umbral_perdida_diaria' => '100.00']);
        $admin = User::factory()->administrador()->create();
        $otroAdmin = User::factory()->administrador()->create();
        $jugador = $this->crearJugador();
        $apuesta = $this->crearApuesta($this->contexto()['ronda'], $jugador, 'Rojo', '100.01', '1.00', EstadoApuesta::Perdida);
        $apuesta->update(['resuelta_en' => now(), 'premio' => '0.00']);

        app(AutorizacionJugadorService::class)->evaluarTrasApuesta($jugador);

        $this->assertSame(1, $jugador->user->notifications()->count());
        $this->assertSame(1, $admin->notifications()->count());
        $this->assertSame(1, $otroAdmin->notifications()->count());
        $this->assertSame('Pérdida diaria', $jugador->user->notifications()->first()->data['motivo']);
        $this->assertSame('Pérdida diaria', $admin->notifications()->first()->data['motivo']);
    }

    public function test_fa_01_pausa_voluntaria_no_notifica(): void
    {
        $admin = User::factory()->administrador()->create();
        $jugador = $this->crearJugador();

        app(AutorizacionJugadorService::class)->solicitarPausa($jugador);

        $this->assertSame(0, $jugador->user->notifications()->count());
        $this->assertSame(0, $admin->notifications()->count());
    }

    public function test_fa_01_abono_que_reactiva_notifica_al_jugador(): void
    {
        $empleado = $this->crearEmpleado('crupier', 'E1', Juego::firstOrFail());
        $jugador = $empleado->user->jugador()->create(['codigo_cliente' => 'JE1', 'documento' => 'E1', 'nombre_completo' => 'Empleado', 'edad' => 30, 'saldo' => '100.00']);
        $apuesta = $this->crearApuesta($this->contexto()['ronda'], $jugador, 'Rojo', '250.00', '1.00', EstadoApuesta::Perdida);
        $apuesta->update(['resuelta_en' => now(), 'premio' => '0.00']);
        $jugador->suspensiones()->create(['motivo' => MotivoSuspension::PerdidaEmpleado, 'inicio' => now()->subHour()]);

        app(AutorizacionJugadorService::class)->registrarAbono($jugador, '50.00');

        $this->assertSame(1, $jugador->user->notifications()->count());
        $this->assertStringContainsString('reactivada', $jugador->user->notifications()->first()->data['mensaje']);
    }

    public function test_fa_01_finalizar_investigacion_notifica_al_jugador(): void
    {
        $jugador = $this->crearJugador();
        $suspension = $jugador->suspensiones()->create(['motivo' => MotivoSuspension::Investigacion, 'inicio' => now()->subHour()]);
        $admin = User::factory()->administrador()->create();

        $this->actingAs($admin)->post(route('suspensiones.finalizar', $suspension))->assertRedirect();

        $this->assertSame(1, $jugador->user->notifications()->count());
    }

    public function test_fa_01_concepto_sospecha_de_fraude_notifica_a_los_administradores(): void
    {
        [$supervisor, $asignacion] = $this->asignacionConcepto();
        $admin = User::factory()->administrador()->create();
        $otroAdmin = User::factory()->administrador()->create();

        $this->actingAs($supervisor)->post(route('conceptos.store', $asignacion), ['concepto' => Concepto::SospechaFraude->value])->assertRedirect();

        $this->assertSame('M1', $admin->notifications()->firstOrFail()->data['mesa']);
        $this->assertSame('Crupier', $admin->notifications()->first()->data['crupier']);
        $this->assertSame('Supervisor', $admin->notifications()->first()->data['supervisor']);
        $this->assertSame(1, $otroAdmin->notifications()->count());
    }

    public function test_fa_01_otro_concepto_no_notifica(): void
    {
        [$supervisor, $asignacion] = $this->asignacionConcepto();
        $admin = User::factory()->administrador()->create();

        $this->actingAs($supervisor)->post(route('conceptos.store', $asignacion), ['concepto' => Concepto::GeneraGanancias->value])->assertRedirect();

        $this->assertSame(0, $admin->notifications()->count());
    }

    public function test_fa_01_marcar_como_leida_y_todas(): void
    {
        $user = User::factory()->jugador()->create();
        $user->notify(new CuentaReactivada('Aviso uno.'));
        $user->notify(new CuentaReactivada('Aviso dos.'));
        $notificacion = $user->notifications()->oldest()->firstOrFail();

        $this->actingAs($user)->post(route('notificaciones.leer', $notificacion->id))->assertRedirect();
        $this->assertNotNull($notificacion->fresh()->read_at);
        $this->post(route('notificaciones.marcar-todas'))->assertRedirect();
        $this->assertSame(0, $user->unreadNotifications()->count());
    }

    public function test_fa_01_no_marca_notificaciones_ajenas(): void
    {
        $propietario = User::factory()->jugador()->create();
        $ajeno = User::factory()->jugador()->create();
        $propietario->notify(new CuentaReactivada('Aviso.'));
        $notificacion = $propietario->notifications()->firstOrFail();

        $this->actingAs($ajeno)->post(route('notificaciones.leer', $notificacion->id))->assertNotFound();
        $this->assertNull($notificacion->fresh()->read_at);
    }

    public function test_fa_01_la_cabecera_muestra_el_numero_de_no_leidas(): void
    {
        $user = User::factory()->jugador()->create();
        $user->notify(new CuentaReactivada('Aviso.'));

        $this->actingAs($user)->get(route('inicio'))->assertOk()->assertSee('notification-count')->assertSee('1', false);
    }

    private function contexto(): array
    {
        $juego = Juego::where('registro_detallado', true)->firstOrFail();
        $crupier = $this->crearEmpleado('crupier', 'C1', $juego);
        $supervisor = $this->crearEmpleado('supervisor', 'S1', $juego);
        $mesa = $this->crearMesa($juego);
        $ronda = $this->crearRonda($this->crearAsignacion($this->crearTurno(), $mesa, $crupier, $supervisor), 1);

        return compact('ronda');
    }

    private function asignacionConcepto(): array
    {
        $this->travelTo(now()->setDate(2026, 10, 29)->setTime(10, 0));
        $juego = Juego::firstOrFail();
        $crupier = $this->crearEmpleado('crupier', 'C1', $juego);
        $supervisorEmpleado = $this->crearEmpleado('supervisor', 'S1', $juego);
        $crupier->update(['nombre_completo' => 'Crupier']);
        $supervisorEmpleado->update(['nombre_completo' => 'Supervisor']);
        $mesa = $this->crearMesa($juego);
        $turno = Turno::create(['fecha' => '2026-10-29', 'hora_inicio' => '08:00', 'hora_fin' => '10:00']);
        $asignacion = $this->crearAsignacion($turno, $mesa, $crupier, $supervisorEmpleado);

        return [$supervisorEmpleado->user, $asignacion];
    }
}
