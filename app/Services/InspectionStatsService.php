<?php

namespace App\Services;

use App\Enums\Shift;
use App\Models\InspectionFieldValue;
use App\Models\InspectionRecord;
use App\Models\StationType;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class InspectionStatsService
{
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

        $dayBase = (clone $base)->where('shift', Shift::Day);
        $nightBase = (clone $base)->where('shift', Shift::Night);

        $dayRecords = (clone $dayBase)->with('fieldValues.field')->get();
        $nightRecords = (clone $nightBase)->with('fieldValues.field')->get();

        $dayTotal = (clone $dayBase)->count();
        $nightTotal = (clone $nightBase)->count();

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

    protected function countJudgement(Collection $records, string $judgement): int
    {
        return $records->sum(function (InspectionRecord $record) use ($judgement) {
            $fieldValues = $record->fieldValues;

            $autoJudgements = $fieldValues
                ->filter(fn (InspectionFieldValue $fv) => $fv->field?->has_auto_judge)
                ->pluck('auto_judgement')
                ->filter();

            if ($autoJudgements->isNotEmpty()) {
                return $autoJudgements->contains($judgement) ? 1 : 0;
            }

            $manualValues = $fieldValues
                ->where('field.field_type', 'enum')
                ->pluck('value')
                ->filter();

            if ($manualValues->isNotEmpty()) {
                $lowered = $manualValues->map(fn ($v) => strtolower($v));

                return $lowered->contains($judgement) ? 1 : 0;
            }

            $booleans = $fieldValues
                ->where('field.field_type', 'boolean')
                ->pluck('value')
                ->filter(fn ($v) => $v !== null && $v !== '');

            if ($booleans->isNotEmpty()) {
                if ($judgement === 'ok') {
                    return $booleans->every(fn ($v) => $v === '1') ? 1 : 0;
                }

                if ($judgement === 'ng') {
                    return $booleans->contains('0') ? 1 : 0;
                }
            }

            return 0;
        });
    }
}
