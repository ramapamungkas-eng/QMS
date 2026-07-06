<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkStation extends Model
{
    use HasFactory;

    protected $fillable = ['process_id', 'name', 'station_type_id'];

    public function process(): BelongsTo
    {
        return $this->belongsTo(Process::class);
    }

    public function stationType(): BelongsTo
    {
        return $this->belongsTo(StationType::class);
    }
}
