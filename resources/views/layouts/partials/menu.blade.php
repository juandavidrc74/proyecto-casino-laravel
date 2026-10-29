<nav class="main-menu" aria-label="Menú principal">
    @if (auth()->user()->tieneRol(\App\Enums\Rol::Administrador))
        <a href="{{ route('inicio') }}">
            Inicio
        </a>
        <a href="{{ route('empleados.index') }}">
            Empleados
        </a>
        <a href="{{ route('jugadores.index') }}">
            Jugadores
        </a>
        <a href="{{ route('mesas.index') }}">
            Mesas
        </a>
        <a href="{{ route('turnos.index') }}">
            Turnos
        </a>
        <a href="{{ route('ganancias.index') }}">
            Ganancias
        </a>
        <a href="{{ route('suspensiones.index') }}">
            Suspensiones
        </a>
    @elseif (auth()->user()->tieneRol(\App\Enums\Rol::Crupier))
        <a href="{{ route('inicio') }}">
            Inicio
        </a>
        <a href="{{ route('mi-mesa') }}">
            Mi mesa
        </a>
    @elseif (auth()->user()->tieneRol(\App\Enums\Rol::Supervisor))
        <a href="{{ route('inicio') }}">
            Inicio
        </a>
        <a href="{{ route('conceptos.index') }}">
            Mis conceptos
        </a>
        <a href="{{ route('ganancias.index') }}">
            Ganancias
        </a>
    @elseif (auth()->user()->tieneRol(\App\Enums\Rol::Jugador))
        <a href="{{ route('inicio') }}">
            Inicio
        </a>
    @endif
    @if (auth()->user()->jugador)
        <a href="{{ route('mi-cuenta') }}">
            Mi cuenta
        </a>
        <a href="{{ route('mi-historial') }}">
            Mi historial
        </a>
    @endif
</nav>
