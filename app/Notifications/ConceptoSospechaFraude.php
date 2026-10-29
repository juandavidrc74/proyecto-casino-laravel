<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

class ConceptoSospechaFraude extends Notification
{
    public function __construct(private array $datos) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return $this->datos;
    }
}
