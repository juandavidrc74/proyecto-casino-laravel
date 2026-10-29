@extends('layouts.app')

@section('title', 'Registrar turno')

@section('content')
    <h1>
        Registrar turno
    </h1>
    @include('turnos.form', ['action' => route('turnos.store'), 'method' => 'POST'])
@endsection
