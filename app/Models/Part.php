<?php

namespace App\Models;

use Database\Factories\PartFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Part extends Model
{
    /** @use HasFactory<PartFactory> */
    use HasFactory;

    protected $fillable = ['part_number', 'part_name', 'model', 'variant', 'image'];

    public function imageUrl(): string
    {
        return $this->image
            ? asset('storage/'.$this->image)
            : 'https://ui-avatars.com/api/?size=256&background=475569&color=fff&name='.urlencode($this->part_name);
    }

    /** @return HasMany<PartHardwareMapping, $this> */
    public function hardwareMappings(): HasMany
    {
        return $this->hasMany(PartHardwareMapping::class);
    }

    /** @return HasMany<WeldLengthStandard, $this> */
    public function weldLengthStandards(): HasMany
    {
        return $this->hasMany(WeldLengthStandard::class);
    }

    /** @return HasMany<InspectionRecord, $this> */
    public function inspectionRecords(): HasMany
    {
        return $this->hasMany(InspectionRecord::class);
    }

    /** @return HasMany<PartWorkStationType, $this> */
    public function stationTypes(): HasMany
    {
        return $this->hasMany(PartWorkStationType::class);
    }
}
