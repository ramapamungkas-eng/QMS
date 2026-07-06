<?php

namespace App\Models;

use Database\Factories\WeldLengthStandardFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WeldLengthStandard extends Model
{
    /** @use HasFactory<WeldLengthStandardFactory> */
    use HasFactory;

    protected $fillable = ['part_id', 'work_station_id', 'min_length', 'max_length', 'unit'];

    protected function casts(): array
    {
        return [
            'min_length' => 'decimal:2',
            'max_length' => 'decimal:2',
        ];
    }

    /** @return BelongsTo<Part, $this> */
    public function part(): BelongsTo
    {
        return $this->belongsTo(Part::class);
    }

    /** @return BelongsTo<WorkStation, $this> */
    public function workStation(): BelongsTo
    {
        return $this->belongsTo(WorkStation::class);
    }
}
