<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PortableSpotInspectionDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'inspection_record_id',
        'is_ok',
        'remarks',
    ];

    protected function casts(): array
    {
        return [
            'is_ok' => 'boolean',
        ];
    }

    public function inspectionRecord(): BelongsTo
    {
        return $this->belongsTo(InspectionRecord::class);
    }
}
