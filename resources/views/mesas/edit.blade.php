@extends('layouts.app')

@section('title', 'Editar mesa')

@section('content')
    <h1>
        Editar mesa {{ $mesa->codigo }}
    </h1>
    @include('mesas.form', ['action' => route('mesas.update', $mesa), 'method' => 'PUT'])
@endsection
