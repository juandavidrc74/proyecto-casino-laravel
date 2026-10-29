<?php

namespace App\Notifications;

use App\Models\Apuesta;
use Illuminate\Notifications\Notification;

class ApuestaResuelta extends Notification
{
    public function __construct(private Apuesta $apuesta, private string $saldoResultante) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $estado = $this->apuesta->estado->value;

        return [
            'mensaje' => 'Tu apuesta de la ronda '.$this->apuesta->ronda->numero.' fue resuelta: '.$estado.'.',
            'mesa' => $this->apuesta->ronda->mesa->codigo,
            'ronda' => $this->apuesta->ronda->numero,
            'tipo' => $this->apuesta->tipo_apuesta,
            'monto' => $this->apuesta->monto,
            'estado' => $estado,
            'premio' => $this->apuesta->premio,
            'saldo_resultante' => $this->saldoResultante,
        ];
    }
}
