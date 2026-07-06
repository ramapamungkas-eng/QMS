<?php

namespace App\Models;

use Database\Factories\WorkStationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkStation extends Model
{
    /** @use HasFactory<WorkStationFactory> */
    use HasFactory;

    protected $fillable = ['process_id', 'name', 'station_type_id'];

    /** @return BelongsTo<Process, $this> */
    public function process(): BelongsTo
    {
        return $this->belongsTo(Process::class);
    }

    /** @return BelongsTo<StationType, $this> */
    public function stationType(): BelongsTo
    {
        return $this->belongsTo(StationType::class);
    }
}
