@extends('layouts.app')

@section('title', 'Jugadores')

@section('content')
    <section class="card">
        <div class="page-heading">
            <h1>
                Jugadores
            </h1>
            <a class="button button-primary" href="{{ route('jugadores.create') }}">
                Registrar jugador
            </a>
        </div>
        @if ($jugadores->isEmpty())
            <p class="muted">
                No hay jugadores registrados.
            </p>
        @else
            <div class="table-scroll">
                <table>
                    <thead>
                        <tr>
                            <th>
                                Código
                            </th>
                            <th>
                                Documento
                            </th>
                            <th>
                                Nombre
                            </th>
                            <th>
                                Edad
                            </th>
                            <th>
                                Saldo
                            </th>
                            <th>
                                Es empleado
                            </th>
                            <th>
                                Estado
                            </th>
                            <th>
                                Acciones
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($jugadores as $jugador)
                            <tr>
                                <td>
                                    {{ $jugador->codigo_cliente }}
                                </td>
                                <td>
                                    {{ $jugador->documento }}
                                </td>
                                <td>
                                    {{ $jugador->nombre_completo }}
                                </td>
                                <td>
                                    {{ $jugador->edad }}
                                </td>
                                <td>
                                    {{ $jugador->saldo }}
                                </td>
                                <td>
                                    {{ $jugador->user->empleado ? 'Sí' : 'No' }}
                                </td>
                                <td>
                                    {{ $jugador->estado() }}
                                </td>
                                <td class="actions">
                                    <a href="{{ route('jugadores.show', $jugador) }}">
                                        Historial
                                    </a>
                                    <a href="{{ route('jugadores.edit', $jugador) }}">
                                        Editar
                                    </a>
                                    @if ($jugador->user->empleado)
                                        <a href="{{ route('jugadores.abonos.index', $jugador) }}">
                                            Abonos
                                        </a>
                                    @endif
                                    <form method="POST" action="{{ route('jugadores.destroy', $jugador) }}" onsubmit="return confirm('¿Borrar este perfil de jugador?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="link-button" type="submit">
                                            Borrar
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
@endsection
