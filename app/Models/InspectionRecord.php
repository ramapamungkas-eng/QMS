<?php

namespace App\Models;

use App\Enums\InspectionStage;
use App\Enums\Shift;
use App\Support\ShiftResolver;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class InspectionRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'part_id',
        'work_station_id',
        'stage',
        'checker_id',
        'checked_at',
        'shift',
        'production_date',
    ];

    protected function casts(): array
    {
        return [
            'stage' => InspectionStage::class,
            'shift' => Shift::class,
            'checked_at' => 'datetime',
            'production_date' => 'date',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (InspectionRecord $record): void {
            $record->checker_id ??= auth()->id();
            $record->checked_at ??= now();

            [$shift, $productionDate] = ShiftResolver::resolve($record->checked_at);
            $record->shift = $shift;
            $record->production_date = $productionDate;
        });
    }

    public function part(): BelongsTo
    {
        return $this->belongsTo(Part::class);
    }

    public function workStation(): BelongsTo
    {
        return $this->belongsTo(WorkStation::class);
    }

    public function checker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checker_id');
    }

    public function stampingDetail(): HasOne
    {
        return $this->hasOne(StampingInspectionDetail::class);
    }

    public function stationSpotDetails(): HasMany
    {
        return $this->hasMany(StationSpotInspectionDetail::class);
    }

    public function portableSpotDetail(): HasOne
    {
        return $this->hasOne(PortableSpotInspectionDetail::class);
    }

    public function robotSpotDetail(): HasOne
    {
        return $this->hasOne(RobotSpotInspectionDetail::class);
    }
}
