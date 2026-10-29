<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

class CuentaReactivada extends Notification
{
    public function __construct(private string $mensaje) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return ['mensaje' => $this->mensaje];
    }
}
