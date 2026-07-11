<?php

namespace App\Services;

use App\Enums\Shift;
use App\Models\InspectionRecord;
use App\Models\StationType;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class InspectionStatsService
{
    /**
     * @param  array<string, mixed>  $options
     * @return array<string, mixed>
     */
    public function dailyByType(StationType $stationType, string $productionDate, array $options = []): array
    {
        $targetDate = Carbon::parse($productionDate);

        $base = InspectionRecord::query()
            ->whereDate('production_date', $targetDate)
            ->whereHas('workStation', fn (Builder $q) => $q->where('station_type_id', $stationType->id));

        if (! empty($options['search'])) {
            $base->whereHas('part', function (Builder $q) use ($options) {
                $q->where('part_number', 'like', "%{$options['search']}%")
                    ->orWhere('part_name', 'like', "%{$options['search']}%");
            });
        }

        if (! empty($options['workStationId'])) {
            $base->where('work_station_id', $options['workStationId']);
        }

        $total = (clone $base)->count();
        $partsChecked = (clone $base)->distinct('part_id')->count('part_id');

        $records = (clone $base)->with('fieldValues.field')->get();
        $ok = $this->countJudgement($records, 'ok');
        $ng = $this->countJudgement($records, 'ng');
        $passRate = $total > 0 ? (int) round(($ok / $total) * 100) : 0;

        $dayRecords = $records->filter(fn ($r) => $r->shift === Shift::Day);
        $nightRecords = $records->filter(fn ($r) => $r->shift === Shift::Night);
        $dayTotal = $dayRecords->count();
        $nightTotal = $nightRecords->count();

        return [
            'total' => $total,
            'ok' => $ok,
            'ng' => $ng,
            'pass_rate' => $passRate,
            'parts_checked' => $partsChecked,
            'day_total' => $dayTotal,
            'night_total' => $nightTotal,
            'day_ok' => $this->countJudgement($dayRecords, 'ok'),
            'night_ok' => $this->countJudgement($nightRecords, 'ok'),
            'day_ng' => $this->countJudgement($dayRecords, 'ng'),
            'night_ng' => $this->countJudgement($nightRecords, 'ng'),
            'day_rate' => $dayTotal > 0 ? (int) round(($this->countJudgement($dayRecords, 'ok') / $dayTotal) * 100) : 0,
            'night_rate' => $nightTotal > 0 ? (int) round(($this->countJudgement($nightRecords, 'ok') / $nightTotal) * 100) : 0,
        ];
    }

    /**
     * @param  array<int, StationType>  $stationTypes
     * @return array<int, array<string, mixed>>
     */
    public function allByTypes(string $productionDate, array $stationTypes): array
    {
        $targetDate = Carbon::parse($productionDate);
        $typeIds = array_map(fn (StationType $st) => $st->id, $stationTypes);

        $allRecords = InspectionRecord::query()
            ->whereDate('production_date', $targetDate)
            ->whereHas('workStation', fn (Builder $q) => $q->whereIn('station_type_id', $typeIds))
            ->with('fieldValues.field', 'workStation')
            ->get()
            ->groupBy(fn ($r) => $r->workStation->station_type_id);

        return array_map(function (StationType $type) use ($allRecords) {
            $typeRecords = $allRecords->get($type->id, collect());
            $total = $typeRecords->count();
            $ok = $this->countJudgement($typeRecords, 'ok');
            $ng = $this->countJudgement($typeRecords, 'ng');

            $dayRecords = $typeRecords->filter(fn ($r) => $r->shift === Shift::Day);
            $nightRecords = $typeRecords->filter(fn ($r) => $r->shift === Shift::Night);
            $dayTotal = $dayRecords->count();
            $nightTotal = $nightRecords->count();

            return [
                'id' => $type->id,
                'type' => $type,
                'total' => $total,
                'ok' => $ok,
                'ng' => $ng,
                'pass_rate' => $total > 0 ? (int) round(($ok / $total) * 100) : 0,
                'parts_checked' => $typeRecords->pluck('part_id')->unique()->count(),
                'day_total' => $dayTotal,
                'night_total' => $nightTotal,
                'day_ok' => $this->countJudgement($dayRecords, 'ok'),
                'night_ok' => $this->countJudgement($nightRecords, 'ok'),
                'day_ng' => $this->countJudgement($dayRecords, 'ng'),
                'night_ng' => $this->countJudgement($nightRecords, 'ng'),
                'day_rate' => $dayTotal > 0 ? (int) round(($this->countJudgement($dayRecords, 'ok') / $dayTotal) * 100) : 0,
                'night_rate' => $nightTotal > 0 ? (int) round(($this->countJudgement($nightRecords, 'ok') / $nightTotal) * 100) : 0,
            ];
        }, $stationTypes);
    }

    /** @return array<string, mixed> */
    /**
     * @param  array<int, StationType>  $stationTypes
     * @return array<string, mixed>
     */
    public function overallSummary(string $productionDate, array $stationTypes): array
    {
        $targetDate = Carbon::parse($productionDate);

        $typeIds = array_map(fn (StationType $st) => $st->id, $stationTypes);

        $base = InspectionRecord::query()
            ->whereDate('production_date', $targetDate)
            ->whereHas('workStation', fn (Builder $q) => $q->whereIn('station_type_id', $typeIds));

        $total = (clone $base)->count();
        $partsChecked = (clone $base)->distinct('part_id')->count('part_id');

        $records = (clone $base)->with('fieldValues.field')->get();
        $ok = $this->countJudgement($records, 'ok');
        $ng = $this->countJudgement($records, 'ng');

        return [
            'total' => $total,
            'ok' => $ok,
            'ng' => $ng,
            'parts_checked' => $partsChecked,
            'pass_rate' => $total > 0 ? (int) round(($ok / $total) * 100) : 0,
        ];
    }

    /** @return Collection<int, InspectionRecord> */
    /**
     * @param  array<int, StationType>  $stationTypes
     * @return Collection<int, InspectionRecord>
     */
    public function recentNgRecords(string $productionDate, array $stationTypes, int $limit = 10): Collection
    {
        $typeIds = array_map(fn (StationType $st) => $st->id, $stationTypes);

        return InspectionRecord::query()
            ->with(['part', 'workStation', 'checker', 'fieldValues.field'])
            ->whereDate('production_date', Carbon::parse($productionDate))
            ->whereHas('workStation', fn (Builder $q) => $q->whereIn('station_type_id', $typeIds))
            ->whereHas('fieldValues', fn (Builder $q) => $q->where('auto_judgement', 'ng'))
            ->latest('checked_at')
            ->limit($limit)
            ->get();
    }

    /** @param Collection<int, InspectionRecord> $records */
    protected function countJudgement(Collection $records, string $judgement): int
    {
        $service = app(InspectionJudgementService::class);

        return $records->sum(function (InspectionRecord $record) use ($service, $judgement): int {
            return $service->stageOverall($record->fieldValues) === $judgement ? 1 : 0;
        });
    }
}
