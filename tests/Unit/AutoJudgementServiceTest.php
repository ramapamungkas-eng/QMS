<?php

use App\Models\ChecklistField;
use App\Models\HardwareType;
use App\Models\InspectionRecord;
use App\Models\MeasurementStandard;
use App\Models\Part;
use App\Models\PartHardwareMapping;
use App\Models\WeldLengthStandard;
use App\Models\WorkStation;
use App\Services\AutoJudgementService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->service = app(AutoJudgementService::class);
});

function makeField(array $attributes = []): ChecklistField
{
    return new ChecklistField(array_merge([
        'section_id' => 1,
        'field_key' => 'test_field',
        'label' => 'Test Field',
        'field_type' => 'numeric',
        'required' => false,
        'order' => 1,
        'has_auto_judge' => false,
    ], $attributes));
}

it('returns null for fields without auto judge', function () {
    $field = makeField();

    expect($this->service->judge($field, 'anything'))->toBeNull();
});

it('judges by limits', function () {
    $field = makeField([
        'has_auto_judge' => true,
        'auto_judge_source' => 'limits',
        'min_value' => 10,
        'max_value' => 20,
    ]);

    expect($this->service->judge($field, 15))->toBe('ok');
    expect($this->service->judge($field, 5))->toBe('ng');
    expect($this->service->judge($field, 25))->toBe('ng');
});

it('judges by measurement standard', function () {
    $part = Part::factory()->create();
    $hardware = HardwareType::factory()->create();
    $mapping = PartHardwareMapping::factory()->create([
        'part_id' => $part->id,
        'hardware_type_id' => $hardware->id,
    ]);

    MeasurementStandard::factory()->create([
        'part_hardware_mapping_id' => $mapping->id,
        'min_value' => 10,
        'max_value' => 20,
        'unit' => 'Nm',
    ]);

    $field = makeField([
        'has_auto_judge' => true,
        'auto_judge_source' => 'measurement_standard',
    ]);

    expect($this->service->judge($field, 15, $mapping->id))->toBe('ok');
    expect($this->service->judge($field, 5, $mapping->id))->toBe('ng');
});

it('judges by weld length standard', function () {
    $part = Part::factory()->create();
    $workStation = WorkStation::factory()->create();

    WeldLengthStandard::factory()->create([
        'part_id' => $part->id,
        'work_station_id' => $workStation->id,
        'min_length' => 10,
        'max_length' => 20,
    ]);

    $field = makeField([
        'has_auto_judge' => true,
        'auto_judge_source' => 'weld_length_standard',
    ]);

    $record = InspectionRecord::factory()->make([
        'part_id' => $part->id,
        'work_station_id' => $workStation->id,
    ]);

    expect($this->service->judge($field, 15, null, $record))->toBe('ok');
    expect($this->service->judge($field, 5, null, $record))->toBe('ng');
});
