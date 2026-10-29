@extends('layouts.app')

@section('title', 'Registrar empleado')

@section('content')
    <section class="card">
        <h1>
            Registrar empleado
        </h1>
        @include('empleados.form', ['action' => route('empleados.store'), 'method' => 'POST'])
    </section>
@endsection
