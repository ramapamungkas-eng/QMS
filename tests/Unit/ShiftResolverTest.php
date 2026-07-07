<?php

use App\Enums\Shift;
use App\Support\ShiftResolver;
use Carbon\CarbonImmutable;

it('resolves day shift for morning and afternoon', function (string $time) {
    [$shift, $date] = ShiftResolver::resolve(CarbonImmutable::parse($time));

    expect($shift)->toBe(Shift::Day);
    expect($date)->toBe('2026-07-07');
})->with([
    '07:30' => '2026-07-07 07:30:00',
    '12:00' => '2026-07-07 12:00:00',
    '19:59' => '2026-07-07 19:59:59',
]);

it('resolves night shift for evening', function (string $time) {
    [$shift, $date] = ShiftResolver::resolve(CarbonImmutable::parse($time));

    expect($shift)->toBe(Shift::Night);
    expect($date)->toBe('2026-07-07');
})->with([
    '20:00' => '2026-07-07 20:00:00',
    '23:59' => '2026-07-07 23:59:59',
]);

it('resolves previous day for early morning night shift', function (string $time, string $expectedDate) {
    [$shift, $date] = ShiftResolver::resolve(CarbonImmutable::parse($time));

    expect($shift)->toBe(Shift::Night);
    expect($date)->toBe($expectedDate);
})->with([
    '00:00' => ['2026-07-07 00:00:00', '2026-07-06'],
    '07:29' => ['2026-07-07 07:29:59', '2026-07-06'],
]);
