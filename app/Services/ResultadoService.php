<?php

namespace App\Services;

use App\Enums\EstadoApuesta;
use App\Models\Apuesta;
use App\Models\Jugador;
use App\Models\Ronda;
use App\Notifications\ApuestaResuelta;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ResultadoService
{
    public function cerrar(Ronda $ronda, string $resultado, array $tiposGanadores = []): Ronda
    {
        return DB::transaction(function () use ($ronda, $resultado, $tiposGanadores) {
            $ronda = Ronda::with('mesa.juego')->lockForUpdate()->findOrFail($ronda->id);
            if ($ronda->hora_cierre !== null) {
                throw ValidationException::withMessages(['resultado' => 'La ronda ya está cerrada.']);
            }

            if ($ronda->mesa->juego->registro_detallado) {
                $tiposDisponibles = $ronda->apuestas()->where('estado', EstadoApuesta::Pendiente->value)->pluck('tipo_apuesta')->unique()->all();
                if (array_diff($tiposGanadores, $tiposDisponibles)) {
                    throw ValidationException::withMessages(['tipos_ganadores' => 'Solo se pueden marcar tipos de apuesta pendientes de esta ronda.']);
                }
            }

            $ronda->update(['resultado' => $resultado, 'hora_cierre' => now(config('app.timezone'))]);
            if ($ronda->mesa->juego->registro_detallado) {
                $pendientes = $ronda->apuestas()->where('estado', EstadoApuesta::Pendiente->value)->get();
                foreach ($pendientes as $apuesta) {
                    $this->resolver($apuesta, in_array($apuesta->tipo_apuesta, $tiposGanadores, true) ? EstadoApuesta::Ganada : EstadoApuesta::Perdida);
                }
            }

            return $ronda->fresh(['apuestas']);
        });
    }

    public function resolver(Apuesta $apuesta, EstadoApuesta $estado): Apuesta
    {
        return DB::transaction(function () use ($apuesta, $estado) {
            $apuesta = Apuesta::lockForUpdate()->findOrFail($apuesta->id);
            if ($apuesta->estado !== EstadoApuesta::Pendiente) {
                throw ValidationException::withMessages(['apuesta' => 'La apuesta ya fue resuelta.']);
            }

            $jugador = Jugador::whereKey($apuesta->jugador_id)->lockForUpdate()->firstOrFail();
            $premio = '0.00';
            if ($estado === EstadoApuesta::Ganada) {
                $premio = bcmul($apuesta->monto, $apuesta->factor_pago, 2);
                $jugador->saldo = bcadd($jugador->saldo, $premio, 2);
                $jugador->save();
            }
            $apuesta->update(['estado' => $estado, 'premio' => $premio, 'resuelta_en' => now(config('app.timezone'))]);
            app(AutorizacionJugadorService::class)->evaluarTrasApuesta($jugador);
            $apuesta = $apuesta->fresh(['ronda.mesa']);
            $saldoResultante = $jugador->fresh()->saldo;
            DB::afterCommit(function () use ($apuesta, $saldoResultante) {
                $apuesta->jugador->user->notify(new ApuestaResuelta($apuesta, $saldoResultante));
            });

            return $apuesta;
        });
    }
}
