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
    <body class="guest-page">
        <header class="guest-header">
            <span class="brand">
                {{ config('app.name') }}
            </span>
        </header>
        <main class="guest-content">
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
    </body>
</html>
