@extends('layouts.guest')

@section('title', 'Iniciar sesión')

@section('content')
    <section class="card login-card">
        <h1>
            Iniciar sesión
        </h1>
        <p class="muted">
            Ingresa con tu nombre de usuario y contraseña.
        </p>
        <form id="login-form" method="POST" action="{{ route('login') }}" novalidate>
            @csrf
            <input type="hidden" name="nonce" value="{{ $nonce }}">
            <input type="hidden" name="respuesta" value="">
            <label for="username">
                Usuario
            </label>
            <input id="username" name="username" type="text" value="{{ old('username') }}" autocomplete="username" required autofocus>
            <label for="password">
                Contraseña
            </label>
            <input id="password" type="password" autocomplete="current-password" required>
            <p id="zk-error" class="message message-error" role="alert" hidden>
            </p>
            <button class="button button-primary" type="submit">
                Entrar
            </button>
        </form>
    </section>
    <script src="{{ asset('js/zk.js') }}" defer>
    </script>
@endsection
