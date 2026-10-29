@if ($elementos->isEmpty())
    <p class="muted">No hay registros.</p>
@else
    <div class="table-scroll">
        <table>
            <thead><tr><th>Jugador</th><th>Motivo</th><th>Inicio</th><th>Fin</th><th>Acción</th></tr></thead>
            <tbody>
                @foreach ($elementos as $suspension)
                    <tr>
                        <td>{{ $suspension->jugador->codigo_cliente }} · {{ $suspension->jugador->nombre_completo }}</td>
                        <td>{{ $suspension->motivo->etiqueta() }}</td>
                        <td>{{ $suspension->inicio->timezone(config('app.timezone'))->format('d/m/Y H:i') }}</td>
                        <td>{{ $suspension->fin?->timezone(config('app.timezone'))->format('d/m/Y H:i') ?? 'Indefinido' }}</td>
                        <td>
                            @if ($activa && $suspension->motivo === \App\Enums\MotivoSuspension::Investigacion)
                                <form method="POST" action="{{ route('suspensiones.finalizar', $suspension) }}" onsubmit="return confirm('¿Finalizar esta investigación?')">
                                    @csrf
                                    <button class="link-button" type="submit">Finalizar investigación</button>
                                </form>
                            @else
                                —
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
