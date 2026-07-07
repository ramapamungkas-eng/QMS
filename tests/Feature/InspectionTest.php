<?php

use App\Enums\InspectionStage;
use App\Enums\Shift;
use App\Models\ChecklistTemplate;
use App\Models\InspectionFieldValue;
use App\Models\InspectionRecord;
use App\Models\Part;
use App\Models\Process;
use App\Models\StationType;
use App\Models\WorkStation;
use App\Services\InspectionJudgementService;
use Carbon\CarbonImmutable;

beforeEach(function () {
    seedApplication();

    $this->stamping = Process::where('name', 'Stamping')->first();
    $this->stampingType = StationType::where('slug', 'stamping')->first();
    $this->workStation = WorkStation::where('station_type_id', $this->stampingType->id)->first();
    $this->part = Part::whereHas('stationTypes', fn ($q) => $q->where('station_type_id', $this->stampingType->id))->first();
    $this->checker = checkerUser($this->stamping);
});

it('loads the inspection create page', function () {
    $this->actingAs($this->checker)
        ->get(route('inspections.stamping.create'))
        ->assertSuccessful();
});

it('auto sets checker shift and production date on inspection record creation', function () {
    $record = InspectionRecord::create([
        'part_id' => $this->part->id,
        'work_station_id' => $this->workStation->id,
        'stage' => InspectionStage::Start,
        'checker_id' => $this->checker->id,
    ]);

    expect($record->checker_id)->toBe($this->checker->id);
    expect($record->shift)->toBe(Shift::Day);
    expect($record->production_date)->not->toBeNull();
});

it('resolves night shift for late evening submissions', function () {
    $record = InspectionRecord::create([
        'part_id' => $this->part->id,
        'work_station_id' => $this->workStation->id,
        'stage' => InspectionStage::Start,
        'checker_id' => $this->checker->id,
        'checked_at' => CarbonImmutable::parse('2026-07-07 21:00:00'),
    ]);

    expect($record->shift)->toBe(Shift::Night);
    expect($record->production_date->format('Y-m-d'))->toBe('2026-07-07');
});

it('resolves previous day production date for early morning night shift', function () {
    $record = InspectionRecord::create([
        'part_id' => $this->part->id,
        'work_station_id' => $this->workStation->id,
        'stage' => InspectionStage::Start,
        'checker_id' => $this->checker->id,
        'checked_at' => CarbonImmutable::parse('2026-07-07 05:00:00'),
    ]);

    expect($record->shift)->toBe(Shift::Night);
    expect($record->production_date->format('Y-m-d'))->toBe('2026-07-06');
});

it('detects recheck confirmation when part already has an ok record', function () {
    $record = InspectionRecord::create([
        'part_id' => $this->part->id,
        'work_station_id' => $this->workStation->id,
        'stage' => InspectionStage::Start,
        'checker_id' => $this->checker->id,
    ]);

    $template = ChecklistTemplate::where('station_type_id', $this->stampingType->id)->first();
    $section = $template->sections()->where('label', 'Final Judgement')->first();
    $field = $section->fields()->where('field_key', 'manual_judgement')->first();

    InspectionFieldValue::create([
        'inspection_record_id' => $record->id,
        'field_id' => $field->id,
        'value' => 'OK',
    ]);

    $exists = InspectionRecord::where('part_id', $this->part->id)
        ->where('work_station_id', $this->workStation->id)
        ->where('stage', InspectionStage::Start->value)
        ->whereDate('production_date', now()->toDateString())
        ->whereHas('fieldValues', fn ($q) => $q->where('value', 'OK'))
        ->exists();

    expect($exists)->toBeTrue();
});

it('derives overall judgement from field values', function () {
    $service = app(InspectionJudgementService::class);

    $record = InspectionRecord::create([
        'part_id' => $this->part->id,
        'work_station_id' => $this->workStation->id,
        'stage' => InspectionStage::Start,
        'checker_id' => $this->checker->id,
    ]);

    $template = ChecklistTemplate::where('station_type_id', $this->stampingType->id)->first();
    $section = $template->sections()->where('label', 'Final Judgement')->first();
    $field = $section->fields()->where('field_key', 'manual_judgement')->first();

    InspectionFieldValue::create([
        'inspection_record_id' => $record->id,
        'field_id' => $field->id,
        'value' => 'NG',
    ]);

    $judgement = $service->stageOverall($record->fresh()->fieldValues);

    expect($judgement)->toBe('ng');
});
