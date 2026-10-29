<?php

namespace App\Enums;

enum Concepto: string
{
    case GeneraGanancias = 'Genera ganancias';
    case GeneraPerdidas = 'Genera pérdidas';
    case SospechaFraude = 'Sospecha de fraude';
    case ComportamientoNeutro = 'Comportamiento neutro';
}
