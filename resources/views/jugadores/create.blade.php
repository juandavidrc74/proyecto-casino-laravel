@extends('layouts.app')

@section('title', 'Registrar jugador')

@section('content')
    <section class="card">
        <h1>
            Registrar jugador
        </h1>
        @if ($empleado)
            <p>
                Perfil vinculado a
                <strong>
                    {{ $empleado->nombre_completo }}
                </strong>
                (documento {{ $empleado->documento }}).
            </p>
        @endif
        @include('jugadores.form', ['action' => route('jugadores.store'), 'method' => 'POST'])
    </section>
@endsection
