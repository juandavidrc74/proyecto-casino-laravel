<?php

namespace Database\Seeders;

use App\Enums\Concepto;
use App\Enums\EstadoApuesta;
use App\Enums\Rol;
use App\Models\Empleado;
use App\Models\Juego;
use App\Models\Jugador;
use App\Models\Mesa;
use App\Models\Turno;
use App\Models\User;
use App\Notifications\ConceptoSospechaFraude;
use App\Services\ApuestaService;
use App\Services\AsignacionService;
use App\Services\AutorizacionJugadorService;
use App\Services\ResultadoService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $demo = config('casino.demo_password', env('CASINO_DEMO_PASSWORD'));

        if (! is_string($demo) || $demo === '') {
            $this->command->warn('CASINO_DEMO_PASSWORD está vacío; no se crearon usuarios de demostración.');

            return;
        }

        $password = hash('sha256', $demo);
        $this->usuario('admin', Rol::Administrador, $password);
        $empleados = $this->crearEmpleados($password);
        $jugadores = $this->crearJugadores($password, $empleados['crupier3']);
        $juegos = Juego::all()->keyBy('nombre');
        $mesas = $this->crearMesas($juegos);
        $ahora = now(config('app.timezone'));
        $ayer = $ahora->copy()->subDay();

        $this->crearDeudaEmpleado($ayer, $empleados, $jugadores['crupier3'], $mesas['P-01']);
        $this->crearHistorial($ayer, $empleados, $jugadores, $mesas);
        $turnoActual = $this->crearTurnoActual(
            $ahora,
            $empleados,
            $jugadores['jugador1'],
            $jugadores['jugador3'],
            $mesas,
        );
        app(AutorizacionJugadorService::class)->solicitarPausa($jugadores['jugador2']);

        $this->command->info('Usuarios: admin, crupier1, crupier2, crupier3, supervisor1, supervisor2, jugador1, jugador2, jugador3 (sin contraseñas).');
        $this->command->info('Mesas: R-01, B-01, P-01. Turno en curso: '.$turnoActual->fecha.' '.$turnoActual->hora_inicio.'–'.$turnoActual->hora_fin.'.');
        $this->command->info('Estados: jugador1 '.$jugadores['jugador1']->fresh()->estado().'; jugador2 '.$jugadores['jugador2']->fresh()->estado().'; jugador3 '.$jugadores['jugador3']->fresh()->estado().'; perfil crupier3 '.$jugadores['crupier3']->fresh()->estado().'.');
    }

    private function usuario(string $username, Rol $rol, string $password): User
    {
        return User::updateOrCreate(
            ['username' => $username],
            ['password' => $password, 'rol' => $rol],
        );
    }

    private function crearEmpleados(string $password): array
    {
        $juegos = Juego::all()->keyBy('nombre');
        $definiciones = [
            'crupier1' => ['Crupier Uno', '3000.00', Rol::Crupier, ['Ruleta', 'Blackjack']],
            'crupier2' => ['Crupier Dos', '3000.00', Rol::Crupier, ['Blackjack', 'Póker']],
            'crupier3' => ['Crupier Tres', '1000.00', Rol::Crupier, ['Ruleta', 'Póker']],
            'supervisor1' => ['Supervisora Uno', '4000.00', Rol::Supervisor, ['Ruleta', 'Blackjack', 'Póker']],
            'supervisor2' => ['Supervisor Dos', '4000.00', Rol::Supervisor, ['Ruleta', 'Blackjack']],
        ];
        $empleados = [];

        foreach ($definiciones as $username => [$nombre, $salario, $rol, $competencias]) {
            $user = $this->usuario($username, $rol, $password);
            $empleado = Empleado::updateOrCreate(
                ['user_id' => $user->id],
                ['documento' => 'EMP-'.$username, 'nombre_completo' => $nombre, 'salario_mensual' => $salario],
            );
            $empleado->juegos()->sync(collect($competencias)->map(fn (string $juego) => $juegos[$juego]->id));
            $empleados[$username] = $empleado;
        }

        return $empleados;
    }

    private function crearJugadores(string $password, Empleado $crupier3): array
    {
        $definiciones = [
            'jugador1' => ['CLI-001', '1010000001', 'Ana Jugadora', '1000.00'],
            'jugador2' => ['CLI-002', '1010000002', 'Bruno Jugador', '5000.00'],
            'jugador3' => ['CLI-003', '1010000003', 'Carla Jugadora', '1500.00'],
        ];
        $jugadores = [];

        foreach ($definiciones as $username => [$codigo, $documento, $nombre, $saldo]) {
            $user = $this->usuario($username, Rol::Jugador, $password);
            $jugadores[$username] = Jugador::updateOrCreate(
                ['user_id' => $user->id],
                ['codigo_cliente' => $codigo, 'documento' => $documento, 'nombre_completo' => $nombre, 'edad' => 30, 'saldo' => $saldo],
            );
        }

        $jugadores['crupier3'] = Jugador::updateOrCreate(
            ['user_id' => $crupier3->user_id],
            ['codigo_cliente' => 'EMP-CLI-003', 'documento' => $crupier3->documento, 'nombre_completo' => $crupier3->nombre_completo, 'edad' => 30, 'saldo' => '3000.00'],
        );

        return $jugadores;
    }

    private function crearMesas($juegos): array
    {
        $definiciones = [
            'R-01' => ['Ruleta', '10.00', '500.00'],
            'B-01' => ['Blackjack', '20.00', '1000.00'],
            'P-01' => ['Póker', '50.00', '2000.00'],
        ];
        $mesas = [];

        foreach ($definiciones as $codigo => [$juego, $minimo, $maximo]) {
            $mesas[$codigo] = Mesa::updateOrCreate(
                ['codigo' => $codigo],
                ['juego_id' => $juegos[$juego]->id, 'apuesta_minima' => $minimo, 'apuesta_maxima' => $maximo],
            );
        }

        return $mesas;
    }

    private function crearHistorial(Carbon $ayer, array $empleados, array $jugadores, array $mesas): void
    {
        $turnos = [
            ['10:00:00', '12:00:00'],
            ['12:00:00', '14:00:00'],
        ];
        $crupierPorTurno = [
            ['crupier1', 'crupier2', 'crupier3'],
            ['crupier3', 'crupier1', 'crupier2'],
        ];
        $supervisorIds = ['supervisor1', 'supervisor2'];

        foreach ($turnos as $indice => [$inicio, $fin]) {
            $momento = $ayer->copy()->setTimeFromTimeString($inicio)->addMinutes(5);
            Carbon::setTestNow($momento);
            $turno = Turno::create(['fecha' => $ayer->toDateString(), 'hora_inicio' => $inicio, 'hora_fin' => $fin]);
            $asignaciones = [];

            foreach (array_values($mesas) as $mesaIndice => $mesa) {
                $crupier = $empleados[$crupierPorTurno[$indice][$mesaIndice]];
                $supervisor = $mesa->codigo === 'P-01'
                    ? $empleados['supervisor1']
                    : $empleados[$supervisorIds[($mesaIndice + $indice) % count($supervisorIds)]];
                $asignaciones[$mesa->codigo] = app(AsignacionService::class)->crear($turno, [
                    'mesa_id' => $mesa->id,
                    'crupier_id' => $crupier->id,
                    'supervisor_id' => $supervisor->id,
                ]);
            }

            $this->crearRondaCerrada($mesas['R-01'], $asignaciones['R-01'], $momento->copy()->addMinutes(1), [
                [$jugadores['jugador1'], 'Rojo', '100.00', '2.00', true],
                [$jugadores['jugador2'], 'Negro', '100.00', '2.00', false],
            ], 'Rojo');

            $apuestasBlackjack = [
                [$jugadores['jugador1'], '17', '50.00', '2.00', false],
            ];
            if ($indice === 0) {
                $apuestasBlackjack[] = [$jugadores['crupier3'], '21', '600.00', '7.00', true];
            }
            $this->crearRondaCerrada(
                $mesas['B-01'],
                $asignaciones['B-01'],
                $momento->copy()->addMinutes(2),
                $apuestasBlackjack,
                '21',
            );

            Carbon::setTestNow($ayer->copy()->setTimeFromTimeString($fin)->addMinute());
            foreach ($asignaciones as $codigo => $asignacion) {
                $concepto = $indice === 0 && $codigo === 'R-01' ? Concepto::SospechaFraude : Concepto::ComportamientoNeutro;
                $asignacion->update(['concepto' => $concepto, 'concepto_registrado_en' => now(config('app.timezone'))]);
                if ($concepto === Concepto::SospechaFraude) {
                    $datos = [
                        'mensaje' => 'Se registró una sospecha de fraude en la mesa R-01.',
                        'mesa' => 'R-01',
                        'fecha' => $turno->fecha,
                        'turno' => $turno->hora_inicio.'–'.$turno->hora_fin,
                        'crupier' => $asignacion->crupier->nombre_completo,
                        'supervisor' => $asignacion->supervisor->nombre_completo,
                    ];
                    foreach (User::where('rol', 'administrador')->get() as $admin) {
                        $admin->notify(new ConceptoSospechaFraude($datos));
                    }
                }
            }
        }

        Carbon::setTestNow();
    }

    private function crearDeudaEmpleado(Carbon $ayer, array $empleados, Jugador $jugador, Mesa $mesa): void
    {
        foreach ([4, 3, 2] as $diasAntes) {
            $dia = $ayer->copy()->subDays($diasAntes);
            $momento = $dia->copy()->setTime(10, 5);
            Carbon::setTestNow($momento);
            $turno = Turno::create([
                'fecha' => $dia->toDateString(),
                'hora_inicio' => '10:00:00',
                'hora_fin' => '12:00:00',
            ]);
            $asignacion = app(AsignacionService::class)->crear($turno, [
                'mesa_id' => $mesa->id,
                'crupier_id' => $empleados['crupier2']->id,
                'supervisor_id' => $empleados['supervisor1']->id,
            ]);
            $this->crearRondaPoker($mesa, $asignacion, $momento->copy()->addMinute(), $jugador, '800.00');
        }

        Carbon::setTestNow($ayer->copy()->subDays(2)->setTime(12, 1));
        app(AutorizacionJugadorService::class)->registrarAbono($jugador, '500.00');
    }

    private function crearRondaCerrada(Mesa $mesa, $asignacion, Carbon $momento, array $apuestas, string $resultado): void
    {
        Carbon::setTestNow($momento);
        $ronda = $mesa->rondas()->create([
            'asignacion_id' => $asignacion->id,
            'numero' => (int) $mesa->rondas()->max('numero') + 1,
            'hora_inicio' => $momento,
        ]);

        foreach ($apuestas as [$jugador, $tipo, $monto, $factor]) {
            app(ApuestaService::class)->registrar($ronda, [
                'codigo_cliente' => $jugador->codigo_cliente,
                'tipo_apuesta' => $tipo,
                'monto' => $monto,
                'factor_pago' => $factor,
            ]);
        }

        app(ResultadoService::class)->cerrar($ronda, $resultado, array_map(
            fn (array $apuesta) => $apuesta[4] ? $apuesta[1] : null,
            array_filter($apuestas, fn (array $apuesta) => $apuesta[4]),
        ));
    }

    private function crearRondaPoker(Mesa $mesa, $asignacion, Carbon $momento, Jugador $jugador, string $monto): void
    {
        Carbon::setTestNow($momento);
        $ronda = $mesa->rondas()->create([
            'asignacion_id' => $asignacion->id,
            'numero' => (int) $mesa->rondas()->max('numero') + 1,
            'hora_inicio' => $momento,
        ]);
        app(ResultadoService::class)->cerrar($ronda, 'Mesa terminada');
        app(ApuestaService::class)->registrar($ronda, [
            'codigo_cliente' => $jugador->codigo_cliente,
            'tipo_apuesta' => 'Mano perdida',
            'monto' => $monto,
            'factor_pago' => '1.00',
            'estado' => EstadoApuesta::Perdida->value,
        ]);
    }

    private function crearTurnoActual(
        Carbon $ahora,
        array $empleados,
        Jugador $jugador,
        Jugador $jugadorConPerdidaDiaria,
        array $mesas,
    ): Turno {
        $inicio = $ahora->copy()->startOfHour();
        if ($ahora->diffInMinutes($inicio->copy()->endOfDay()) < 30) {
            $inicio = $ahora->copy()->setTime(22, 0);
        }
        $fin = $inicio->copy()->addHours(2);
        if ($fin->toDateString() !== $inicio->toDateString()) {
            $fin = $inicio->copy()->setTime(23, 59, 59);
        }
        $turno = Turno::create([
            'fecha' => $inicio->toDateString(),
            'hora_inicio' => $inicio->format('H:i:s'),
            'hora_fin' => $fin->format('H:i:s'),
        ]);
        $crupiers = ['crupier1', 'crupier2', 'crupier3'];
        $supervisores = ['supervisor1', 'supervisor2', 'supervisor1'];

        foreach (array_values($mesas) as $indice => $mesa) {
            app(AsignacionService::class)->crear($turno, [
                'mesa_id' => $mesa->id,
                'crupier_id' => $empleados[$crupiers[$indice]]->id,
                'supervisor_id' => $empleados[$supervisores[$indice]]->id,
            ]);
        }

        $asignacionRuleta = $turno->asignaciones()->where('mesa_id', $mesas['R-01']->id)->firstOrFail();
        Carbon::setTestNow($ahora);
        $ronda = $mesas['R-01']->rondas()->create([
            'asignacion_id' => $asignacionRuleta->id,
            'numero' => (int) $mesas['R-01']->rondas()->max('numero') + 1,
            'hora_inicio' => $ahora,
        ]);
        app(ApuestaService::class)->registrar($ronda, [
            'codigo_cliente' => $jugador->codigo_cliente,
            'tipo_apuesta' => 'Rojo',
            'monto' => '50.00',
            'factor_pago' => '2.00',
        ]);

        $mesaPoker = $mesas['P-01'];
        $asignacionPoker = $turno->asignaciones()->where('mesa_id', $mesaPoker->id)->firstOrFail();
        $perdida = $this->montoParaSuperarUmbral($jugadorConPerdidaDiaria, $mesaPoker);
        $this->crearRondaPoker($mesaPoker, $asignacionPoker, $ahora, $jugadorConPerdidaDiaria, $perdida);
        Carbon::setTestNow($ahora);

        return $turno;
    }

    private function montoParaSuperarUmbral(Jugador $jugador, Mesa $mesa): string
    {
        $umbral = (string) config('casino.umbral_perdida_diaria');
        $monto = bcmul($umbral, '1.10', 2);
        if (bccomp($monto, $mesa->apuesta_minima, 2) < 0) {
            $monto = $mesa->apuesta_minima;
        }

        $limite = bccomp($mesa->apuesta_maxima, $jugador->saldo, 2) < 0
            ? $mesa->apuesta_maxima
            : $jugador->saldo;
        if (bccomp($monto, $limite, 2) > 0) {
            $monto = $limite;
        }
        if (bccomp($monto, $umbral, 2) <= 0
            || bccomp($monto, $mesa->apuesta_minima, 2) < 0
            || bccomp($monto, $mesa->apuesta_maxima, 2) > 0) {
            throw new \RuntimeException('CASINO_UMBRAL_PERDIDA_DIARIA no se puede superar con el saldo de jugador3 y los límites de P-01.');
        }

        return $monto;
    }
}
