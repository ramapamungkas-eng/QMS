<?php

namespace App\Models;

use Database\Factories\StationTypeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property-read Process|null $process
 */
class StationType extends Model
{
    /** @use HasFactory<StationTypeFactory> */
    use HasFactory;

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

    /**
     * @return array<string, string>
     */
    public static function routeSlugs(): array
    {
        return [
            'stamping' => 'Stamping',
            'station-spot' => 'Welding',
            'portable-spot' => 'Welding',
            'robot-spot' => 'Welding',
        ];
    }
}
