<?php

namespace App\Services;

use App\Models\ChecklistField;
use App\Models\InspectionFieldValue;
use Illuminate\Support\Collection;

class InspectionJudgementService
{
    /**
     * Determine the overall stage-level judgement from a collection of field values.
     *
     * Precedence: auto-judged fields → enum fields → boolean fields.
     *
     * @param  Collection<int, InspectionFieldValue>  $fieldValues
     */
    public function stageOverall(Collection $fieldValues): ?string
    {
        $autoJudgements = $fieldValues
            ->filter(fn (InspectionFieldValue $fv): bool => (bool) $fv->field?->has_auto_judge)
            ->pluck('auto_judgement')
            ->filter();

        if ($autoJudgements->isNotEmpty()) {
            if ($autoJudgements->contains('ng')) {
                return 'ng';
            }

            return $autoJudgements->every(fn ($j) => $j === 'ok') ? 'ok' : null;
        }

        $enumValues = $fieldValues
            ->where('field.field_type', 'enum')
            ->pluck('value')
            ->filter();

        if ($enumValues->isNotEmpty()) {
            $lower = $enumValues->map(fn ($v) => strtolower($v));

            if ($lower->contains('ng')) {
                return 'ng';
            }

            if ($lower->contains('repair')) {
                return 'repair';
            }

            return 'ok';
        }

        $booleans = $fieldValues
            ->where('field.field_type', 'boolean')
            ->pluck('value')
            ->filter(fn ($v) => $v !== null && $v !== '');

        if ($booleans->isNotEmpty()) {
            return $booleans->every(fn ($v) => $v === '1') ? 'ok' : 'ng';
        }

        return null;
    }

    /**
     * Determine the detail-result badge for a single field value.
     *
     * Boolean fields use inverted logic for `is_defect`; all other booleans use standard logic.
     */
    public function detailResult(ChecklistField $field, mixed $value, ?string $autoJudgement = null): ?string
    {
        if ($autoJudgement !== null) {
            return $autoJudgement;
        }

        return match ($field->field_type) {
            'enum' => match (strtolower((string) $value)) {
                'ok' => 'ok',
                'ng' => 'ng',
                'repair' => 'repair',
                default => null,
            },
            'boolean' => $this->booleanDetailResult($field, $value),
            default => null,
        };
    }

    protected function booleanDetailResult(ChecklistField $field, mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($field->field_key === 'is_defect') {
            return $value === '0' ? 'ok' : 'ng';
        }

        return $value === '1' ? 'ok' : 'ng';
    }
}
