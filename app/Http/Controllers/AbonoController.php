<?php

namespace App\Http\Controllers;

use App\Http\Requests\AbonoStoreRequest;
use App\Models\Jugador;
use App\Services\AutorizacionJugadorService;
use Symfony\Component\HttpFoundation\Response;

class AbonoController extends Controller
{
    public function index(Jugador $jugador, AutorizacionJugadorService $autorizacion)
    {
        abort_unless($jugador->user->empleado, Response::HTTP_FORBIDDEN);
        $jugador->load(['abonos' => fn ($query) => $query->latest()]);
        $deuda = $autorizacion->deudaEmpleado($jugador);
        $limite = bcmul($jugador->user->empleado->salario_mensual, '2.00', 2);

        return view('abonos.index', compact('jugador', 'deuda', 'limite'));
    }

    public function store(AbonoStoreRequest $request, Jugador $jugador, AutorizacionJugadorService $autorizacion)
    {
        abort_unless($jugador->user->empleado, Response::HTTP_FORBIDDEN);
        $autorizacion->registrarAbono($jugador, $request->validated('monto'));

        return redirect()->route('jugadores.abonos.index', $jugador)->with('status', 'Abono registrado.');
    }
}
