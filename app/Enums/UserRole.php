<?php

namespace App\Enums;

enum UserRole: string
{
    case User = 'user';
    case WorkshopOwner = 'workshop_owner';
    case Admin = 'admin';

    public function label(): string
    {
        return match ($this) {
            self::User => 'Usuario',
            self::WorkshopOwner => 'Dueño de taller',
            self::Admin => 'Administrador',
        };
    }
}
