<form method="POST" action="{{ $action }}"
    @unless ($empleado)
        data-zk-registro
        @if ($method === 'PUT')
            data-zk-opcional
        @endif
    @endunless
>
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif
    @if ($method === 'POST' && $empleado)
        <input type="hidden" name="empleado_id" value="{{ $empleado->id }}">
        <label for="documento">
            Documento del empleado
        </label>
        <input id="documento" value="{{ $empleado->documento }}" readonly>
        <label for="nombre_completo">
            Nombre del empleado
        </label>
        <input id="nombre_completo" value="{{ $empleado->nombre_completo }}" readonly>
    @elseif ($empleado)
        <label for="documento">
            Documento del empleado
        </label>
        <input id="documento" value="{{ $empleado->documento }}" readonly>
        <label for="nombre_completo">
            Nombre del empleado
        </label>
        <input id="nombre_completo" value="{{ $empleado->nombre_completo }}" readonly>
    @else
        <label for="documento">
            Documento
        </label>
        <input id="documento" name="documento" value="{{ old('documento', $jugador->documento) }}" required>
        <label for="nombre_completo">
            Nombre completo
        </label>
        <input id="nombre_completo" name="nombre_completo" value="{{ old('nombre_completo', $jugador->nombre_completo) }}" required>
    @endif
    <label for="codigo_cliente">
        Código de cliente
    </label>
    <input id="codigo_cliente" name="codigo_cliente" value="{{ old('codigo_cliente', $jugador->codigo_cliente) }}" required>
    <label for="edad">
        Edad
    </label>
    <input id="edad" name="edad" type="number" min="18" step="1" value="{{ old('edad', $jugador->edad) }}" required>
    @if ($method === 'POST')
        <label for="saldo">
            Saldo inicial en fichas
        </label>
        <input id="saldo" name="saldo" type="number" min="0" step="0.01" value="{{ old('saldo', '0.00') }}" required>
    @endif
    @if (! $empleado)
        <label for="username">
            Nombre de usuario
        </label>
        <input id="username" name="username" value="{{ old('username', $jugador->exists ? $jugador->user->username : '') }}" required>
        <label for="clave">
            {{ $method === 'POST' ? 'Contraseña' : 'Nueva contraseña (opcional)' }}
        </label>
        <input id="clave" type="password" data-zk-clave autocomplete="new-password">
        <label for="confirmacion">
            Confirmar contraseña
        </label>
        <input id="confirmacion" type="password" data-zk-confirmacion autocomplete="new-password">
        <input type="hidden" name="password_hash" value="">
        <p class="message message-error" data-zk-error role="alert" hidden>
        </p>
    @endif
    <button class="button button-primary" type="submit">
        Guardar jugador
    </button>
    <a href="{{ route('jugadores.index') }}">
        Cancelar
    </a>
</form>
@unless ($empleado)
    <script src="{{ asset('js/zk.js') }}" defer>
    </script>
@endunless
