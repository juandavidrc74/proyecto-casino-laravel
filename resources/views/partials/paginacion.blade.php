@if ($paginator->hasPages())
    <nav class="pagination" role="navigation" aria-label="Paginación">
        @if ($paginator->previousPageUrl())
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev">« Anterior</a>
        @endif
        @if ($paginator->nextPageUrl())
            <a href="{{ $paginator->nextPageUrl() }}" rel="next">Siguiente »</a>
        @endif
    </nav>
@endif
