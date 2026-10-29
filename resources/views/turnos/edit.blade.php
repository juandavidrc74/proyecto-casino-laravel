@extends('layouts.app')

@section('title', 'Editar turno')

@section('content')
    <h1>
        Editar turno
    </h1>
    @include('turnos.form', ['action' => route('turnos.update', $turno), 'method' => 'PUT'])
@endsection
