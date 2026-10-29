@extends('layouts.app')

@section('title', 'Mesas')

@section('content')
    <h1>
        Mesas
    </h1>
    <p>
        <a class="button button-primary" href="{{ route('mesas.create') }}">
            Registrar mesa
        </a>
    </p>
    <table>
        <thead>
            <tr>
                <th>
                    Código
                </th>
                <th>
                    Juego
                </th>
                <th>
                    Apuesta mínima
                </th>
                <th>
                    Apuesta máxima
                </th>
                <th>
                    Acciones
                </th>
            </tr>
        </thead>
        <tbody>
            @forelse ($mesas as $mesa)
                <tr>
                    <td>
                        {{ $mesa->codigo }}
                    </td>
                    <td>
                        {{ $mesa->juego->nombre }}
                    </td>
                    <td>
                        {{ $mesa->apuesta_minima }}
                    </td>
                    <td>
                        {{ $mesa->apuesta_maxima }}
                    </td>
                    <td>
                        <a href="{{ route('mesas.edit', $mesa) }}">
                            Editar
                        </a>
                        <form method="POST" action="{{ route('mesas.destroy', $mesa) }}">
                            @csrf
                            @method('DELETE')
                            <button type="submit">
                                Borrar
                            </button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5">
                        No hay mesas registradas.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
@endsection
