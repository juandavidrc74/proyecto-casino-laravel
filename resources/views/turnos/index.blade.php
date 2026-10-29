@extends('layouts.app')

@section('title', 'Turnos')

@section('content')
    <h1>
        Turnos
    </h1>
    <p>
        <a class="button button-primary" href="{{ route('turnos.create') }}">
            Registrar turno
        </a>
    </p>
    <table>
        <thead>
            <tr>
                <th>
                    Fecha
                </th>
                <th>
                    Inicio
                </th>
                <th>
                    Fin
                </th>
                <th>
                    Acciones
                </th>
            </tr>
        </thead>
        <tbody>
            @forelse ($turnos as $turno)
                <tr>
                    <td>
                        {{ $turno->fecha }}
                    </td>
                    <td>
                        {{ substr($turno->hora_inicio, 0, 5) }}
                    </td>
                    <td>
                        {{ substr($turno->hora_fin, 0, 5) }}
                    </td>
                    <td>
                        <a href="{{ route('turnos.show', $turno) }}">
                            Ver asignaciones
                        </a>
                        <a href="{{ route('turnos.edit', $turno) }}">
                            Editar
                        </a>
                        <form method="POST" action="{{ route('turnos.destroy', $turno) }}">
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
                    <td colspan="4">
                        No hay turnos registrados.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
@endsection
