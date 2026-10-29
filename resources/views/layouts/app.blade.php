<!doctype html>
<html lang="es">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>
            @yield('title', config('app.name')) · {{ config('app.name') }}
        </title>
        <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    </head>
    <body>
        <header class="site-header">
            <a class="brand" href="{{ route('inicio') }}">
                {{ config('app.name') }}
            </a>
            <div class="header-account">
                <a class="notification-link" href="{{ route('notificaciones.index') }}">
                    Notificaciones <span class="notification-count">{{ auth()->user()->unreadNotifications()->count() }}</span>
                </a>
                <span>
                    {{ auth()->user()->username }} · {{ auth()->user()->rol->etiqueta() }}
                </span>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="button button-secondary" type="submit">
                        Cerrar sesión
                    </button>
                </form>
            </div>
        </header>
        <div class="shell">
            @include('layouts.partials.menu')
            <main class="main-content">
                @if (session('status'))
                    <div class="message message-success" role="status">
                        {{ session('status') }}
                    </div>
                @endif
                @if ($errors->any())
                    <div class="message message-error" role="alert">
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>
                                    {{ $error }}
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                @yield('content')
            </main>
        </div>
    </body>
</html>
