@extends('layouts.app')

@section('title', 'Mi historial')

@section('content')
    @include('jugadores.partials.historial', ['titulo' => 'Mi historial'])
@endsection
