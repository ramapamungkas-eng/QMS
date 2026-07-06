<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read ChecklistField|null $field
 */
class InspectionFieldValue extends Model
{
    protected $table = 'inspection_field_values';

    protected $fillable = [
        'inspection_record_id',
        'field_id',
        'value',
        'auto_judgement',
        'remarks',
        'group_index',
        'source_id',
    ];

    /** @return BelongsTo<InspectionRecord, $this> */
    public function inspectionRecord(): BelongsTo
    {
        return $this->belongsTo(InspectionRecord::class);
    }

    /** @return BelongsTo<ChecklistField, $this> */
    public function field(): BelongsTo
    {
        return $this->belongsTo(ChecklistField::class, 'field_id');
    }

    /** @return BelongsTo<PartHardwareMapping, $this> */
    public function source(): BelongsTo
    {
        return $this->belongsTo(PartHardwareMapping::class, 'source_id');
    }
}
