<?php

namespace App\Services;

use App\Models\ChecklistField;
use App\Models\InspectionRecord;
use App\Models\MeasurementStandard;
use App\Models\WeldLengthStandard;

class AutoJudgementService
{
    public function judge(ChecklistField $field, mixed $value, ?int $sourceId = null, ?InspectionRecord $record = null): ?string
    {
        if (! $field->has_auto_judge) {
            return null;
        }

        return match ($field->auto_judge_source) {
            'limits' => $this->judgeByLimits($field, $value),
            'measurement_standard' => $this->judgeByMeasurementStandard($field, $value, $sourceId),
            'weld_length_standard' => $this->judgeByWeldLengthStandard($field, $value, $record),
            default => null,
        };
    }

    protected function judgeByLimits(ChecklistField $field, mixed $value): string
    {
        $floatVal = (float) $value;

        if ($field->min_value !== null && $floatVal < (float) $field->min_value) {
            return 'ng';
        }

        if ($field->max_value !== null && $floatVal > (float) $field->max_value) {
            return 'ng';
        }

        return 'ok';
    }

    protected function judgeByMeasurementStandard(ChecklistField $field, mixed $value, ?int $sourceId): string
    {
        if ($sourceId === null) {
            return 'ng';
        }

        $standard = MeasurementStandard::where('part_hardware_mapping_id', $sourceId)->first();

        if ($standard === null) {
            return 'ok';
        }

        $floatVal = (float) $value;

        if ($floatVal < (float) $standard->min_value || $floatVal > (float) $standard->max_value) {
            return 'ng';
        }

        return 'ok';
    }

    protected function judgeByWeldLengthStandard(ChecklistField $field, mixed $value, ?InspectionRecord $record): string
    {
        if ($record === null) {
            return 'ng';
        }

        $standard = WeldLengthStandard::where('part_id', $record->part_id)
            ->where('work_station_id', $record->work_station_id)
            ->first();

        if ($standard === null) {
            return 'ok';
        }

        $floatVal = (float) $value;

        if ($floatVal < (float) $standard->min_length || $floatVal > (float) $standard->max_length) {
            return 'ng';
        }

        return 'ok';
    }
}
