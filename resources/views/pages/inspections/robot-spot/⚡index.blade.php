<?php

use App\Enums\JudgementResult;
use App\Enums\Shift;
use App\Enums\WorkStationType;
use App\Models\InspectionRecord;
use App\Models\Part;
use App\Models\WorkStation;
use App\Support\ShiftResolver;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;

new
#[Layout('layouts.app')]
#[Title('Robot Spot Inspections')]
class extends Component
{
    use Toast, WithPagination;

    #[Url(history: true)]
    public string $search = '';

    #[Url(history: true)]
    public string $productionDate = '';

    #[Url(history: true)]
    public ?string $workStationId = null;

    public bool $drawer = false;

    public bool $historyModal = false;

    public ?Part $selectedPart = null;

    public string $selectedStage = '';

    public string $selectedShift = '';

    public array $stageHistory = [];

    public function mount(): void
    {
        [, $this->productionDate] = ShiftResolver::resolve(now());
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedProductionDate(): void
    {
        $this->resetPage();
    }

    public function updatedWorkStationId(): void
    {
        $this->resetPage();
    }

    public function previousDay(): void
    {
        $this->productionDate = Carbon::parse($this->productionDate)->subDay()->toDateString();
        $this->resetPage();
    }

    public function nextDay(): void
    {
        $this->productionDate = Carbon::parse($this->productionDate)->addDay()->toDateString();
        $this->resetPage();
    }

    public function goToToday(): void
    {
        [, $this->productionDate] = ShiftResolver::resolve(now());
        $this->resetPage();
    }

    public function isToday(): bool
    {
        [, $today] = ShiftResolver::resolve(now());

        return $this->productionDate === $today;
    }

    public function headers(): array
    {
        return [
            ['key' => 'part_number', 'label' => 'Part Number', 'class' => 'font-semibold w-32 max-sm:w-24'],
            ['key' => 'part_name', 'label' => 'Part Name', 'class' => 'w-48 max-sm:w-32'],
            ['key' => 'day_shift', 'label' => 'Day Shift', 'class' => 'text-center'],
            ['key' => 'night_shift', 'label' => 'Night Shift', 'class' => 'text-center'],
        ];
    }

    public function workStationOptions(): array
    {
        return WorkStation::where('type', WorkStationType::RobotSpot)
            ->orderBy('name')
            ->get()
            ->map(fn (WorkStation $station) => ['id' => $station->id, 'name' => $station->name])
            ->all();
    }

    public function clearFilters(): void
    {
        $this->reset('search', 'workStationId');
        [, $this->productionDate] = ShiftResolver::resolve(now());
        $this->resetPage();
        $this->success('Filters reset.', position: 'toast-bottom');
    }

    public function activeFilterCount(): int
    {
        [, $today] = ShiftResolver::resolve(now());

        return collect([
            $this->search !== '' ? $this->search : null,
            $this->workStationId,
            $this->productionDate !== $today ? $this->productionDate : null,
        ])->filter()->count();
    }

    public function dailyStats(): array
    {
        $targetDate = Carbon::parse($this->productionDate);

        $base = InspectionRecord::query()
            ->whereDate('production_date', $targetDate)
            ->whereHas('workStation', fn (Builder $q) => $q->where('type', WorkStationType::RobotSpot))
            ->when($this->search, function (Builder $q) {
                $q->whereHas('part', function (Builder $pq) {
                    $pq->where('part_number', 'like', "%{$this->search}%")
                        ->orWhere('part_name', 'like', "%{$this->search}%");
                });
            })
            ->when($this->workStationId, fn (Builder $q) => $q->where('work_station_id', $this->workStationId));

        $total = (clone $base)->count();
        $ok = (clone $base)->whereHas('robotSpotDetail', fn (Builder $q) => $q->where('auto_judgement', JudgementResult::Ok))->count();
        $ng = (clone $base)->whereHas('robotSpotDetail', fn (Builder $q) => $q->where('auto_judgement', JudgementResult::Ng))->count();
        $partsChecked = (clone $base)->distinct('part_id')->count('part_id');
        $passRate = $total > 0 ? (int) round(($ok / $total) * 100) : 0;

        $dayBase = (clone $base)->where('shift', Shift::Day);
        $nightBase = (clone $base)->where('shift', Shift::Night);

        $dayTotal = (clone $dayBase)->count();
        $nightTotal = (clone $nightBase)->count();
        $dayOk = (clone $dayBase)->whereHas('robotSpotDetail', fn (Builder $q) => $q->where('auto_judgement', JudgementResult::Ok))->count();
        $nightOk = (clone $nightBase)->whereHas('robotSpotDetail', fn (Builder $q) => $q->where('auto_judgement', JudgementResult::Ok))->count();
        $dayNg = (clone $dayBase)->whereHas('robotSpotDetail', fn (Builder $q) => $q->where('auto_judgement', JudgementResult::Ng))->count();
        $nightNg = (clone $nightBase)->whereHas('robotSpotDetail', fn (Builder $q) => $q->where('auto_judgement', JudgementResult::Ng))->count();
        $dayRate = $dayTotal > 0 ? (int) round(($dayOk / $dayTotal) * 100) : 0;
        $nightRate = $nightTotal > 0 ? (int) round(($nightOk / $nightTotal) * 100) : 0;

        return [
            'total' => $total,
            'ok' => $ok,
            'ng' => $ng,
            'pass_rate' => $passRate,
            'parts_checked' => $partsChecked,
            'day_total' => $dayTotal,
            'night_total' => $nightTotal,
            'day_ok' => $dayOk,
            'night_ok' => $nightOk,
            'day_ng' => $dayNg,
            'night_ng' => $nightNg,
            'day_rate' => $dayRate,
            'night_rate' => $nightRate,
        ];
    }

    public function showStageHistory(int $partId, string $stage, string $shift): void
    {
        $targetDate = Carbon::parse($this->productionDate);

        $this->selectedPart = Part::findOrFail($partId);
        $this->selectedStage = $stage;
        $this->selectedShift = $shift;

        $records = InspectionRecord::query()
            ->with(['workStation', 'checker', 'robotSpotDetail'])
            ->where('part_id', $partId)
            ->where('stage', $stage)
            ->where('shift', $shift)
            ->whereDate('production_date', $targetDate)
            ->whereHas('workStation', fn (Builder $q) => $q->where('type', WorkStationType::RobotSpot))
            ->when($this->workStationId, fn (Builder $q) => $q->where('work_station_id', $this->workStationId))
            ->orderBy('checked_at')
            ->get();

        if ($records->isEmpty()) {
            $this->stageHistory = [];
            $this->warning("No inspection history for {$shift} shift stage {$stage} on ".$targetDate->format('d M Y'), position: 'toast-bottom');

            return;
        }

        $this->stageHistory = $records->map(function ($record, $index) {
            $detail = $record->robotSpotDetail;

            return [
                'attempt' => $index + 1,
                'checked_at' => $record->checked_at?->format('H:i:s') ?? '—',
                'checker_name' => $record->checker?->name ?? '—',
                'work_station' => $record->workStation?->name ?? '—',
                'shift' => $record->shift?->value ?? '—',
                'weld_length' => $detail?->weld_length,
                'auto_judgement' => $detail?->auto_judgement,
                'jig_ok' => $detail?->jig_ok,
                'jig_remarks' => $detail?->jig_remarks,
            ];
        })->all();

        $this->historyModal = true;
    }

    public function partsList(): LengthAwarePaginator
    {
        $targetDate = Carbon::parse($this->productionDate);

        return Part::query()
            ->whereHas('stationTypes', fn (Builder $q) => $q->where('work_station_type', WorkStationType::RobotSpot->value))
            ->with(['inspectionRecords' => function ($query) use ($targetDate) {
                $query->whereDate('production_date', $targetDate)
                    ->whereHas('workStation', fn (Builder $q) => $q->where('type', WorkStationType::RobotSpot))
                    ->with(['workStation', 'robotSpotDetail'])
                    ->orderBy('stage');
            }])
            ->when($this->search, function (Builder $q) {
                $q->where('part_number', 'like', "%{$this->search}%")
                    ->orWhere('part_name', 'like', "%{$this->search}%");
            })
            ->orderBy('part_number')
            ->paginate(15);
    }

    public function with(): array
    {
        return [
            'partsList' => $this->partsList(),
            'headers' => $this->headers(),
            'workStationOptions' => $this->workStationOptions(),
            'stats' => $this->dailyStats(),
            'stages' => [
                'start' => ['label' => 'S', 'name' => 'Start'],
                'middle' => ['label' => 'M', 'name' => 'Middle'],
                'end' => ['label' => 'E', 'name' => 'End'],
            ],
        ];
    }
}; ?>

<div>
    <x-header title="Robot Spot Inspection Board" subtitle="Daily quality overview for Robot Spot welding lines." separator progress-indicator>
        <x-slot:middle class="!justify-end max-sm:w-full max-sm:mt-2">
            <x-input placeholder="Search part number or name..." wire:model.live.debounce.350ms="search" clearable icon="o-magnifying-glass" class="max-sm:w-full" />
        </x-slot:middle>
        <x-slot:actions class="max-sm:flex-wrap max-sm:gap-2">
            <div class="join">
                <x-button icon="o-chevron-left" wire:click="previousDay" class="join-item btn-sm max-sm:btn-xs" tooltip="Previous day" />
                <button
                    type="button"
                    wire:click="{{ $this->isToday() ? '' : 'goToToday' }}"
                    class="join-item btn btn-sm max-sm:btn-xs px-3 font-semibold {{ $this->isToday() ? 'btn-primary pointer-events-none' : '' }}"
                >
                    <span class="sm:hidden">{{ Carbon::parse($productionDate)->format('d M') }}</span>
                    <span class="hidden sm:inline">{{ $this->isToday() ? 'Today' : Carbon::parse($productionDate)->format('D, d M Y') }}</span>
                </button>
                <x-button icon="o-chevron-right" wire:click="nextDay" class="join-item btn-sm max-sm:btn-xs" tooltip="Next day" />
            </div>

            <div class="relative inline-block">
                <x-button label="Filters" @click="$wire.drawer = true" class="max-sm:btn-xs" responsive icon="o-funnel" />
                @if($this->activeFilterCount() > 0)
                    <span class="absolute -top-1.5 -right-1.5 flex h-4 w-4 items-center justify-center rounded-full bg-primary text-[10px] text-primary-content font-bold shadow pointer-events-none">
                        {{ $this->activeFilterCount() }}
                    </span>
                @endif
            </div>

            <x-button label="New inspection" link="#" icon="o-plus" class="btn-primary max-sm:btn-xs" responsive />
        </x-slot:actions>
    </x-header>

    <div class="grid grid-cols-2 max-sm:grid-cols-1 lg:grid-cols-4 gap-3 md:gap-4 mb-6">
        <div class="rounded-2xl border border-base-300 bg-base-100 p-3 md:p-4">
            <div class="flex items-center gap-2 md:gap-3">
                <div class="grid h-9 w-9 md:h-11 md:w-11 shrink-0 place-items-center rounded-xl bg-base-content/5 text-base-content">
                    <x-icon name="o-clipboard-document-check" class="w-4 h-4 md:w-5 md:h-5" />
                </div>
                <div class="min-w-0 flex-1">
                    <div class="text-[10px] md:text-xs text-base-content/50 uppercase font-bold tracking-wider">Total Inspections</div>
                    <div class="text-xl md:text-2xl font-extrabold mt-0.5">{{ $stats['total'] }}</div>
                    <div class="flex flex-wrap items-center gap-x-2.5 gap-y-0.5 mt-1 text-[9px] md:text-[10px] text-base-content/40">
                        <span class="flex items-center gap-1">
                            <span class="w-1.5 h-1.5 rounded-full bg-amber-400 shrink-0"></span> Day: {{ $stats['day_total'] }}
                        </span>
                        <span class="flex items-center gap-1">
                            <span class="w-1.5 h-1.5 rounded-full bg-indigo-400 shrink-0"></span> Night: {{ $stats['night_total'] }}
                        </span>
                        <span class="max-sm:hidden">{{ $stats['parts_checked'] }} parts</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="rounded-2xl border border-base-300 bg-base-100 p-3 md:p-4">
            <div class="flex items-center gap-2 md:gap-3">
                <div class="grid h-9 w-9 md:h-11 md:w-11 shrink-0 place-items-center rounded-xl bg-success/10 text-success">
                    <x-icon name="o-check-circle" class="w-4 h-4 md:w-5 md:h-5" />
                </div>
                <div class="min-w-0 flex-1">
                    <div class="text-[10px] md:text-xs text-base-content/50 uppercase font-bold tracking-wider">OK</div>
                    <div class="text-xl md:text-2xl font-extrabold mt-0.5 text-success">{{ $stats['ok'] }}</div>
                    <div class="flex flex-wrap items-center gap-x-2.5 gap-y-0.5 mt-1 text-[9px] md:text-[10px] text-base-content/40">
                        <span class="flex items-center gap-1">
                            <span class="w-1.5 h-1.5 rounded-full bg-amber-400 shrink-0"></span> Day: {{ $stats['day_ok'] }}
                        </span>
                        <span class="flex items-center gap-1">
                            <span class="w-1.5 h-1.5 rounded-full bg-indigo-400 shrink-0"></span> Night: {{ $stats['night_ok'] }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div class="rounded-2xl border p-3 md:p-4 {{ $stats['ng'] > 0 ? 'border-error/40 bg-error/5' : 'border-base-300 bg-base-100' }}">
            <div class="flex items-center gap-2 md:gap-3">
                <div class="grid h-9 w-9 md:h-11 md:w-11 shrink-0 place-items-center rounded-xl bg-error/10 text-error">
                    <x-icon name="o-x-circle" class="w-4 h-4 md:w-5 md:h-5" />
                </div>
                <div class="min-w-0 flex-1">
                    <div class="text-[10px] md:text-xs text-base-content/50 uppercase font-bold tracking-wider">NG</div>
                    <div class="text-xl md:text-2xl font-extrabold mt-0.5 text-error">{{ $stats['ng'] }}</div>
                    <div class="flex flex-wrap items-center gap-x-2.5 gap-y-0.5 mt-1 text-[9px] md:text-[10px]">
                        <span class="flex items-center gap-1 {{ $stats['day_ng'] > 0 ? 'text-error/70 font-medium' : 'text-base-content/40' }}">
                            <span class="w-1.5 h-1.5 rounded-full bg-amber-400 shrink-0"></span> Day: {{ $stats['day_ng'] }}
                        </span>
                        <span class="flex items-center gap-1 {{ $stats['night_ng'] > 0 ? 'text-error/70 font-medium' : 'text-base-content/40' }}">
                            <span class="w-1.5 h-1.5 rounded-full bg-indigo-400 shrink-0"></span> Night: {{ $stats['night_ng'] }}
                        </span>
                    </div>
                    <div class="text-[10px] md:text-[11px] {{ $stats['ng'] > 0 ? 'text-error/70 font-medium' : 'text-base-content/40' }}">
                        {{ $stats['ng'] > 0 ? 'Needs countermeasure' : 'No rejects' }}
                    </div>
                </div>
            </div>
        </div>

        <div class="rounded-2xl border border-base-300 bg-base-100 p-3 md:p-4">
            <div class="flex items-center gap-2 md:gap-3">
                <div class="radial-progress {{ $stats['pass_rate'] >= 80 ? 'text-success' : ($stats['pass_rate'] >= 50 ? 'text-warning' : 'text-error') }} shrink-0"
                     style="--value:{{ $stats['pass_rate'] }}; --size:2.75rem; --thickness: 4px;"
                     role="progressbar"
                     aria-valuenow="{{ $stats['pass_rate'] }}">
                    <span class="text-[10px] md:text-[11px] font-bold text-base-content">{{ $stats['pass_rate'] }}%</span>
                </div>
                <div class="min-w-0 flex-1">
                    <div class="text-[10px] md:text-xs text-base-content/50 uppercase font-bold tracking-wider">Pass Rate</div>
                    <div class="text-[10px] md:text-xs mt-0.5 space-y-0.5 text-base-content/40">
                        <span class="flex items-center gap-1">
                            <span class="w-1.5 h-1.5 rounded-full bg-amber-400 shrink-0"></span> Day: <span class="font-semibold text-base-content/70">{{ $stats['day_rate'] }}%</span>
                        </span>
                        <span class="flex items-center gap-1">
                            <span class="w-1.5 h-1.5 rounded-full bg-indigo-400 shrink-0"></span> Night: <span class="font-semibold text-base-content/70">{{ $stats['night_rate'] }}%</span>
                        </span>
                    </div>
                    <div class="text-xs md:text-sm font-semibold mt-1 text-base-content/70 truncate">
                        {{ Carbon::parse($productionDate)->format('d M Y') }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="overflow-x-auto">
    <x-card shadow class="border border-base-200">
        <div class="flex flex-wrap items-center justify-between gap-3 pb-4 mb-1 border-b border-base-200">
            <div>
                <h3 class="font-bold text-base-content">Parts Overview</h3>
                <p class="text-xs text-base-content/50">Click a stage badge to view inspection history for that shift.</p>
            </div>
            <div class="flex items-center gap-3 text-xs text-base-content/60">
                <span class="flex items-center gap-1.5 rounded-full bg-base-200 px-2.5 py-1"><span class="w-2 h-2 rounded-full bg-success"></span> OK</span>
                <span class="flex items-center gap-1.5 rounded-full bg-base-200 px-2.5 py-1"><span class="w-2 h-2 rounded-full bg-error"></span> NG</span>
                <span class="flex items-center gap-1.5 rounded-full bg-base-200 px-2.5 py-1"><span class="w-2 h-2 rounded-full bg-base-300"></span> Not checked</span>
            </div>
        </div>

        @if ($partsList->isEmpty())
            <div class="flex flex-col items-center gap-3 py-16 text-center">
                <div class="grid h-14 w-14 place-items-center rounded-full bg-base-200">
                    <x-icon name="o-inbox" class="h-7 w-7 text-base-content/30" />
                </div>
                <div>
                    <p class="font-semibold text-base-content/70">No parts match your criteria</p>
                    <p class="text-sm text-base-content/45">Try adjusting your search or date filter.</p>
                </div>
                @if ($search || $workStationId || !$this->isToday())
                    <x-button label="Reset filters" wire:click="clearFilters" icon="o-x-mark" class="btn-sm mt-1" />
                @endif
            </div>
        @else
            <x-table :headers="$headers" :rows="$partsList" with-pagination>
                @scope('cell_part_number', $part)
                    <div class="font-bold text-primary tracking-wide">
                        {{ $part->part_number }}
                    </div>
                @endscope

                @scope('cell_part_name', $part)
                    <div class="text-sm font-medium text-base-content/80">
                        {{ $part->part_name }}
                    </div>
                @endscope

                @scope('cell_day_shift', $part)
                    @php
                        $stageKeys = ['start' => 'S', 'middle' => 'M', 'end' => 'E'];
                    @endphp
                    <div class="flex items-center justify-center gap-1.5 md:gap-4">
                        <div class="relative flex items-center gap-1.5 md:gap-4">
                            <div class="absolute left-4 md:left-5 right-4 md:right-5 top-1/2 -translate-y-1/2 h-0.5 bg-base-300 -z-0"></div>

                            @foreach ($stageKeys as $stageKey => $label)
                                @php
                                    $stageRecords = $part->inspectionRecords->filter(fn($r) => $r->stage->value === $stageKey && $r->shift === Shift::Day);
                                    $totalInspections = $stageRecords->count();

                                    if ($totalInspections > 0) {
                                        $latestRecord = $stageRecords->sortByDesc('checked_at')->first();
                                        $judgement = $latestRecord->robotSpotDetail?->auto_judgement;

                                        if ($judgement === JudgementResult::Ok) {
                                            $badgeColor = 'btn-success text-white hover:bg-success-focus';
                                            $tooltip = "OK ({$totalInspections}x inspected)";
                                        } elseif ($judgement === JudgementResult::Ng) {
                                            $badgeColor = 'btn-error text-white hover:bg-error-focus';
                                            $tooltip = "NG ({$totalInspections}x inspected)";
                                        } else {
                                            $badgeColor = 'btn-ghost text-base-content/40 border-base-300';
                                            $tooltip = 'Incomplete data';
                                        }
                                    } else {
                                        $badgeColor = 'btn-ghost text-base-content/40 border-base-300';
                                        $tooltip = 'Not checked';
                                    }
                                @endphp

                                <div class="tooltip tooltip-top relative z-10" data-tip="{{ $tooltip }}">
                                    <button
                                        type="button"
                                        @if($totalInspections > 0)
                                            wire:click="showStageHistory({{ $part->id }}, '{{ $stageKey }}', 'day')"
                                        @endif
                                        @class([
                                            'btn btn-circle btn-xs md:btn-sm font-bold shadow-sm transition-all duration-150 relative',
                                            'hover:scale-110' => $totalInspections > 0,
                                            $badgeColor => true,
                                            'pointer-events-none opacity-40' => $totalInspections === 0,
                                        ])
                                    >
                                        @if (isset($judgement) && $judgement === JudgementResult::Ng)
                                            <span class="absolute inset-0 rounded-full bg-error animate-ping opacity-40"></span>
                                        @endif

                                        <span class="relative">{{ $label }}</span>

                                        @if($totalInspections > 0)
                                            <span class="absolute -top-1 -right-1 flex h-3.5 w-3.5 md:h-4 md:w-4 items-center justify-center rounded-full bg-neutral text-[8px] md:text-[9px] text-white font-normal shadow">
                                                {{ $totalInspections }}
                                            </span>
                                        @endif
                                    </button>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endscope

                @scope('cell_night_shift', $part)
                    @php
                        $stageKeys = ['start' => 'S', 'middle' => 'M', 'end' => 'E'];
                    @endphp
                    <div class="flex items-center justify-center gap-1.5 md:gap-4">
                        <div class="relative flex items-center gap-1.5 md:gap-4">
                            <div class="absolute left-4 md:left-5 right-4 md:right-5 top-1/2 -translate-y-1/2 h-0.5 bg-base-300 -z-0"></div>

                            @foreach ($stageKeys as $stageKey => $label)
                                @php
                                    $stageRecords = $part->inspectionRecords->filter(fn($r) => $r->stage->value === $stageKey && $r->shift === Shift::Night);
                                    $totalInspections = $stageRecords->count();

                                    if ($totalInspections > 0) {
                                        $latestRecord = $stageRecords->sortByDesc('checked_at')->first();
                                        $judgement = $latestRecord->robotSpotDetail?->auto_judgement;

                                        if ($judgement === JudgementResult::Ok) {
                                            $badgeColor = 'btn-success text-white hover:bg-success-focus';
                                            $tooltip = "OK ({$totalInspections}x inspected)";
                                        } elseif ($judgement === JudgementResult::Ng) {
                                            $badgeColor = 'btn-error text-white hover:bg-error-focus';
                                            $tooltip = "NG ({$totalInspections}x inspected)";
                                        } else {
                                            $badgeColor = 'btn-ghost text-base-content/40 border-base-300';
                                            $tooltip = 'Incomplete data';
                                        }
                                    } else {
                                        $badgeColor = 'btn-ghost text-base-content/40 border-base-300';
                                        $tooltip = 'Not checked';
                                    }
                                @endphp

                                <div class="tooltip tooltip-top relative z-10" data-tip="{{ $tooltip }}">
                                    <button
                                        type="button"
                                        @if($totalInspections > 0)
                                            wire:click="showStageHistory({{ $part->id }}, '{{ $stageKey }}', 'night')"
                                        @endif
                                        @class([
                                            'btn btn-circle btn-xs md:btn-sm font-bold shadow-sm transition-all duration-150 relative',
                                            'hover:scale-110' => $totalInspections > 0,
                                            $badgeColor => true,
                                            'pointer-events-none opacity-40' => $totalInspections === 0,
                                        ])
                                    >
                                        @if (isset($judgement) && $judgement === JudgementResult::Ng)
                                            <span class="absolute inset-0 rounded-full bg-error animate-ping opacity-40"></span>
                                        @endif

                                        <span class="relative">{{ $label }}</span>

                                        @if($totalInspections > 0)
                                            <span class="absolute -top-1 -right-1 flex h-3.5 w-3.5 md:h-4 md:w-4 items-center justify-center rounded-full bg-neutral text-[8px] md:text-[9px] text-white font-normal shadow">
                                                {{ $totalInspections }}
                                            </span>
                                        @endif
                                    </button>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endscope
            </x-table>
        @endif
    </x-card>
    </div>

    <x-drawer wire:model="drawer" title="Filters" right separator with-close-button class="lg:w-1/3">
        <x-input type="date" label="Production date" wire:model.live="productionDate" icon="o-calendar" />
        <x-select label="Work station" wire:model.live="workStationId" :options="$workStationOptions" placeholder="All stations" class="mt-4" />

        <x-slot:actions>
            <x-button label="Reset" icon="o-x-mark" wire:click="clearFilters" spinner />
            <x-button label="Done" icon="o-check" class="btn-primary" @click="$wire.drawer = false" />
        </x-slot:actions>
    </x-drawer>

    <x-modal wire:model="historyModal" class="backdrop-blur-sm" box-class="max-w-4xl">
        @if($selectedPart)
            <div class="flex flex-col sm:flex-row items-start justify-between border-b border-base-200 pb-3 md:pb-4 mb-3 md:mb-4 gap-2">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="text-[10px] md:text-xs uppercase font-extrabold tracking-widest text-primary">Inspection History</span>
                        <x-badge :value="$selectedStage" class="badge-neutral badge-xs md:badge-sm text-[10px] md:text-xs font-bold uppercase" />
                        <x-badge :value="$selectedShift . ' shift'" class="badge-ghost badge-xs md:badge-sm text-[10px] md:text-xs font-semibold" />
                    </div>
                    <h3 class="text-lg md:text-xl font-bold mt-1 text-base-content">
                        {{ $selectedPart->part_name }}
                    </h3>
                    <p class="text-xs md:text-sm text-base-content/60 mt-0.5">
                        {{ $selectedPart->part_number }}
                        @if ($selectedPart->model)
                            &middot; Model: <span class="font-semibold text-base-content">{{ $selectedPart->model }}</span>
                        @endif
                        @if ($selectedPart->variant)
                            &middot; Variant: <span class="font-semibold text-base-content">{{ $selectedPart->variant }}</span>
                        @endif
                        &middot; <span class="hidden sm:inline">Date:</span> <span class="font-semibold text-base-content">{{ Carbon::parse($productionDate)->format('d F Y') }}</span>
                    </p>
                </div>
                <div class="text-right shrink-0">
                    @php
                        $lastHistory = collect($stageHistory)->last();
                        $finalStatus = $lastHistory['auto_judgement'] ?? null;
                    @endphp
                    <span class="text-[10px] md:text-xs block text-base-content/50">Final Result</span>
                    @if ($finalStatus)
                        <x-badge
                            :value="$finalStatus->label()"
                            class="{{ $finalStatus->badgeClass() }} badge-sm md:badge-md text-white font-extrabold mt-1"
                        />
                    @endif
                </div>
            </div>

            <div class="space-y-4 md:space-y-6">
                <div class="alert alert-info py-2 px-3 md:py-2.5 md:px-3.5 shadow-none text-[10px] md:text-xs rounded-xl flex items-start gap-2 bg-info/10 text-info border-none">
                    <x-icon name="o-information-circle" class="w-4 h-4 md:w-5 md:h-5 shrink-0 mt-0.5" />
                    <span>Inspections listed chronologically. Each row represents a single inspection attempt at this stage.</span>
                </div>

                <div class="relative max-h-[50vh] md:max-h-[60vh] overflow-y-auto pr-1 md:pr-2">
                    <div class="absolute left-[13px] md:left-[15px] top-3 bottom-3 w-0.5 bg-base-300"></div>

                    <div class="space-y-3 md:space-y-4">
                        @foreach($stageHistory as $history)
                            <div class="relative pl-8 md:pl-10">
                                <span @class([
                                    'absolute left-0 top-3 md:top-4 flex h-6 w-6 md:h-8 md:w-8 items-center justify-center rounded-full text-white text-[9px] md:text-xs font-bold ring-2 md:ring-4 ring-base-100 shadow',
                                    'bg-success' => $history['auto_judgement'] === JudgementResult::Ok,
                                    'bg-error' => $history['auto_judgement'] === JudgementResult::Ng,
                                ])>
                                    #{{ $history['attempt'] }}
                                </span>

                                <div @class([
                                    'rounded-xl border p-2.5 md:p-4 shadow-sm transition-all duration-200',
                                    'border-success/30 bg-success/5' => $history['auto_judgement'] === JudgementResult::Ok,
                                    'border-error/30 bg-error/5' => $history['auto_judgement'] === JudgementResult::Ng,
                                ])>
                                    <div class="flex flex-wrap items-center justify-between gap-2 border-b border-base-200 pb-2 md:pb-3 mb-2 md:mb-3">
                                        <div class="flex items-center gap-1.5 md:gap-2">
                                            <span class="font-bold text-xs md:text-sm">Inspection #{{ $history['attempt'] }}</span>
                                            <span class="text-[10px] md:text-xs text-base-content/50">at {{ $history['checked_at'] }}</span>
                                        </div>
                                        <div class="flex items-center gap-1.5 md:gap-2 flex-wrap">
                                            <span class="text-[10px] md:text-xs text-base-content/60">
                                                <strong class="text-base-content">{{ $history['checker_name'] }}</strong>
                                                <span class="hidden sm:inline">&middot; {{ $history['work_station'] }}</span>
                                                &middot; {{ ucfirst($history['shift']) }} shift
                                            </span>
                                            <x-badge
                                                :value="$history['auto_judgement']?->label() ?? '—'"
                                                class="{{ $history['auto_judgement']?->badgeClass() ?? 'badge-ghost' }} badge-xs md:badge-sm text-white font-bold"
                                            />
                                        </div>
                                    </div>

                                    <div class="overflow-x-auto rounded-lg border border-base-200">
                                        <table class="table table-compact w-full text-[10px] md:text-xs">
                                            <thead>
                                                <tr class="bg-base-200/50 text-base-content/85">
                                                    <th class="py-1.5 md:py-2">Check Point</th>
                                                    <th class="text-center py-1.5 md:py-2">Value</th>
                                                    <th class="text-center py-1.5 md:py-2">Result</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr class="hover:bg-base-200/30">
                                                    <td class="font-semibold py-1.5 md:py-2">Weld Length</td>
                                                    <td class="text-center py-1.5 md:py-2 font-mono">
                                                        {{ $history['weld_length'] !== null ? number_format($history['weld_length'], 2).' mm' : '—' }}
                                                    </td>
                                                    <td class="text-center py-1.5 md:py-2">
                                                        <x-badge
                                                            :value="$history['auto_judgement']?->label() ?? '—'"
                                                            class="{{ $history['auto_judgement']?->badgeClass() ?? 'badge-ghost' }} badge-xs text-white font-bold"
                                                        />
                                                    </td>
                                                </tr>
                                                <tr class="hover:bg-base-200/30">
                                                    <td class="font-semibold py-1.5 md:py-2">Jig Conformance</td>
                                                    <td class="text-center py-1.5 md:py-2">
                                                        @if ($history['jig_ok'] === true)
                                                            <x-badge value="OK" class="badge-success badge-xs text-white font-bold" />
                                                        @elseif ($history['jig_ok'] === false)
                                                            <x-badge value="NG" class="badge-error badge-xs text-white font-bold" />
                                                        @else
                                                            <span class="text-base-content/40">—</span>
                                                        @endif
                                                    </td>
                                                    <td class="py-1.5 md:py-2 text-base-content/60">
                                                        {{ $history['jig_remarks'] ?: '—' }}
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="modal-action border-t border-base-200 pt-2 md:pt-3 mt-3 md:mt-4">
                <x-button label="Close" @click="$wire.historyModal = false" class="btn-primary btn-sm max-sm:btn-xs" />
            </div>
        @endif
    </x-modal>
</div>