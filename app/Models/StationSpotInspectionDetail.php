<?php

namespace App\Models;

use App\Enums\JudgementResult;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StationSpotInspectionDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'inspection_record_id',
        'part_hardware_mapping_id',
        'measurement_value',
        'auto_judgement',
        'remarks',
    ];

    protected function casts(): array
    {
        return [
            'measurement_value' => 'decimal:2',
            'auto_judgement' => JudgementResult::class,
        ];
    }

    public function inspectionRecord(): BelongsTo
    {
        return $this->belongsTo(InspectionRecord::class);
    }

    public function partHardwareMapping(): BelongsTo
    {
        return $this->belongsTo(PartHardwareMapping::class);
    }
}
