<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property-read Process|null $process
 */
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

    /** @return BelongsTo<Process, $this> */
    public function process(): BelongsTo
    {
        return $this->belongsTo(Process::class);
    }

    /** @return HasMany<WorkStation, $this> */
    public function workStations(): HasMany
    {
        return $this->hasMany(WorkStation::class, 'station_type_id');
    }

    /** @return HasMany<ChecklistTemplate, $this> */
    public function checklistTemplates(): HasMany
    {
        return $this->hasMany(ChecklistTemplate::class, 'station_type_id');
    }
}
