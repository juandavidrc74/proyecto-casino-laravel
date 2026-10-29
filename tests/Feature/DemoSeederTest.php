<?php

namespace Tests\Feature;

use App\Enums\EstadoApuesta;
use App\Enums\MotivoSuspension;
use App\Models\Asignacion;
use App\Models\Jugador;
use App\Models\Turno;
use App\Models\User;
use App\Notifications\ConceptoSospechaFraude;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class DemoSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_en_03_sin_contrasena_demo_no_crea_nada(): void
    {
        config(['casino.demo_password' => '']);

        $this->artisan('db:seed', ['--class' => DatabaseSeeder::class])
            ->expectsOutputToContain('CASINO_DEMO_PASSWORD está vacío')
            ->assertExitCode(0);

        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('mesas', 0);
        $this->assertDatabaseCount('turnos', 0);
    }

    #[DataProvider('horasActuales')]
    public function test_en_03_crea_cuentas_mesas_y_turno_en_curso(string $hora): void
    {
        $demo = bin2hex(random_bytes(16));
        config(['casino.demo_password' => $demo]);
        config(['casino.umbral_perdida_diaria' => '1000.00']);
        $ahora = now(config('app.timezone'))->setTimeFromTimeString($hora);
        $this->travelTo($ahora);
        $this->seed(DatabaseSeeder::class);

        $turno = Turno::whereDate('fecha', $ahora->toDateString())
            ->where('hora_inicio', '<=', $ahora->format('H:i:s'))
            ->where('hora_fin', '>', $ahora->format('H:i:s'))
            ->first();

        $this->assertNotNull($turno, "No hay turno en curso para {$hora}.");
        $this->assertLessThanOrEqual(120, $turno->inicio()->diffInMinutes($turno->fin()));
        $this->assertSame($turno->fecha, $turno->fin()->toDateString());
        $this->assertSame(9, User::count());
        $this->assertDatabaseCount('mesas', 3);
        $this->assertDatabaseHas('users', ['username' => 'admin', 'password' => hash('sha256', $demo)]);
    }

    public static function horasActuales(): array
    {
        return [['09:15'], ['23:40'], ['00:10']];
    }

    public function test_en_03_los_datos_cumplen_las_reglas(): void
    {
        Notification::fake();
        config(['casino.demo_password' => bin2hex(random_bytes(16))]);
        config(['casino.umbral_perdida_diaria' => '1000.00']);
        $this->travelTo(now(config('app.timezone'))->setTime(15, 0));
        $this->seed(DatabaseSeeder::class);

        $asignaciones = Asignacion::with(['turno', 'mesa', 'crupier.juegos', 'supervisor.juegos'])->get();
        foreach ($asignaciones as $asignacion) {
            $this->assertTrue($asignacion->crupier->juegos->contains('id', $asignacion->mesa->juego_id));
            $this->assertTrue($asignacion->supervisor->juegos->contains('id', $asignacion->mesa->juego_id));
        }
        foreach ($asignaciones->groupBy(fn (Asignacion $asignacion) => $asignacion->turno_id) as $delTurno) {
            // RN-04: en un turno, ni la mesa ni el crupier se repiten.
            $this->assertCount($delTurno->count(), $delTurno->pluck('mesa_id')->unique());
            $this->assertCount($delTurno->count(), $delTurno->pluck('crupier_id')->unique());
        }
        foreach ($asignaciones->groupBy(fn (Asignacion $asignacion) => $asignacion->turno->fecha.'-'.$asignacion->crupier_id) as $porDiaYCrupier) {
            $this->assertCount($porDiaYCrupier->count(), $porDiaYCrupier->pluck('mesa_id')->unique());
        }

        $saldosIniciales = [
            'CLI-001' => '1000.00',
            'CLI-002' => '5000.00',
            'CLI-003' => '1500.00',
            'EMP-CLI-003' => '3000.00',
        ];
        foreach ($saldosIniciales as $codigo => $saldoInicial) {
            $jugador = Jugador::where('codigo_cliente', $codigo)->firstOrFail();
            $apuestas = $jugador->apuestas()->get();
            $premios = $apuestas->reduce(fn (string $total, $apuesta) => bcadd($total, $apuesta->premio, 2), '0.00');
            $montos = $apuestas->reduce(fn (string $total, $apuesta) => bcadd($total, $apuesta->monto, 2), '0.00');
            $this->assertSame(bcadd(bcsub($saldoInicial, $montos, 2), $premios, 2), $jugador->saldo);
        }

        $jugador3 = Jugador::where('codigo_cliente', 'CLI-003')->firstOrFail();
        $this->assertSame('Suspendido', $jugador3->estado());
        $this->assertDatabaseHas('apuestas', [
            'jugador_id' => $jugador3->id,
            'monto' => '1100.00',
            'estado' => EstadoApuesta::Perdida->value,
        ]);
        $suspension = $jugador3->suspensiones()->where('motivo', MotivoSuspension::PerdidaDiaria->value)->firstOrFail();
        $this->assertTrue($suspension->fin->equalTo($suspension->inicio->copy()->addHours(24)));
        $this->assertSame('Investigación', Jugador::where('codigo_cliente', 'EMP-CLI-003')->firstOrFail()->estado());
        Notification::assertSentTo(User::where('username', 'admin')->firstOrFail(), ConceptoSospechaFraude::class);
        $this->assertContains(EstadoApuesta::Pendiente, Jugador::where('codigo_cliente', 'CLI-001')->firstOrFail()->apuestas->pluck('estado')->all());
    }

    public function test_en_03_un_crupier_opera_su_mesa_en_curso_tras_el_seed(): void
    {
        config(['casino.demo_password' => bin2hex(random_bytes(16))]);
        config(['casino.umbral_perdida_diaria' => '1000.00']);
        $this->travelTo(now(config('app.timezone'))->setTime(15, 0));
        $this->seed(DatabaseSeeder::class);

        $crupier = User::where('username', 'crupier1')->firstOrFail();
        $this->actingAs($crupier)
            ->get(route('mi-mesa'))
            ->assertOk()
            ->assertSeeText('Mi mesa')
            ->assertSeeText('R-01')
            ->assertSeeText('Pendiente');
    }
}
