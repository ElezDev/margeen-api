<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case Vendedor = 'vendedor';

    public function isAdmin(): bool
    {
        return $this === self::Admin;
    }
}
