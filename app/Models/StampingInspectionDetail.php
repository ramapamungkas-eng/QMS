<?php

namespace App\Models;

use App\Enums\JudgementResult;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StampingInspectionDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'inspection_record_id',
        'is_defect',
        'defect_remarks',
        'jig_spec_ok',
        'jig_remarks',
        'manual_judgement',
        'judgement_remarks',
    ];

    protected function casts(): array
    {
        return [
            'is_defect' => 'boolean',
            'jig_spec_ok' => 'boolean',
            'manual_judgement' => JudgementResult::class,
        ];
    }

    public function inspectionRecord(): BelongsTo
    {
        return $this->belongsTo(InspectionRecord::class);
    }

    public function passed(): bool
    {
        return ! $this->is_defect
            && $this->jig_spec_ok
            && $this->manual_judgement === JudgementResult::Ok;
    }
}
