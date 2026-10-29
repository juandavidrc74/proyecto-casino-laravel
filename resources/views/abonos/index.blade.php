@extends('layouts.app')

@section('title', 'Abonos')

@section('content')
    <section class="card">
        <h1>Abonos de {{ $jugador->nombre_completo }}</h1>
        <p>Deuda actual: <strong>{{ $deuda }}</strong> fichas</p>
        <p>Límite de dos salarios: <strong>{{ $limite }}</strong> fichas</p>
        <form method="POST" action="{{ route('jugadores.abonos.store', $jugador) }}">
            @csrf
            <label for="monto">Monto del abono</label>
            <input id="monto" name="monto" type="number" min="0.01" step="0.01" max="{{ $deuda }}" value="{{ old('monto') }}" required>
            <button class="button button-primary" type="submit">Registrar abono</button>
        </form>
    </section>
    <section class="card">
        <h2>Abonos anteriores</h2>
        @if ($jugador->abonos->isEmpty())
            <p class="muted">No hay abonos registrados.</p>
        @else
            <div class="table-scroll"><table><thead><tr><th>Fecha</th><th>Monto</th></tr></thead><tbody>
                @foreach ($jugador->abonos as $abono)
                    <tr><td>{{ $abono->created_at->timezone(config('app.timezone'))->format('d/m/Y H:i') }}</td><td>{{ $abono->monto }}</td></tr>
                @endforeach
            </tbody></table></div>
        @endif
    </section>
@endsection
