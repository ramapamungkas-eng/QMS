<?php

namespace App\Models;

use App\Enums\InspectionStage;
use App\Enums\Shift;
use App\Support\ShiftResolver;
use Carbon\CarbonImmutable;
use Database\Factories\InspectionRecordFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property Shift $shift
 * @property int $checker_id
 * @property InspectionStage $stage
 * @property Carbon|string|null $production_date
 * @property Carbon|CarbonImmutable|null $checked_at
 */
class InspectionRecord extends Model
{
    /** @use HasFactory<InspectionRecordFactory> */
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
            $record->checker_id = (int) ($record->checker_id ?? auth()->id());
            $record->checked_at ??= now();

            [$shift, $productionDate] = ShiftResolver::resolve(CarbonImmutable::parse($record->checked_at));
            $record->shift = $shift;
            $record->production_date = $productionDate;
        });
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

    /** @return BelongsTo<User, $this> */
    public function checker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checker_id');
    }

    /** @return HasMany<InspectionFieldValue, $this> */
    public function fieldValues(): HasMany
    {
        return $this->hasMany(InspectionFieldValue::class);
    }
}
