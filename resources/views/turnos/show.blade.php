@extends('layouts.app')

@section('title', 'Turno')

@section('content')
    <h1>
        Turno · {{ $turno->fecha }} · {{ substr($turno->hora_inicio, 0, 5) }}–{{ substr($turno->hora_fin, 0, 5) }}
    </h1>
    <p>
        <a href="{{ route('turnos.index') }}">
            Volver a turnos
        </a>
    </p>
    <h2>
        Asignaciones
    </h2>
    <table>
        <thead>
            <tr>
                <th>
                    Mesa
                </th>
                <th>
                    Juego
                </th>
                <th>
                    Crupier
                </th>
                <th>
                    Supervisor
                </th>
                <th>
                    Concepto
                </th>
                <th>
                </th>
            </tr>
        </thead>
        <tbody>
            @forelse ($turno->asignaciones as $asignacion)
                <tr>
                    <td>
                        {{ $asignacion->mesa->codigo }}
                    </td>
                    <td>
                        {{ $asignacion->mesa->juego->nombre }}
                    </td>
                    <td>
                        {{ $asignacion->crupier->nombre_completo }}
                    </td>
                    <td>
                        {{ $asignacion->supervisor->nombre_completo }}
                    </td>
                    <td>
                        {{ $asignacion->concepto?->value ?? 'Pendiente' }}
                    </td>
                    <td>
                        <form method="POST" action="{{ route('turnos.asignaciones.destroy', [$turno, $asignacion]) }}">
                            @csrf
                            @method('DELETE')
                            <button>
                                Borrar
                            </button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6">
                        No hay asignaciones.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
    <h2>
        Añadir asignación
    </h2>
    <form method="POST" action="{{ route('turnos.asignaciones.store', $turno) }}">
        @csrf
        <label for="mesa_id">
            Mesa
        </label>
        <select id="mesa_id" name="mesa_id" required>
            @foreach ($mesas as $mesa)
                <option value="{{ $mesa->id }}">
                    {{ $mesa->codigo }} · {{ $mesa->juego->nombre }}
                </option>
            @endforeach
        </select>
        <label for="crupier_id">
            Crupier
        </label>
        <select id="crupier_id" name="crupier_id" required>
            @foreach ($crupiers as $empleado)
                <option value="{{ $empleado->id }}">
                    {{ $empleado->nombre_completo }}
                </option>
            @endforeach
        </select>
        <label for="supervisor_id">
            Supervisor
        </label>
        <select id="supervisor_id" name="supervisor_id" required>
            @foreach ($supervisores as $empleado)
                <option value="{{ $empleado->id }}">
                    {{ $empleado->nombre_completo }}
                </option>
            @endforeach
        </select>
        <button class="button button-primary">
            Asignar
        </button>
    </form>
@endsection
