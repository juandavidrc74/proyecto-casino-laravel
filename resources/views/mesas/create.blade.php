@extends('layouts.app')

@section('title', 'Registrar mesa')

@section('content')
    <h1>
        Registrar mesa
    </h1>
    @include('mesas.form', ['action' => route('mesas.store'), 'method' => 'POST'])
@endsection
