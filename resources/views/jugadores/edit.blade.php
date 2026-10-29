@extends('layouts.app')

@section('title', 'Editar jugador')

@section('content')
    <section class="card">
        <h1>
            Editar jugador
        </h1>
        @include('jugadores.form', ['action' => route('jugadores.update', $jugador), 'method' => 'PUT', 'empleado' => $jugador->user->empleado])
    </section>
@endsection
