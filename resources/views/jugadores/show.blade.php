@extends('layouts.app')

@section('title', 'Historial del jugador')

@section('content')
    @include('jugadores.partials.historial', ['titulo' => 'Historial del jugador'])
@endsection
