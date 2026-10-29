<form method="POST" action="{{ $action }}">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif
    <label for="codigo">
        Código
    </label>
    <input id="codigo" name="codigo" required maxlength="255" value="{{ old('codigo', $mesa->codigo) }}">
    <label for="juego_id">
        Juego
    </label>
    <select id="juego_id" name="juego_id" required>
        @foreach ($juegos as $juego)
            <option value="{{ $juego->id }}" @selected(old('juego_id', $mesa->juego_id) == $juego->id)>
                {{ $juego->nombre }}
            </option>
        @endforeach
    </select>
    <label for="apuesta_minima">
        Apuesta mínima
    </label>
    <input id="apuesta_minima" name="apuesta_minima" type="number" step="0.01" min="0.01" required value="{{ old('apuesta_minima', $mesa->apuesta_minima) }}">
    <label for="apuesta_maxima">
        Apuesta máxima
    </label>
    <input id="apuesta_maxima" name="apuesta_maxima" type="number" step="0.01" min="0.01" required value="{{ old('apuesta_maxima', $mesa->apuesta_maxima) }}">
    <button class="button button-primary">
        Guardar mesa
    </button>
    <a href="{{ route('mesas.index') }}">
        Cancelar
    </a>
</form>
