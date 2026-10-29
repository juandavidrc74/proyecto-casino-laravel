@extends('layouts.app')

@section('title', 'Notificaciones')

@section('content')
    <section class="card">
        <div class="page-heading">
            <h1>Notificaciones</h1>
            @if (auth()->user()->unreadNotifications()->exists())
                <form method="POST" action="{{ route('notificaciones.marcar-todas') }}">
                    @csrf
                    <button class="button button-secondary" type="submit">Marcar todas como leídas</button>
                </form>
            @endif
        </div>
        @forelse ($notificaciones as $notificacion)
            <article class="notification-item {{ $notificacion->read_at ? '' : 'notification-unread' }}">
                <p>{{ $notificacion->data['mensaje'] ?? 'Notificación' }}</p>
                <p class="muted">{{ $notificacion->created_at->timezone(config('app.timezone'))->format('d/m/Y H:i') }}</p>
                @if (! $notificacion->read_at)
                    <form method="POST" action="{{ route('notificaciones.leer', $notificacion->id) }}">
                        @csrf
                        <button class="link-button" type="submit">Marcar como leída</button>
                    </form>
                @endif
            </article>
        @empty
            <p class="muted">No tienes notificaciones.</p>
        @endforelse
        {{ $notificaciones->links('partials.paginacion') }}
    </section>
@endsection
