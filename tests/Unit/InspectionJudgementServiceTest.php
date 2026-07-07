<?php

use App\Models\ChecklistField;
use App\Models\InspectionFieldValue;
use App\Services\InspectionJudgementService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->service = app(InspectionJudgementService::class);
});

function makeJudgementField(array $attributes = []): ChecklistField
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

function makeFieldValue(ChecklistField $field, mixed $value = null, ?string $autoJudgement = null): InspectionFieldValue
{
    $fieldValue = new InspectionFieldValue([
        'value' => $value,
        'auto_judgement' => $autoJudgement,
    ]);
    $fieldValue->setRelation('field', $field);

    return $fieldValue;
}

it('returns null for empty field values', function () {
    expect($this->service->stageOverall(collect()))->toBeNull();
});

it('prefers auto judgement over enum and boolean', function () {
    $autoField = makeJudgementField(['has_auto_judge' => true]);
    $enumField = makeJudgementField(['field_type' => 'enum']);

    $values = collect([
        makeFieldValue($autoField, null, 'ok'),
        makeFieldValue($enumField, 'NG', null),
    ]);

    expect($this->service->stageOverall($values))->toBe('ok');
});

it('returns ng when any auto judgement is ng', function () {
    $field = makeJudgementField(['has_auto_judge' => true]);

    $values = collect([
        makeFieldValue($field, null, 'ok'),
        makeFieldValue($field, null, 'ng'),
    ]);

    expect($this->service->stageOverall($values))->toBe('ng');
});

it('falls back to enum values', function () {
    $field = makeJudgementField(['field_type' => 'enum']);

    $values = collect([
        makeFieldValue($field, 'OK', null),
    ]);

    expect($this->service->stageOverall($values))->toBe('ok');
});

it('falls back to boolean values', function () {
    $field = makeJudgementField(['field_type' => 'boolean']);

    $values = collect([
        makeFieldValue($field, '1', null),
    ]);

    expect($this->service->stageOverall($values))->toBe('ok');
});

it('inverts is_defect detail result', function () {
    $field = makeJudgementField([
        'field_type' => 'boolean',
        'field_key' => 'is_defect',
    ]);

    expect($this->service->detailResult($field, '0'))->toBe('ok');
    expect($this->service->detailResult($field, '1'))->toBe('ng');
});

it('uses standard boolean detail result for non is_defect fields', function () {
    $field = makeJudgementField([
        'field_type' => 'boolean',
        'field_key' => 'jig_spec_ok',
    ]);

    expect($this->service->detailResult($field, '1'))->toBe('ok');
    expect($this->service->detailResult($field, '0'))->toBe('ng');
});

it('uses auto judgement for detail result when present', function () {
    $field = makeJudgementField([
        'field_type' => 'numeric',
        'has_auto_judge' => true,
    ]);

    expect($this->service->detailResult($field, 15, 'ok'))->toBe('ok');
});
