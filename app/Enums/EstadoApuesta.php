<?php

namespace App\Enums;

enum EstadoApuesta: string
{
    case Pendiente = 'Pendiente';
    case Ganada = 'Ganada';
    case Perdida = 'Perdida';
}
