<?php

namespace App\Http\Controllers;

use App\Enums\EstadoApuesta;
use App\Enums\Rol;
use App\Http\Requests\GananciaRequest;
use App\Models\Apuesta;
use App\Models\Mesa;

class GananciaController extends Controller
{
    public function index(GananciaRequest $request)
    {
        $datos = $request->validated();
        $desde = $datos['desde'];
        $hasta = $datos['hasta'];
        $mesasPermitidas = null;
        $asignacionesPermitidas = null;
        if (auth()->user()->tieneRol(Rol::Supervisor)) {
            $empleado = auth()->user()->empleado;
            abort_unless($empleado, 403);
            $asignaciones = $empleado->asignacionesComoSupervisor()->get(['id', 'mesa_id']);
            $mesasPermitidas = $asignaciones->pluck('mesa_id')->unique();
            $asignacionesPermitidas = $asignaciones->pluck('id');
        }
        $mesas = Mesa::with('juego')
            ->when($mesasPermitidas !== null, fn ($query) => $query->whereIn('id', $mesasPermitidas))
            ->when(isset($datos['mesa_id']), fn ($query) => $query->whereKey($datos['mesa_id']))
            ->orderBy('codigo')
            ->get();
        $apuestas = Apuesta::with('ronda.mesa')
            ->whereIn('estado', [EstadoApuesta::Ganada->value, EstadoApuesta::Perdida->value])
            ->whereBetween('resuelta_en', [$desde.' 00:00:00', $hasta.' 23:59:59'])
            ->whereHas('ronda', function ($query) use ($mesas, $asignacionesPermitidas) {
                $query->whereIn('mesa_id', $mesas->pluck('id'));
                if ($asignacionesPermitidas !== null) {
                    $query->whereIn('asignacion_id', $asignacionesPermitidas);
                }
            })
            ->get();
        $filas = $mesas->map(function (Mesa $mesa) use ($apuestas) {
            $items = $apuestas->where('ronda.mesa_id', $mesa->id);
            $apostado = '0.00';
            $premios = '0.00';
            $consolidado = '0.00';
            foreach ($items as $apuesta) {
                $apostado = bcadd($apostado, $apuesta->monto, 2);
                $premios = bcadd($premios, $apuesta->premio, 2);
                if ($apuesta->estado === EstadoApuesta::Perdida) {
                    $consolidado = bcadd($consolidado, $apuesta->monto, 2);
                }
            }

            return [
                'mesa' => $mesa,
                'cantidad' => $items->count(),
                'apostado' => $apostado,
                'premios' => $premios,
                'consolidado' => $consolidado,
                'neto' => bcsub($apostado, $premios, 2),
            ];
        });
        $totales = [
            'cantidad' => $filas->sum('cantidad'),
            'apostado' => '0.00',
            'premios' => '0.00',
            'consolidado' => '0.00',
            'neto' => '0.00',
        ];
        foreach ($filas as $fila) {
            foreach (['apostado', 'premios', 'consolidado', 'neto'] as $campo) {
                $totales[$campo] = bcadd($totales[$campo], $fila[$campo], 2);
            }
        }

        return view('ganancias.index', compact('desde', 'hasta', 'mesas', 'filas', 'totales'));
    }
}
