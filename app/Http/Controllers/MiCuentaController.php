<?php

namespace App\Http\Controllers;

use App\Models\Jugador;
use App\Services\AutorizacionJugadorService;
use Illuminate\Http\Request;

class MiCuentaController extends Controller
{
    public function index(Request $request, AutorizacionJugadorService $autorizacion)
    {
        $jugador = $this->perfil($request);
        $suspension = $jugador->suspensiones()->where('inicio', '<=', now(config('app.timezone')))
            ->where(fn ($query) => $query->whereNull('fin')->orWhere('fin', '>', now(config('app.timezone'))))
            ->orderByRaw("CASE WHEN motivo = 'investigacion' THEN 0 ELSE 1 END")->first();
        $bloqueo = $suspension ? $autorizacion->motivoBloqueo($jugador) : null;

        return view('mi-cuenta.index', compact('jugador', 'suspension', 'bloqueo'));
    }

    public function pausar(Request $request, AutorizacionJugadorService $autorizacion)
    {
        $autorizacion->solicitarPausa($this->perfil($request));

        return redirect()->route('mi-cuenta')->with('status', 'Tu pausa de 24 horas comenzó.');
    }

    private function perfil(Request $request): Jugador
    {
        $jugador = $request->user()->jugador;
        abort_unless($jugador, 403);

        return $jugador;
    }
}
