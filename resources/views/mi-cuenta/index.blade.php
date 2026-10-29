@extends('layouts.app')

@section('title', 'Mi cuenta')

@section('content')
    <section class="card">
        <h1>Mi cuenta</h1>
        <p>Código de cliente: <strong>{{ $jugador->codigo_cliente }}</strong></p>
        <p>Nombre completo: <strong>{{ $jugador->nombre_completo }}</strong></p>
        <p>Saldo: <strong>{{ $jugador->saldo }}</strong> fichas</p>
        <p>Estado: <strong>{{ $jugador->estado() }}</strong></p>
        @if ($bloqueo)
            <p>{{ $bloqueo }}</p>
        @endif
        @unless ($suspension)
            <form method="POST" action="{{ route('mi-cuenta.pausa') }}" onsubmit="return confirm('¿Solicitar una pausa de 24 horas?')">
                @csrf
                <button class="button button-primary" type="submit">Solicitar pausa de 24 horas</button>
            </form>
        @endunless
    </section>
@endsection
