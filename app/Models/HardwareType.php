<?php

namespace App\Models;

use Database\Factories\HardwareTypeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HardwareType extends Model
{
    /** @use HasFactory<HardwareTypeFactory> */
    use HasFactory;

    protected $fillable = ['part_number', 'part_name', 'image'];

    public function imageUrl(): string
    {
        return $this->image
            ? asset('storage/'.$this->image)
            : 'https://ui-avatars.com/api/?size=256&background=475569&color=fff&name='.urlencode($this->part_name);
    }

    /** @return HasMany<PartHardwareMapping, $this> */
    public function partMappings(): HasMany
    {
        return $this->hasMany(PartHardwareMapping::class);
    }
}
