<?php

namespace App\Models;

use Database\Factories\MeasurementStandardFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MeasurementStandard extends Model
{
    /** @use HasFactory<MeasurementStandardFactory> */
    use HasFactory;

    protected $fillable = ['part_hardware_mapping_id', 'min_value', 'max_value', 'unit'];

    protected function casts(): array
    {
        return [
            'min_value' => 'decimal:2',
            'max_value' => 'decimal:2',
        ];
    }

    /** @return BelongsTo<PartHardwareMapping, $this> */
    public function partHardwareMapping(): BelongsTo
    {
        return $this->belongsTo(PartHardwareMapping::class);
    }
}
