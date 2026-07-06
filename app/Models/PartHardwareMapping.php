<?php

namespace App\Models;

use App\Enums\MeasurementType;
use Database\Factories\PartHardwareMappingFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PartHardwareMapping extends Model
{
    /** @use HasFactory<PartHardwareMappingFactory> */
    use HasFactory;

    protected $fillable = ['part_id', 'hardware_type_id', 'measurement_type', 'usage_qty'];

    protected function casts(): array
    {
        return [
            'measurement_type' => MeasurementType::class,
            'usage_qty' => 'integer',
        ];
    }

    /** @return BelongsTo<Part, $this> */
    public function part(): BelongsTo
    {
        return $this->belongsTo(Part::class);
    }

    /** @return BelongsTo<HardwareType, $this> */
    public function hardwareType(): BelongsTo
    {
        return $this->belongsTo(HardwareType::class);
    }

    /** @return HasOne<MeasurementStandard, $this> */
    public function measurementStandard(): HasOne
    {
        return $this->hasOne(MeasurementStandard::class);
    }
}
