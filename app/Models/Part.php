<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Part extends Model
{
    use HasFactory;

    protected $fillable = ['part_number', 'part_name', 'model', 'variant', 'image'];

    public function imageUrl(): string
    {
        return $this->image
            ? asset('storage/'.$this->image)
            : 'https://ui-avatars.com/api/?size=256&background=475569&color=fff&name='.urlencode($this->part_name);
    }

    public function hardwareMappings(): HasMany
    {
        return $this->hasMany(PartHardwareMapping::class);
    }

    public function weldLengthStandard(): HasOne
    {
        return $this->hasOne(WeldLengthStandard::class);
    }

    public function inspectionRecords(): HasMany
    {
        return $this->hasMany(InspectionRecord::class);
    }

    public function stationTypes(): HasMany
    {
        return $this->hasMany(PartWorkStationType::class);
    }
}
