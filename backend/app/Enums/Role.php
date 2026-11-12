<?php

namespace App\Enums;

enum Role: string
{
    case Estudiante = 'estudiante';
    case Admin = 'admin';

    /** @return list<string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
