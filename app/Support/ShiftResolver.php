<?php

namespace App\Support;

use App\Enums\Shift;
use Carbon\CarbonInterface;

class ShiftResolver
{
    /**
     * Resolve which shift a given timestamp falls in, and what "production date"
     * it should be attributed to.
     *
     * Day shift:   07:30–20:00 -> production_date = same calendar day
     * Night shift: 20:00–07:30 (crosses midnight) -> production_date = the day the
     *              night shift *started* on, not the calendar day the checker is
     *              physically standing in when they submit.
     *
     * @return array{0: Shift, 1: string} [Shift enum, production_date as Y-m-d]
     */
    public static function resolve(CarbonInterface $time): array
    {
        $time = $time->copy();

        $dayStart = $time->copy()->setTimeFromTimeString('07:30:00');
        $nightStart = $time->copy()->setTimeFromTimeString('20:00:00');

        if ($time->gte($dayStart) && $time->lt($nightStart)) {
            return [Shift::Day, $time->toDateString()];
        }

        if ($time->gte($nightStart)) {
            return [Shift::Night, $time->toDateString()];
        }

        // Between 00:00 and 07:30 — still part of the night shift that started yesterday.
        return [Shift::Night, $time->copy()->subDay()->toDateString()];
    }
}
