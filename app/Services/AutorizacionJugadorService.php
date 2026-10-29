<?php

namespace App\Services;

use App\Enums\EstadoApuesta;
use App\Enums\MotivoSuspension;
use App\Models\Jugador;
use App\Models\User;
use App\Notifications\CuentaReactivada;
use App\Notifications\CuentaSuspendida;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AutorizacionJugadorService
{
    public function motivoBloqueo(Jugador $jugador): ?string
    {
        $suspension = $this->suspensionesActivas($jugador)->orderByRaw("CASE WHEN motivo = 'investigacion' THEN 0 ELSE 1 END")
            ->orderByDesc('inicio')->first();
        if (! $suspension) {
            return null;
        }

        $motivo = $suspension->motivo->etiqueta();
        if ($suspension->fin) {
            $motivo .= ' hasta '.$suspension->fin->timezone(config('app.timezone'))->format('d/m/Y H:i');
        }

        return 'El jugador está suspendido: '.$motivo.'.';
    }

    public function perdidaNetaDelDia(Jugador $jugador, Carbon $dia): string
    {
        $inicio = $dia->copy()->timezone(config('app.timezone'))->startOfDay();
        $fin = $inicio->copy()->addDay();

        return $this->sumarApuestas($jugador, $inicio, $fin, 'monto', 'premio');
    }

    public function deudaEmpleado(Jugador $jugador): string
    {
        $abonos = $jugador->abonos()->get(['monto'])->reduce(
            fn (string $total, $abono) => bcadd($total, $abono->monto, 2),
            '0.00'
        );

        return bcsub($this->sumarApuestas($jugador, null, null, 'monto', 'premio'), $abonos, 2);
    }

    public function gananciaEmpleadoDesdeUltimaInvestigacion(Jugador $jugador): string
    {
        $ultima = $jugador->suspensiones()->where('motivo', MotivoSuspension::Investigacion->value)
            ->whereNotNull('fin')->max('fin');

        return $this->sumarApuestas($jugador, $ultima ? Carbon::parse($ultima) : null, null, 'premio', 'monto', (bool) $ultima);
    }

    public function evaluarTrasApuesta(Jugador $jugador): void
    {
        DB::transaction(function () use ($jugador) {
            $jugador = Jugador::whereKey($jugador->id)->lockForUpdate()->firstOrFail();
            $ahora = now(config('app.timezone'));
            if (bccomp($this->perdidaNetaDelDia($jugador, $ahora), (string) config('casino.umbral_perdida_diaria'), 2) > 0) {
                $this->crearSiNoActiva($jugador, MotivoSuspension::PerdidaDiaria, $ahora->copy()->addHours(24));
            }

            $empleado = $jugador->user->empleado;
            if (! $empleado) {
                return;
            }

            $limiteDeuda = bcmul($empleado->salario_mensual, '2.00', 2);
            if (bccomp($this->deudaEmpleado($jugador), $limiteDeuda, 2) > 0) {
                $this->crearSiNoActiva($jugador, MotivoSuspension::PerdidaEmpleado, null);
            }
            if (bccomp($this->gananciaEmpleadoDesdeUltimaInvestigacion($jugador), $empleado->salario_mensual, 2) > 0) {
                $this->crearSiNoActiva($jugador, MotivoSuspension::Investigacion, null);
            }
        });
    }

    public function evaluarTrasAbono(Jugador $jugador): void
    {
        DB::transaction(function () use ($jugador) {
            $jugador = Jugador::whereKey($jugador->id)->lockForUpdate()->firstOrFail();
            $empleado = $jugador->user->empleado;
            if (! $empleado || bccomp($this->deudaEmpleado($jugador), bcmul($empleado->salario_mensual, '2.00', 2), 2) > 0) {
                return;
            }

            $levantadas = $this->suspensionesActivas($jugador)->where('motivo', MotivoSuspension::PerdidaEmpleado->value)
                ->update(['fin' => now(config('app.timezone'))]);
            if ($levantadas > 0) {
                DB::afterCommit(function () use ($jugador) {
                    $jugador->user->notify(new CuentaReactivada('Un abono redujo la deuda y tu cuenta fue reactivada.'));
                });
            }
        });
    }

    public function registrarAbono(Jugador $jugador, string $monto): void
    {
        DB::transaction(function () use ($jugador, $monto) {
            $jugador = Jugador::whereKey($jugador->id)->lockForUpdate()->firstOrFail();
            if (! $jugador->user->empleado) {
                throw ValidationException::withMessages(['jugador' => 'Solo se registran abonos para empleados.']);
            }
            if (bccomp($monto, '0.00', 2) <= 0 || bccomp($monto, $this->deudaEmpleado($jugador), 2) > 0) {
                throw ValidationException::withMessages(['monto' => 'El abono debe ser mayor que cero y no superar la deuda actual.']);
            }

            $jugador->abonos()->create(['monto' => $monto]);
            $this->evaluarTrasAbono($jugador);
        });
    }

    public function solicitarPausa(Jugador $jugador): void
    {
        DB::transaction(function () use ($jugador) {
            $jugador = Jugador::whereKey($jugador->id)->lockForUpdate()->firstOrFail();
            if ($this->suspensionesActivas($jugador)->exists()) {
                throw ValidationException::withMessages(['pausa' => 'Ya tienes una suspensión activa.']);
            }

            $inicio = now(config('app.timezone'));
            $jugador->suspensiones()->create([
                'motivo' => MotivoSuspension::PausaVoluntaria,
                'inicio' => $inicio,
                'fin' => $inicio->copy()->addHours(24),
            ]);
        });
    }

    private function sumarApuestas(Jugador $jugador, ?Carbon $desde, ?Carbon $hasta, string $minuendo, string $sustraendo, bool $estrictamenteDespues = false): string
    {
        $consulta = $jugador->apuestas()->whereIn('estado', [EstadoApuesta::Ganada->value, EstadoApuesta::Perdida->value])
            ->whereNotNull('resuelta_en');
        if ($desde) {
            $consulta->where('resuelta_en', $estrictamenteDespues ? '>' : '>=', $desde->copy()->timezone(config('app.timezone')));
        }
        if ($hasta) {
            $consulta->where('resuelta_en', '<', $hasta->copy()->timezone(config('app.timezone')));
        }

        return $consulta->get(['monto', 'premio'])->reduce(
            fn (string $total, $apuesta) => bcadd($total, bcsub($apuesta->{$minuendo}, $apuesta->{$sustraendo}, 2), 2),
            '0.00'
        );
    }

    private function suspensionesActivas(Jugador $jugador)
    {
        $ahora = now(config('app.timezone'));

        return $jugador->suspensiones()->where('inicio', '<=', $ahora)
            ->where(function ($query) use ($ahora) {
                $query->whereNull('fin')->orWhere('fin', '>', $ahora);
            });
    }

    private function crearSiNoActiva(Jugador $jugador, MotivoSuspension $motivo, ?Carbon $fin): void
    {
        if (! $this->suspensionesActivas($jugador)->where('motivo', $motivo->value)->exists()) {
            $suspension = $jugador->suspensiones()->create(['motivo' => $motivo, 'inicio' => now(config('app.timezone')), 'fin' => $fin]);
            DB::afterCommit(function () use ($jugador, $suspension, $motivo) {
                $notification = new CuentaSuspendida($motivo->etiqueta(), $suspension->fin);
                $administradores = User::where('rol', 'administrador')->get();
                $jugador->user->notify($notification);
                foreach ($administradores as $administrador) {
                    $administrador->notify(new CuentaSuspendida($motivo->etiqueta(), $suspension->fin, $jugador->nombre_completo));
                }
            });
        }
    }
}
