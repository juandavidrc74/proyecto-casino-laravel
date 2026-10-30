@extends('layouts.app')

@section('title', 'Mi mesa')

@section('content')
    <h1>
        Mi mesa
    </h1>
    @if (! $asignacion)
        <p>
            No tienes una mesa asignada en el turno en curso.
        </p>
    @else
        <section class="panel">
            <h2>
                {{ $asignacion->mesa->codigo }} · {{ $asignacion->mesa->juego->nombre }}
            </h2>
            <p>
                Apuesta mínima: {{ $asignacion->mesa->apuesta_minima }}
                · Máxima: {{ $asignacion->mesa->apuesta_maxima }}
            </p>
            @if (! $rondaAbierta)
                <form method="POST" action="{{ route('rondas.store', $asignacion->mesa) }}">
                    @csrf
                    <button class="button" type="submit">
                        Abrir ronda
                    </button>
                </form>
            @endif
        </section>
        @foreach ($rondas as $ronda)
            <section class="panel">
                <h2>
                    Ronda {{ $ronda->numero }} · {{ $ronda->hora_cierre ? 'Cerrada' : 'Abierta' }}
                </h2>
                @if ($ronda->hora_cierre)
                    <p>
                        Resultado: {{ $ronda->resultado }}
                        · Cerrada {{ $ronda->hora_cierre->format('d/m/Y H:i') }}
                    </p>
                @endif
                @if (! $ronda->hora_cierre)
                    <form method="POST" action="{{ route('rondas.cerrar', $ronda) }}" class="form-grid">
                        @csrf
                        <label>
                            Resultado
                            <input name="resultado" value="{{ old('resultado') }}" required maxlength="255">
                        </label>
                        @if ($asignacion->mesa->juego->registro_detallado)
                            <fieldset>
                                <legend>
                                    Tipos ganadores
                                </legend>
                                @foreach ($ronda->apuestas->pluck('tipo_apuesta')->unique() as $tipo)
                                    <label>
                                        <input type="checkbox" name="tipos_ganadores[]" value="{{ $tipo }}" @checked(in_array($tipo, old('tipos_ganadores', []), true))>
                                        {{ $tipo }}
                                    </label>
                                @endforeach
                            </fieldset>
                        @endif
                        <button class="button" type="submit">
                            Cerrar ronda
                        </button>
                    </form>
                    @if ($ronda->apuestas->isEmpty())
                        <form method="POST" action="{{ route('rondas.destroy', $ronda) }}">
                            @csrf
                            @method('DELETE')
                            <button class="button button-secondary" type="submit">
                                Anular ronda
                            </button>
                        </form>
                    @endif
                @endif
                @php($conservar = (string) old('ronda_formulario') === (string) $ronda->id)
                @if ($ronda->hora_cierre && ! $asignacion->mesa->juego->registro_detallado)
                    <form method="POST" action="{{ route('apuestas.store', $ronda) }}" class="form-grid">
                        @csrf
                        <input type="hidden" name="ronda_formulario" value="{{ $ronda->id }}">
                        <h3>
                            Registrar apuesta con resultado conocido
                        </h3>
                        <label>
                            Código de cliente
                            <input name="codigo_cliente" value="{{ $conservar ? old('codigo_cliente') : '' }}" required>
                        </label>
                        <label>
                            Tipo de apuesta
                            <input name="tipo_apuesta" value="{{ $conservar ? old('tipo_apuesta') : '' }}" required>
                        </label>
                        <label>
                            Monto
                            <input name="monto" type="number" min="0.01" step="0.01" value="{{ $conservar ? old('monto') : '' }}" required>
                        </label>
                        <label>
                            Factor de pago
                            <input name="factor_pago" type="number" min="0.01" step="0.01" value="{{ $conservar ? old('factor_pago') : '' }}" required>
                        </label>
                        <label>
                            Estado
                            <select name="estado" required>
                                <option>
                                    Ganada
                                </option>
                                <option @selected($conservar && old('estado') === 'Perdida')>
                                    Perdida
                                </option>
                            </select>
                        </label>
                        <button class="button" type="submit">
                            Registrar apuesta
                        </button>
                    </form>
                @endif
                <h3>
                    Apuestas
                </h3>
                @forelse ($ronda->apuestas as $apuesta)
                    <p>
                        {{ $apuesta->jugador->codigo_cliente }}
                        · {{ $apuesta->tipo_apuesta }}
                        · {{ $apuesta->monto }} × {{ $apuesta->factor_pago }}
                        · {{ $apuesta->estado->value }}
                        · Premio {{ $apuesta->premio }}
                        @if ($apuesta->estado->value === 'Pendiente')
                            <a href="{{ route('apuestas.edit', $apuesta) }}">
                                Modificar
                            </a>
                            <form method="POST" action="{{ route('apuestas.destroy', $apuesta) }}" class="inline-form">
                                @csrf
                                @method('DELETE')
                                <button class="link-button" type="submit">
                                    Anular
                                </button>
                            </form>
                        @endif
                    </p>
                @empty
                    <p>
                        Aún no hay apuestas.
                    </p>
                @endforelse
                @if (! $ronda->hora_cierre && $asignacion->mesa->juego->registro_detallado)
                    <form method="POST" action="{{ route('apuestas.store', $ronda) }}" class="form-grid">
                        @csrf
                        <input type="hidden" name="ronda_formulario" value="{{ $ronda->id }}">
                        <h3>
                            Registrar apuesta
                        </h3>
                        <label>
                            Código de cliente
                            <input name="codigo_cliente" value="{{ $conservar ? old('codigo_cliente') : '' }}" required>
                        </label>
                        <label>
                            Tipo de apuesta
                            <input name="tipo_apuesta" value="{{ $conservar ? old('tipo_apuesta') : '' }}" required>
                        </label>
                        <label>
                            Monto
                            <input name="monto" type="number" min="0.01" step="0.01" value="{{ $conservar ? old('monto') : '' }}" required>
                        </label>
                        <label>
                            Factor de pago
                            <input name="factor_pago" type="number" min="0.01" step="0.01" value="{{ $conservar ? old('factor_pago') : '' }}" required>
                        </label>
                        <button class="button" type="submit">
                            Registrar apuesta
                        </button>
                    </form>
                @endif
            </section>
        @endforeach
    @endif
@endsection
