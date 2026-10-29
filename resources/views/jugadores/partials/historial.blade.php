<section class="card history-card">
    <h1>{{ $titulo }}</h1>
    <h2>Datos del jugador</h2>
    <dl class="history-details">
        <dt>Código</dt><dd>{{ $jugador->codigo_cliente }}</dd>
        <dt>Documento</dt><dd>{{ $jugador->documento }}</dd>
        <dt>Nombre</dt><dd>{{ $jugador->nombre_completo }}</dd>
        <dt>Edad</dt><dd>{{ $jugador->edad }}</dd>
        <dt>Saldo</dt><dd>{{ $jugador->saldo }} fichas</dd>
        <dt>Estado</dt><dd>{{ $jugador->estado() }}</dd>
        <dt>Es empleado</dt><dd>{{ $jugador->user->empleado ? 'Sí' : 'No' }}</dd>
    </dl>

    <h2>Totales de apuestas resueltas</h2>
    <dl class="history-details">
        <dt>Apuestas resueltas</dt><dd>{{ $totales['resueltas'] }}</dd>
        <dt>Total apostado</dt><dd>{{ $totales['apostado'] }}</dd>
        <dt>Total en premios</dt><dd>{{ $totales['premios'] }}</dd>
        <dt>Resultado neto del jugador</dt><dd>{{ $totales['neto'] }}</dd>
    </dl>

    <h2>Apuestas</h2>
    @if ($apuestas->isEmpty())
        <p class="muted">No hay apuestas registradas.</p>
    @else
        <div class="table-scroll">
            <table>
                <thead><tr><th>Fecha</th><th>Mesa</th><th>Juego</th><th>Ronda</th><th>Tipo</th><th>Monto</th><th>Factor</th><th>Estado</th><th>Premio</th></tr></thead>
                <tbody>
                    @foreach ($apuestas as $apuesta)
                        <tr>
                            <td>{{ ($apuesta->resuelta_en ?? $apuesta->created_at)->timezone(config('app.timezone'))->format('d/m/Y H:i') }}</td>
                            <td>{{ $apuesta->ronda->mesa->codigo }}</td>
                            <td>{{ $apuesta->ronda->mesa->juego->nombre }}</td>
                            <td>{{ $apuesta->ronda->numero }}</td>
                            <td>{{ $apuesta->tipo_apuesta }}</td>
                            <td>{{ $apuesta->monto }}</td>
                            <td>{{ $apuesta->factor_pago }}</td>
                            <td>{{ $apuesta->estado->value }}</td>
                            <td>{{ $apuesta->premio }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        {{ $apuestas->links('partials.paginacion') }}
    @endif

    <h2>Suspensiones</h2>
    @forelse ($suspensiones as $suspension)
        <p>{{ $suspension->motivo->etiqueta() }} · inicio {{ $suspension->inicio->timezone(config('app.timezone'))->format('d/m/Y H:i') }} · fin {{ $suspension->fin?->timezone(config('app.timezone'))->format('d/m/Y H:i') ?? 'Sin fecha de fin' }}</p>
    @empty
        <p class="muted">No hay suspensiones registradas.</p>
    @endforelse

    @if ($jugador->user->empleado)
        <h2>Abonos</h2>
        @forelse ($abonos as $abono)
            <p>{{ $abono->created_at->timezone(config('app.timezone'))->format('d/m/Y H:i') }} · {{ $abono->monto }} fichas</p>
        @empty
            <p class="muted">No hay abonos registrados.</p>
        @endforelse
    @endif
</section>
