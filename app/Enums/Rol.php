<?php

namespace App\Enums;

enum Rol: string
{
    case Administrador = 'administrador';
    case Crupier = 'crupier';
    case Supervisor = 'supervisor';
    case Jugador = 'jugador';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Administrador => 'Administrador',
            self::Crupier => 'Crupier',
            self::Supervisor => 'Supervisor',
            self::Jugador => 'Jugador',
        };
    }
}
