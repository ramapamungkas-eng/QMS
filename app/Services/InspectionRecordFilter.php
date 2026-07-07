<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\InspectionRecord;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

class InspectionRecordFilter
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function __construct(
        protected array $filters,
        protected ?User $user = null,
    ) {}

    /** @return Builder<InspectionRecord> */
    public function query(): Builder
    {
        $query = InspectionRecord::query()
            ->with([
                'part',
                'workStation.stationType',
                'checker',
                'fieldValues.field',
            ]);

        if (! empty($this->filters['date_from'])) {
            $query->whereDate('production_date', '>=', Carbon::parse($this->filters['date_from']));
        }

        if (! empty($this->filters['date_to'])) {
            $query->whereDate('production_date', '<=', Carbon::parse($this->filters['date_to']));
        }

        if (! empty($this->filters['station_type_id'])) {
            $query->whereHas('workStation', fn (Builder $q) => $q->where('station_type_id', $this->filters['station_type_id']));
        }

        if (! empty($this->filters['work_station_id'])) {
            $query->where('work_station_id', $this->filters['work_station_id']);
        }

        if (! empty($this->filters['stage'])) {
            $query->where('stage', $this->filters['stage']);
        }

        if (! empty($this->filters['shift'])) {
            $query->where('shift', $this->filters['shift']);
        }

        if (! empty($this->filters['judgement'])) {
            $judgement = $this->filters['judgement'];
            $query->where(function (Builder $q) use ($judgement) {
                $q->whereHas('fieldValues', fn (Builder $fv) => $fv->where('auto_judgement', $judgement))
                    ->orWhereHas('fieldValues', function (Builder $fv) use ($judgement) {
                        $fv->whereHas('field', fn (Builder $f) => $f->where('field_type', 'enum'))
                            ->where('value', $judgement);
                    });
            });
        }

        if (! empty($this->filters['search'])) {
            $search = $this->filters['search'];
            $query->whereHas('part', fn (Builder $q) => $q
                ->where('part_number', 'like', "%{$search}%")
                ->orWhere('part_name', 'like', "%{$search}%"));
        }

        if ($this->user !== null && $this->user->role === UserRole::Checker) {
            $query->whereHas('workStation', fn (Builder $q) => $q->where('process_id', $this->user->process_id));
        }

        return $query;
    }

    /** @return Builder<InspectionRecord> */
    public function queryOrdered(): Builder
    {
        return $this->query()
            ->orderBy('production_date', 'desc')
            ->orderBy('checked_at', 'desc');
    }
}
