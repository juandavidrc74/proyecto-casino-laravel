<?php

namespace App\Http\Controllers;

use App\Enums\MotivoSuspension;
use App\Models\Suspension;
use App\Notifications\CuentaReactivada;
use Illuminate\Support\Facades\DB;

class SuspensionController extends Controller
{
    public function index()
    {
        $ahora = now(config('app.timezone'));
        $suspensiones = Suspension::with('jugador')->orderByDesc('inicio')->get();
        $activas = $suspensiones->filter(fn (Suspension $suspension) => $suspension->inicio->lte($ahora) && (! $suspension->fin || $suspension->fin->gt($ahora)));
        $historial = $suspensiones->diff($activas);

        return view('suspensiones.index', compact('activas', 'historial'));
    }

    public function finalizar(Suspension $suspension)
    {
        abort_unless($suspension->motivo === MotivoSuspension::Investigacion, 404);
        abort_if($suspension->fin && $suspension->fin->lte(now(config('app.timezone'))), 404);
        abort_if($suspension->inicio->gt(now(config('app.timezone'))), 404);
        $suspension->update(['fin' => now(config('app.timezone'))]);
        DB::afterCommit(function () use ($suspension) {
            $suspension->jugador->user->notify(new CuentaReactivada('El Administrador finalizó la investigación y tu cuenta fue reactivada.'));
        });

        return redirect()->route('suspensiones.index')->with('status', 'Investigación finalizada.');
    }
}
