<form method="POST" action="{{ $action }}">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif
    <label for="fecha">
        Fecha
    </label>
    <input id="fecha" name="fecha" type="date" required value="{{ old('fecha', $turno->fecha) }}">
    <label for="hora_inicio">
        Hora de inicio
    </label>
    <input id="hora_inicio" name="hora_inicio" type="time" required value="{{ old('hora_inicio', $turno->hora_inicio ? substr($turno->hora_inicio, 0, 5) : '') }}">
    <label for="hora_fin">
        Hora de fin
    </label>
    <input id="hora_fin" name="hora_fin" type="time" required value="{{ old('hora_fin', $turno->hora_fin ? substr($turno->hora_fin, 0, 5) : '') }}">
    <button class="button button-primary">
        Guardar turno
    </button>
    <a href="{{ route('turnos.index') }}">
        Cancelar
    </a>
</form>
