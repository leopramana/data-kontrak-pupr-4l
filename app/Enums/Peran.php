<?php

namespace App\Enums;

enum Peran: string
{
    case Admin = 'admin';
    case Walidata = 'walidata';
    case Operator = 'operator';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Admin',
            self::Walidata => 'Walidata',
            self::Operator => 'Operator OPD',
        };
    }
}
