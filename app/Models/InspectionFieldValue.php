<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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

    public function inspectionRecord(): BelongsTo
    {
        return $this->belongsTo(InspectionRecord::class);
    }

    public function field(): BelongsTo
    {
        return $this->belongsTo(ChecklistField::class, 'field_id');
    }

    public function source(): BelongsTo
    {
        return $this->belongsTo(PartHardwareMapping::class, 'source_id');
    }
}
