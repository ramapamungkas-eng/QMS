<?php

namespace App\Models;

use Database\Factories\ProcessFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string $name
 */
class Process extends Model
{
    /** @use HasFactory<ProcessFactory> */
    use HasFactory;

    protected $fillable = ['name'];

    /** @return HasMany<WorkStation, $this> */
    public function workStations(): HasMany
    {
        return $this->hasMany(WorkStation::class);
    }

    /** @return BelongsToMany<Part, $this> */
    public function parts(): BelongsToMany
    {
        return $this->belongsToMany(Part::class);
    }
}
