<?php

namespace App\Enums;

enum Shift: string
{
    case Day = 'day';
    case Night = 'night';

    public function label(): string
    {
        return match ($this) {
            self::Day => 'Day Shift',
            self::Night => 'Night Shift',
        };
    }
}
