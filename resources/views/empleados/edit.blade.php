@extends('layouts.app')

@section('title', 'Editar empleado')

@section('content')
    <section class="card">
        <h1>
            Editar empleado
        </h1>
        @include('empleados.form', ['action' => route('empleados.update', $empleado), 'method' => 'PUT'])
    </section>
@endsection
