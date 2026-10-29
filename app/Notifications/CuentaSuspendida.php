<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

class CuentaSuspendida extends Notification
{
    public function __construct(private string $motivo, private mixed $fin, private ?string $jugador = null) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $fin = $this->fin?->timezone(config('app.timezone'))->format('d/m/Y H:i');
        $detalle = $this->jugador ? 'La cuenta de '.$this->jugador.' fue suspendida por '.$this->motivo : 'Tu cuenta fue suspendida por '.$this->motivo;

        return [
            'mensaje' => $detalle.($fin ? ' hasta '.$fin.'.' : '.'),
            'motivo' => $this->motivo,
            'fin' => $fin,
            'jugador' => $this->jugador,
        ];
    }
}
