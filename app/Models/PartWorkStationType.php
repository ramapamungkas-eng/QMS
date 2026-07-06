<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PartWorkStationType extends Model
{
    protected $table = 'part_work_station_types';

    protected $fillable = [
        'part_id',
        'station_type_id',
    ];

    public function part(): BelongsTo
    {
        return $this->belongsTo(Part::class);
    }

    public function stationType(): BelongsTo
    {
        return $this->belongsTo(StationType::class);
    }
}
