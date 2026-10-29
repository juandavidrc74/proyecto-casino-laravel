@extends('layouts.app')

@section('title', 'Ganancias')

@section('content')
    <h1>
        Ganancias
    </h1>
    <form method="GET" action="{{ route('ganancias.index') }}" class="form-grid">
        <label>
            Desde
            <input type="date" name="desde" value="{{ $desde }}" required>
        </label>
        <label>
            Hasta
            <input type="date" name="hasta" value="{{ $hasta }}" required>
        </label>
        <label>
            Mesa
            <select name="mesa_id">
                <option value="">
                    Todas
                </option>
                @foreach ($mesas as $mesa)
                    <option value="{{ $mesa->id }}" @selected(request('mesa_id') == $mesa->id)>
                        {{ $mesa->codigo }}
                    </option>
                @endforeach
            </select>
        </label>
        <button class="button" type="submit">
            Filtrar
        </button>
    </form>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>
                        Mesa
                    </th>
                    <th>
                        Apuestas resueltas
                    </th>
                    <th>
                        Total apostado
                    </th>
                    <th>
                        Premios pagados
                    </th>
                    <th>
                        Consolidado casino
                    </th>
                    <th>
                        Neto casino
                    </th>
                </tr>
            </thead>
            <tbody>
                @foreach ($filas as $fila)
                    <tr>
                        <td>
                            {{ $fila['mesa']->codigo }}
                        </td>
                        <td>
                            {{ $fila['cantidad'] }}
                        </td>
                        <td>
                            {{ $fila['apostado'] }}
                        </td>
                        <td>
                            {{ $fila['premios'] }}
                        </td>
                        <td>
                            {{ $fila['consolidado'] }}
                        </td>
                        <td>
                            {{ $fila['neto'] }}
                        </td>
                    </tr>
                @endforeach
                <tr>
                    <th>
                        Total
                    </th>
                    <th>
                        {{ $totales['cantidad'] }}
                    </th>
                    <th>
                        {{ $totales['apostado'] }}
                    </th>
                    <th>
                        {{ $totales['premios'] }}
                    </th>
                    <th>
                        {{ $totales['consolidado'] }}
                    </th>
                    <th>
                        {{ $totales['neto'] }}
                    </th>
                </tr>
            </tbody>
        </table>
    </div>
@endsection
