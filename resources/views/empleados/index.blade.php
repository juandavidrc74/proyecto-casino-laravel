@extends('layouts.app')

@section('title', 'Empleados')

@section('content')
    <section class="card">
        <div class="page-heading">
            <h1>
                Empleados
            </h1>
            <a class="button button-primary" href="{{ route('empleados.create') }}">
                Registrar empleado
            </a>
        </div>
        @if ($empleados->isEmpty())
            <p class="muted">
                No hay empleados registrados.
            </p>
        @else
            <div class="table-scroll">
                <table>
                    <thead>
                        <tr>
                            <th>
                                Documento
                            </th>
                            <th>
                                Nombre
                            </th>
                            <th>
                                Rol
                            </th>
                            <th>
                                Salario mensual
                            </th>
                            <th>
                                Competencias
                            </th>
                            <th>
                                Perfil de jugador
                            </th>
                            <th>
                                Acciones
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($empleados as $empleado)
                            <tr>
                                <td>
                                    {{ $empleado->documento }}
                                </td>
                                <td>
                                    {{ $empleado->nombre_completo }}
                                </td>
                                <td>
                                    {{ $empleado->user->rol->etiqueta() }}
                                </td>
                                <td>
                                    {{ $empleado->salario_mensual }}
                                </td>
                                <td>
                                    {{ $empleado->juegos->pluck('nombre')->join(', ') ?: '—' }}
                                </td>
                                <td>
                                    {{ $empleado->jugador ? 'Sí' : 'No' }}
                                </td>
                                <td class="actions">
                                    <a href="{{ route('empleados.edit', $empleado) }}">
                                        Editar
                                    </a>
                                    @if (! $empleado->jugador)
                                        <a href="{{ route('jugadores.create', ['empleado' => $empleado->id]) }}">
                                            Registrar como jugador
                                        </a>
                                    @endif
                                    <form method="POST" action="{{ route('empleados.destroy', $empleado) }}" onsubmit="return confirm('¿Borrar este empleado y su cuenta?')">
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
