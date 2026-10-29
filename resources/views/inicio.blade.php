@extends('layouts.app')

@section('title', 'Inicio')

@section('content')
    <section class="card">
        <h1>
            Inicio
        </h1>
        <p>
            Sesión iniciada como
            <strong>
                {{ auth()->user()->username }}
            </strong>
            ({{ auth()->user()->rol->etiqueta() }})
        </p>
    </section>
@endsection
