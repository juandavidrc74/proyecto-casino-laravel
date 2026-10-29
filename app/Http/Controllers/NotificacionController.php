<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class NotificacionController extends Controller
{
    public function index(Request $request)
    {
        $notificaciones = $request->user()->notifications()->latest()->simplePaginate(20);

        return view('notificaciones.index', compact('notificaciones'));
    }

    public function marcarComoLeida(Request $request, string $notificacion)
    {
        $registro = $request->user()->notifications()->whereKey($notificacion)->firstOrFail();
        $registro->markAsRead();

        return back()->with('status', 'Notificación marcada como leída.');
    }

    public function marcarTodasComoLeidas(Request $request)
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return back()->with('status', 'Todas las notificaciones fueron marcadas como leídas.');
    }
}
