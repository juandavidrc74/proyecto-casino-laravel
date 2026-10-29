<form method="POST" action="{{ $action }}" data-zk-registro
    @if ($method === 'PUT')
        data-zk-opcional
    @endif
>
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif
    <label for="documento">
        Documento
    </label>
    <input id="documento" name="documento" value="{{ old('documento', $empleado->documento) }}" required>
    <label for="nombre_completo">
        Nombre completo
    </label>
    <input id="nombre_completo" name="nombre_completo" value="{{ old('nombre_completo', $empleado->nombre_completo) }}" required>
    <label for="rol">
        Rol
    </label>
    <select id="rol" name="rol" required>
        @foreach (['administrador' => 'Administrador', 'crupier' => 'Crupier', 'supervisor' => 'Supervisor'] as $valor => $etiqueta)
            <option value="{{ $valor }}" @selected(old('rol', $empleado->exists ? $empleado->user->rol->value : '') === $valor)>
                {{ $etiqueta }}
            </option>
        @endforeach
    </select>
    <label for="salario_mensual">
        Salario mensual en fichas
    </label>
    <input id="salario_mensual" name="salario_mensual" type="number" min="0.01" step="0.01" value="{{ old('salario_mensual', $empleado->salario_mensual) }}" required>
    <fieldset>
        <legend>
            Competencias por juego
        </legend>
        @foreach ($juegos as $juego)
            <label class="check-label">
                <input type="checkbox" name="competencias[]" value="{{ $juego->id }}" @checked(in_array((string) $juego->id, array_map('strval', old('competencias', $empleado->exists ? $empleado->juegos->modelKeys() : [])), true))>
                {{ $juego->nombre }}
            </label>
        @endforeach
    </fieldset>
    <label for="username">
        Nombre de usuario
    </label>
    <input id="username" name="username" value="{{ old('username', $empleado->exists ? $empleado->user->username : '') }}" required>
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
    <button class="button button-primary" type="submit">
        Guardar empleado
    </button>
    <a href="{{ route('empleados.index') }}">
        Cancelar
    </a>
</form>
<script src="{{ asset('js/zk.js') }}" defer>
</script>
