<?php

use App\Http\Controllers\AbonoController;
use App\Http\Controllers\ApuestaController;
use App\Http\Controllers\AsignacionController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\ConceptoController;
use App\Http\Controllers\EmpleadoController;
use App\Http\Controllers\GananciaController;
use App\Http\Controllers\InicioController;
use App\Http\Controllers\JugadorController;
use App\Http\Controllers\MesaController;
use App\Http\Controllers\MiCuentaController;
use App\Http\Controllers\MiHistorialController;
use App\Http\Controllers\MiMesaController;
use App\Http\Controllers\NotificacionController;
use App\Http\Controllers\RondaController;
use App\Http\Controllers\SuspensionController;
use App\Http\Controllers\TurnoController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('inicio'));
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store']);
});
Route::middleware('auth')->group(function () {
    Route::get('/inicio', InicioController::class)->name('inicio');
    Route::get('/mi-cuenta', [MiCuentaController::class, 'index'])->name('mi-cuenta');
    Route::post('/mi-cuenta/pausa', [MiCuentaController::class, 'pausar'])->name('mi-cuenta.pausa');
    Route::get('/mi-historial', MiHistorialController::class)->name('mi-historial');
    Route::get('/notificaciones', [NotificacionController::class, 'index'])->name('notificaciones.index');
    Route::post('/notificaciones/marcar-todas', [NotificacionController::class, 'marcarTodasComoLeidas'])->name('notificaciones.marcar-todas');
    Route::post('/notificaciones/{notificacion}/leer', [NotificacionController::class, 'marcarComoLeida'])->name('notificaciones.leer');
    Route::post('/logout', LogoutController::class)->name('logout');
    Route::middleware('rol:administrador')->group(function () {
        Route::resource('empleados', EmpleadoController::class)->except('show');
        Route::resource('jugadores', JugadorController::class)->parameters(['jugadores' => 'jugador']);
        Route::get('/jugadores/{jugador}/abonos', [AbonoController::class, 'index'])->name('jugadores.abonos.index');
        Route::post('/jugadores/{jugador}/abonos', [AbonoController::class, 'store'])->name('jugadores.abonos.store');
        Route::get('/suspensiones', [SuspensionController::class, 'index'])->name('suspensiones.index');
        Route::post('/suspensiones/{suspension}/finalizar', [SuspensionController::class, 'finalizar'])->name('suspensiones.finalizar');
        Route::resource('mesas', MesaController::class)->except('show');
        Route::resource('turnos', TurnoController::class);
        Route::post('/turnos/{turno}/asignaciones', [AsignacionController::class, 'store'])->name('turnos.asignaciones.store');
        Route::delete('/turnos/{turno}/asignaciones/{asignacion}', [AsignacionController::class, 'destroy'])->name('turnos.asignaciones.destroy');
    });
    Route::middleware('rol:supervisor')->group(function () {
        Route::get('/mis-conceptos', [ConceptoController::class, 'index'])->name('conceptos.index');
        Route::post('/mis-conceptos/{asignacion}', [ConceptoController::class, 'store'])->name('conceptos.store');
    });
    Route::middleware('rol:crupier')->group(function () {
        Route::get('/mi-mesa', MiMesaController::class)->name('mi-mesa');
        Route::post('/mesas/{mesa}/rondas', [RondaController::class, 'store'])->name('rondas.store');
        Route::delete('/rondas/{ronda}', [RondaController::class, 'destroy'])->name('rondas.destroy');
        Route::post('/rondas/{ronda}/cerrar', [RondaController::class, 'cerrar'])->name('rondas.cerrar');
        Route::post('/rondas/{ronda}/apuestas', [ApuestaController::class, 'store'])->name('apuestas.store');
        Route::get('/apuestas/{apuesta}/editar', [ApuestaController::class, 'edit'])->name('apuestas.edit');
        Route::put('/apuestas/{apuesta}', [ApuestaController::class, 'update'])->name('apuestas.update');
        Route::delete('/apuestas/{apuesta}', [ApuestaController::class, 'destroy'])->name('apuestas.destroy');
    });
    Route::middleware('rol:administrador,supervisor')->get('/ganancias', [GananciaController::class, 'index'])->name('ganancias.index');
});
