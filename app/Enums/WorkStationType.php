<?php

namespace App\Enums;

enum WorkStationType: string
{
    case Stamping = 'stamping';
    case StationSpot = 'station_spot';
    case PortableSpot = 'portable_spot';
    case RobotSpot = 'robot_spot';

    public function label(): string
    {
        return match ($this) {
            self::Stamping => 'Stamping',
            self::StationSpot => 'Station Spot',
            self::PortableSpot => 'Portable Spot',
            self::RobotSpot => 'Robot Spot',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Stamping => 'Physical stamping press line. Visual inspection + manual judgement for defects and jig specs.',
            self::StationSpot => 'Fixed welding station with automated gun. Torque or nugget measurement with min/max standards.',
            self::PortableSpot => 'Handheld welding gun with hammer-and-chisel tap test. Visual pass/fail check.',
            self::RobotSpot => 'Robotic welding arm. Visual check plus weld length measurement against standards.',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Stamping => 'o-cog',
            self::StationSpot => 'o-wrench-screwdriver',
            self::PortableSpot => 'o-hand-raised',
            self::RobotSpot => 'o-computer-desktop',
        };
    }
}
