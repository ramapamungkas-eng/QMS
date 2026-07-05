<?php

namespace App\Enums;

enum JudgementResult: string
{
    case Ok = 'ok';
    case Ng = 'ng';
    case Repair = 'repair';

    public function label(): string
    {
        return match ($this) {
            self::Ok => 'OK',
            self::Ng => 'NG',
            self::Repair => 'REPAIR',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Ok => 'badge-success',
            self::Ng => 'badge-error',
            self::Repair => 'badge-warning',
        };
    }
}
