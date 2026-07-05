<?php

namespace App\Enums;

enum InspectionStage: string
{
    case Start = 'start';
    case Middle = 'middle';
    case End = 'end';

    public function label(): string
    {
        return match ($this) {
            self::Start => 'Start',
            self::Middle => 'Middle',
            self::End => 'End',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Start => 'Initial check when production begins. Verify first-off panel quality and jig setup.',
            self::Middle => 'In-process check during production run. Verify ongoing consistency.',
            self::End => 'Final check at end of run or shift. Verify last-off quality before handover.',
        };
    }
}
