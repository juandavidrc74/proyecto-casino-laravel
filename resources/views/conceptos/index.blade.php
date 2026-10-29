@extends('layouts.app')

@section('title', 'Mis conceptos')

@section('content')
    <h1>
        Mis conceptos
    </h1>
    <table>
        <thead>
            <tr>
                <th>
                    Fecha
                </th>
                <th>
                    Turno
                </th>
                <th>
                    Mesa
                </th>
                <th>
                    Crupier
                </th>
                <th>
                    Concepto
                </th>
                <th>
                </th>
            </tr>
        </thead>
        <tbody>
            @forelse ($asignaciones as $asignacion)
                <tr>
                    <td>
                        {{ $asignacion->turno->fecha }}
                    </td>
                    <td>
                        {{ substr($asignacion->turno->hora_inicio, 0, 5) }}–{{ substr($asignacion->turno->hora_fin, 0, 5) }}
                    </td>
                    <td>
                        {{ $asignacion->mesa->codigo }}
                    </td>
                    <td>
                        {{ $asignacion->crupier->nombre_completo }}
                    </td>
                    <td>
                        {{ $asignacion->concepto?->value ?? 'Pendiente' }}
                    </td>
                    <td>
                        @if (! $asignacion->concepto && $asignacion->turno->haTerminado())
                            <form method="POST" action="{{ route('conceptos.store', $asignacion) }}">
                                @csrf
                                <select name="concepto" required>
                                    <option value="">
                                        Seleccione concepto
                                    </option>
                                    @foreach (\App\Enums\Concepto::cases() as $concepto)
                                        <option value="{{ $concepto->value }}">
                                            {{ $concepto->value }}
                                        </option>
                                    @endforeach
                                </select>
                                <button class="button button-primary">
                                    Registrar concepto
                                </button>
                            </form>
                        @elseif (! $asignacion->concepto)
                            Pendiente de finalizar turno
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6">
                        No tienes asignaciones.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
@endsection
