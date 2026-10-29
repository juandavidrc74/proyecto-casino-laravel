@extends('layouts.app')

@section('title', 'Suspensiones')

@section('content')
    <section class="card">
        <h1>Suspensiones activas</h1>
        @include('suspensiones.tabla', ['elementos' => $activas, 'activa' => true])
    </section>
    <section class="card">
        <h2>Historial</h2>
        @include('suspensiones.tabla', ['elementos' => $historial, 'activa' => false])
    </section>
@endsection
