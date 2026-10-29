@extends('layouts.app')

@section('title', 'Modificar apuesta')

@section('content')
    <h1>
        Modificar apuesta
    </h1>
    <form method="POST" action="{{ route('apuestas.update', $apuesta) }}" class="form-grid">
        @csrf
        @method('PUT')
        <p>
            Jugador: {{ $apuesta->jugador->codigo_cliente }}
        </p>
        <label>
            Tipo de apuesta
            <input name="tipo_apuesta" value="{{ old('tipo_apuesta', $apuesta->tipo_apuesta) }}" required>
        </label>
        <label>
            Monto
            <input name="monto" type="number" min="0.01" step="0.01" value="{{ old('monto', $apuesta->monto) }}" required>
        </label>
        <label>
            Factor de pago
            <input name="factor_pago" type="number" min="0.01" step="0.01" value="{{ old('factor_pago', $apuesta->factor_pago) }}" required>
        </label>
        <button class="button" type="submit">
            Guardar
        </button>
    </form>
@endsection
