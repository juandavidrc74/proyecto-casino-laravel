<?php

namespace App\Services;

use App\Enums\EstadoApuesta;
use App\Models\Apuesta;
use App\Models\Jugador;
use App\Models\Ronda;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ApuestaService
{
    public function registrar(Ronda $ronda, array $datos): Apuesta
    {
        return DB::transaction(function () use ($ronda, $datos) {
            $ronda = Ronda::with('mesa.juego')->lockForUpdate()->findOrFail($ronda->id);
            $jugador = $this->validar($ronda, $datos, null);
            $apuesta = $ronda->apuestas()->create([
                'jugador_id' => $jugador->id,
                'tipo_apuesta' => $datos['tipo_apuesta'],
                'monto' => $datos['monto'],
                'factor_pago' => $datos['factor_pago'],
                'estado' => EstadoApuesta::Pendiente,
                'premio' => '0.00',
            ]);
            $jugador->saldo = bcsub($jugador->saldo, $datos['monto'], 2);
            $jugador->save();
            if (! $ronda->mesa->juego->registro_detallado) {
                app(ResultadoService::class)->resolver($apuesta, EstadoApuesta::from($datos['estado']));
            }

            return $apuesta->fresh();
        });
    }

    public function modificar(Apuesta $apuesta, array $datos): Apuesta
    {
        return DB::transaction(function () use ($apuesta, $datos) {
            $apuesta = Apuesta::with('ronda.mesa.juego')->lockForUpdate()->findOrFail($apuesta->id);
            if ($apuesta->estado !== EstadoApuesta::Pendiente || $apuesta->ronda->hora_cierre !== null) {
                throw ValidationException::withMessages(['apuesta' => 'Solo se modifican apuestas pendientes de una ronda abierta.']);
            }
            $jugador = $this->validar($apuesta->ronda, $datos, $apuesta);
            $diferencia = bcsub($datos['monto'], $apuesta->monto, 2);
            if (bccomp($diferencia, '0.00', 2) > 0 && bccomp($jugador->saldo, $diferencia, 2) < 0) {
                throw ValidationException::withMessages(['monto' => 'El saldo del jugador es insuficiente.']);
            }
            $jugador->saldo = bcsub($jugador->saldo, $diferencia, 2);
            $jugador->save();
            $apuesta->update(['tipo_apuesta' => $datos['tipo_apuesta'], 'monto' => $datos['monto'], 'factor_pago' => $datos['factor_pago']]);

            return $apuesta->fresh();
        });
    }

    public function anular(Apuesta $apuesta): void
    {
        DB::transaction(function () use ($apuesta) {
            $apuesta = Apuesta::with('ronda')->lockForUpdate()->findOrFail($apuesta->id);
            if ($apuesta->estado !== EstadoApuesta::Pendiente || $apuesta->ronda->hora_cierre !== null) {
                throw ValidationException::withMessages(['apuesta' => 'Solo se anulan apuestas pendientes de una ronda abierta.']);
            }
            $jugador = Jugador::whereKey($apuesta->jugador_id)->lockForUpdate()->firstOrFail();
            $jugador->saldo = bcadd($jugador->saldo, $apuesta->monto, 2);
            $jugador->save();
            $apuesta->delete();
        });
    }

    private function validar(Ronda $ronda, array $datos, ?Apuesta $actual): Jugador
    {
        $detallado = $ronda->mesa->juego->registro_detallado;
        if (($detallado && $ronda->hora_cierre !== null) || (! $detallado && $ronda->hora_cierre === null)) {
            throw ValidationException::withMessages(['ronda' => $detallado ? 'Solo se apuesta en una ronda abierta.' : 'En este juego las apuestas se registran cuando se conoce el resultado.']);
        }
        if (! isset($datos['tipo_apuesta']) || trim($datos['tipo_apuesta']) === '') {
            throw ValidationException::withMessages(['tipo_apuesta' => 'El tipo de apuesta es obligatorio.']);
        }
        if (bccomp($datos['factor_pago'], '0.00', 2) <= 0) {
            throw ValidationException::withMessages(['factor_pago' => 'El factor de pago debe ser mayor que cero.']);
        }
        if (bccomp($datos['monto'], $ronda->mesa->apuesta_minima, 2) < 0 || bccomp($datos['monto'], $ronda->mesa->apuesta_maxima, 2) > 0) {
            throw ValidationException::withMessages(['monto' => 'El monto debe estar entre el mínimo y el máximo de la mesa.']);
        }
        $jugador = $actual
            ? Jugador::whereKey($actual->jugador_id)->lockForUpdate()->first()
            : Jugador::where('codigo_cliente', $datos['codigo_cliente'] ?? '')->lockForUpdate()->first();
        if (! $jugador) {
            throw ValidationException::withMessages(['codigo_cliente' => 'El código de cliente no existe.']);
        }
        $bloqueo = app(AutorizacionJugadorService::class)->motivoBloqueo($jugador);
        if ($bloqueo !== null) {
            throw ValidationException::withMessages(['codigo_cliente' => $bloqueo]);
        }
        $disponible = $actual ? bcadd($jugador->saldo, $actual->monto, 2) : $jugador->saldo;
        if (bccomp($disponible, $datos['monto'], 2) < 0) {
            throw ValidationException::withMessages(['monto' => 'El saldo del jugador es insuficiente.']);
        }

        return $jugador;
    }
}
