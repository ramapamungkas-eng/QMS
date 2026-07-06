<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StationType extends Model
{
    protected $table = 'work_station_types';

    protected $fillable = [
        'process_id',
        'slug',
        'name',
        'description',
        'icon',
    ];

    public function process(): BelongsTo
    {
        return $this->belongsTo(Process::class);
    }

    public function workStations(): HasMany
    {
        return $this->hasMany(WorkStation::class, 'station_type_id');
    }

    public function checklistTemplates(): HasMany
    {
        return $this->hasMany(ChecklistTemplate::class, 'station_type_id');
    }
}
