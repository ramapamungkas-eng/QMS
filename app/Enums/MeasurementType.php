<?php

namespace App\Enums;

enum MeasurementType: string
{
    case Torque = 'torque';
    case Nugget = 'nugget';

    public function label(): string
    {
        return match ($this) {
            self::Torque => 'Torque',
            self::Nugget => 'Nugget',
        };
    }

    public function defaultUnit(): string
    {
        return match ($this) {
            self::Torque => 'Nm',
            self::Nugget => 'mm',
        };
    }
}
