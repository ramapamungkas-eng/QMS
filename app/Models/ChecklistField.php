<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property bool $has_auto_judge
 */
class ChecklistField extends Model
{
    protected $table = 'inspection_checklist_fields';

    protected $fillable = [
        'section_id',
        'field_key',
        'label',
        'field_type',
        'options',
        'required',
        'order',
        'has_auto_judge',
        'auto_judge_source',
        'min_value',
        'max_value',
        'unit',
    ];

    protected function casts(): array
    {
        return [
            'required' => 'boolean',
            'has_auto_judge' => 'boolean',
            'options' => 'array',
            'min_value' => 'decimal:2',
            'max_value' => 'decimal:2',
        ];
    }

    /** @return BelongsTo<ChecklistSection, $this> */
    public function section(): BelongsTo
    {
        return $this->belongsTo(ChecklistSection::class, 'section_id');
    }
}
