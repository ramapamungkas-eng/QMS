<?php

namespace App\Enums;

enum Shift: string
{
    case Day = 'day';
    case Night = 'night';

    public function label(): string
    {
        return match ($this) {
            self::Day => 'Day Shift (07:30–20:00)',
            self::Night => 'Night Shift (20:00–07:30)',
        };
    }
}
