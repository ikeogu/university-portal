<?php

namespace App\Enums;

enum Semester: int
{
    case First = 1;
    case Second = 2;

    public function label(): string
    {
        return match ($this) {
            self::First => 'First',
            self::Second => 'Second',
        };
    }
}
