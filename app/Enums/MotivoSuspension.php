<?php

namespace App\Enums;

enum MotivoSuspension: string
{
    case PerdidaDiaria = 'perdida_diaria';
    case PausaVoluntaria = 'pausa_voluntaria';
    case PerdidaEmpleado = 'perdida_empleado';
    case Investigacion = 'investigacion';

    public function etiqueta(): string
    {
        return match ($this) {
            self::PerdidaDiaria => 'Pérdida diaria',
            self::PausaVoluntaria => 'Pausa voluntaria',
            self::PerdidaEmpleado => 'Pérdida de empleado',
            self::Investigacion => 'Investigación',
        };
    }
}
