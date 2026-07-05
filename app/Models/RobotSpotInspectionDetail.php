<?php

namespace App\Models;

use App\Enums\JudgementResult;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RobotSpotInspectionDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'inspection_record_id',
        'weld_length',
        'auto_judgement',
        'jig_ok',
        'jig_remarks',
    ];

    protected function casts(): array
    {
        return [
            'weld_length' => 'decimal:2',
            'auto_judgement' => JudgementResult::class,
            'jig_ok' => 'boolean',
        ];
    }

    public function inspectionRecord(): BelongsTo
    {
        return $this->belongsTo(InspectionRecord::class);
    }
}
