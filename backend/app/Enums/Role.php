<?php

namespace App\Enums;

/** Roles del sistema. Estudiante: publica y borra lo suyo. Admin: edita y borra cualquier proyecto y gestiona usuarios. */
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
