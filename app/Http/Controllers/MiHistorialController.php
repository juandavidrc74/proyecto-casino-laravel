<?php

namespace App\Http\Controllers;

use App\Enums\EstadoApuesta;
use App\Models\Apuesta;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class MiHistorialController extends Controller
{
    public function __invoke(Request $request)
    {
        $jugador = $request->user()->jugador;
        abort_unless($jugador, Response::HTTP_FORBIDDEN);
        $apuestasResueltas = $jugador->apuestas()->whereIn('estado', [EstadoApuesta::Ganada->value, EstadoApuesta::Perdida->value])->get(['monto', 'premio']);
        $totales = $this->totales($apuestasResueltas);
        $apuestas = $jugador->apuestas()->with(['ronda.mesa.juego'])->orderByDesc('created_at')->orderByDesc('id')->simplePaginate(20)->withQueryString();
        $suspensiones = $jugador->suspensiones()->orderByDesc('inicio')->get();
        $abonos = $jugador->user->empleado ? $jugador->abonos()->latest()->get() : collect();

        return view('mi-historial.index', compact('jugador', 'totales', 'apuestas', 'suspensiones', 'abonos'));
    }

    private function totales($apuestas): array
    {
        $apostado = $apuestas->reduce(fn (string $total, Apuesta $apuesta) => bcadd($total, $apuesta->monto, 2), '0.00');
        $premios = $apuestas->reduce(fn (string $total, Apuesta $apuesta) => bcadd($total, $apuesta->premio, 2), '0.00');

        return ['resueltas' => $apuestas->count(), 'apostado' => $apostado, 'premios' => $premios, 'neto' => bcsub($premios, $apostado, 2)];
    }
}
