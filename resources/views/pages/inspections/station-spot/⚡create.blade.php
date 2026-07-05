<?php

use App\Enums\InspectionStage;
use App\Enums\JudgementResult;
use App\Enums\WorkStationType;
use App\Models\InspectionRecord;
use App\Models\Part;
use App\Models\WorkStation;
use App\Support\ShiftResolver;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Mary\Traits\Toast;

new
#[Layout('layouts.app')]
#[Title('New Station Spot Inspection')]
class extends Component {
    use Toast;

    public string $workStationId = '';

    public string $stage = '';

    public string $partSearch = '';

    public ?int $partId = null;

    public array $measurements = [];

    public string $remarks = '';

    public bool $recheckConfirmed = false;

    public function updatedWorkStationId(): void
    {
        $this->recheckConfirmed = false;
    }

    public function updatedStage(): void
    {
        $this->recheckConfirmed = false;
    }

    public function updatedPartId(): void
    {
        $this->recheckConfirmed = false;
        $this->measurements = [];
        $this->remarks = '';
    }

    public function confirmRecheck(): void
    {
        $this->recheckConfirmed = true;
    }

    public function needsRecheckConfirmation(): bool
    {
        if ($this->recheckConfirmed) {
            return false;
        }

        if (! $this->workStationId || ! $this->stage || ! $this->partId) {
            return false;
        }

        [, $productionDate] = ShiftResolver::resolve(now());

        $allOk = InspectionRecord::where('part_id', $this->partId)
            ->where('work_station_id', $this->workStationId)
            ->where('stage', $this->stage)
            ->whereDate('production_date', $productionDate)
            ->whereHas('stationSpotDetails', fn ($q) => $q->where('auto_judgement', JudgementResult::Ok))
            ->exists();

        if (! $allOk) {
            return false;
        }

        return InspectionRecord::where('part_id', $this->partId)
            ->where('work_station_id', $this->workStationId)
            ->where('stage', $this->stage)
            ->whereDate('production_date', $productionDate)
            ->whereDoesntHave('stationSpotDetails', fn ($q) => $q->where('auto_judgement', JudgementResult::Ng))
            ->exists();
    }

    public function workStationOptions(): array
    {
        return WorkStation::where('type', WorkStationType::StationSpot)
            ->orderBy('name')
            ->get()
            ->map(fn (WorkStation $station) => ['id' => $station->id, 'name' => $station->name])
            ->all();
    }

    public function stageOptions(): array
    {
        return array_map(
            fn (InspectionStage $stage) => ['id' => $stage->value, 'name' => $stage->label(), 'description' => $stage->description()],
            InspectionStage::cases(),
        );
    }

    public function selectPart(int $id): void
    {
        $this->partId = $id;
        $this->partSearch = '';
        $this->resetValidation('partId');

        $mappings = Part::findOrFail($id)->hardwareMappings()->with('measurementStandard', 'hardwareType')->get();
        foreach ($mappings as $mapping) {
            $this->measurements[$mapping->id] = '';
        }
    }

    public function removePart(): void
    {
        $this->partId = null;
        $this->partSearch = '';
        $this->measurements = [];
        $this->remarks = '';
    }

    public function partMappings(): ?\Illuminate\Support\Collection
    {
        if (! $this->partId) {
            return null;
        }

        return Part::findOrFail($this->partId)
            ->hardwareMappings()
            ->with('measurementStandard', 'hardwareType')
            ->get();
    }

    public function partHistory(): ?array
    {
        if (! $this->partId) {
            return null;
        }

        $records = InspectionRecord::where('part_id', $this->partId)
            ->whereHas('workStation', fn ($q) => $q->where('type', WorkStationType::StationSpot))
            ->with('stationSpotDetails')
            ->latest('checked_at')
            ->get();

        return [
            'total' => $records->count(),
            'byStage' => $records->groupBy(fn ($r) => $r->stage->value)->map->count(),
            'latest' => $records->first(),
        ];
    }

    public function autoJudgement(?float $value, object $mapping): ?JudgementResult
    {
        if ($value === null || $value === '' || ! $mapping->measurementStandard) {
            return null;
        }

        if ($value >= $mapping->measurementStandard->min_value && $value <= $mapping->measurementStandard->max_value) {
            return JudgementResult::Ok;
        }

        return JudgementResult::Ng;
    }

    public function hasAnyNg(): bool
    {
        if (! $this->partId) {
            return false;
        }

        $mappings = $this->partMappings();

        if (! $mappings || $mappings->isEmpty()) {
            return false;
        }

        foreach ($mappings as $mapping) {
            $val = $this->measurements[$mapping->id] ?? '';
            $judgement = $this->autoJudgement($val !== '' ? (float) $val : null, $mapping);
            if ($judgement === JudgementResult::Ng) {
                return true;
            }
        }

        return false;
    }

    public function allMeasured(): bool
    {
        if (! $this->partId) {
            return false;
        }

        $mappings = $this->partMappings();

        if (! $mappings || $mappings->isEmpty()) {
            return false;
        }

        foreach ($mappings as $mapping) {
            $val = $this->measurements[$mapping->id] ?? '';
            if ($val === '' || $val === null) {
                return false;
            }
        }

        return true;
    }

    public function rules(): array
    {
        $mappings = $this->partId ? Part::findOrFail($this->partId)->hardwareMappings : collect();

        $rules = [
            'workStationId' => ['required', 'exists:work_stations,id'],
            'stage' => ['required', Rule::enum(InspectionStage::class)],
            'partId' => ['required', 'exists:parts,id'],
            'remarks' => ['nullable', 'string', 'max:1000'],
        ];

        foreach ($mappings as $mapping) {
            $rules["measurements.{$mapping->id}"] = ['required', 'numeric'];
        }

        return $rules;
    }

    public function save(): void
    {
        $data = $this->validate();

        $record = DB::transaction(function () use ($data) {
            $record = InspectionRecord::create([
                'part_id' => $this->partId,
                'work_station_id' => $this->workStationId,
                'stage' => $this->stage,
            ]);

            $mappings = $this->partMappings();
            $first = true;

            foreach ($mappings as $mapping) {
                $value = (float) $data['measurements'][$mapping->id];
                $judgement = $this->autoJudgement($value, $mapping);

                $detailData = [
                    'part_hardware_mapping_id' => $mapping->id,
                    'measurement_value' => $value,
                    'auto_judgement' => $judgement,
                ];

                if ($first) {
                    $detailData['remarks'] = $data['remarks'] ?: null;
                    $first = false;
                }

                $record->stationSpotDetails()->create($detailData);
            }

            return $record;
        });

        $this->success(
            'Station Spot inspection recorded.',
            position: 'toast-bottom',
            redirectTo: route('inspections.station-spot.index'),
        );
    }

    public function with(): array
    {
        $mappings = $this->partMappings();

        return [
            'workStationOptions' => $this->workStationOptions(),
            'stageOptions' => $this->stageOptions(),
            'partHistory' => $this->partHistory(),
            'partMappings' => $mappings,
            'partSearchResults' => strlen($this->partSearch) >= 2
                ? Part::where(function ($q) {
                    $q->where('part_number', 'like', "%{$this->partSearch}%")
                        ->orWhere('part_name', 'like', "%{$this->partSearch}%");
                })
                    ->whereHas('stationTypes', fn ($q) => $q->where('work_station_type', WorkStationType::StationSpot->value))
                    ->orderBy('part_number')
                    ->limit(8)
                    ->get()
                : collect(),
            'selectedPart' => $this->partId ? Part::where('id', $this->partId)
                ->whereHas('stationTypes', fn ($q) => $q->where('work_station_type', WorkStationType::StationSpot->value))
                ->with('hardwareMappings.measurementStandard', 'hardwareMappings.hardwareType')
                ->first() : null,
            'needsRecheck' => $this->needsRecheckConfirmation(),
        ];
    }
}; ?>

<div>
    <x-header title="New Station Spot Inspection" subtitle="Hardware measurement for Station Spot welding lines." separator>
        <x-slot:actions>
            <x-button label="Back to records" link="{{ route('inspections.station-spot.index') }}" icon="o-arrow-left" responsive />
        </x-slot:actions>
    </x-header>

    <x-form wire:submit="save">
        <div class="grid gap-6 lg:grid-cols-12">
            {{-- SIDEBAR --}}
            <div class="lg:col-span-4">
                <div class="sticky top-4 grid gap-6 max-lg:static max-lg:grid-cols-1 max-lg:gap-4">
                    <x-card title="Inspection context" subtitle="What, where, and when." shadow>
                        <div class="grid gap-4">
                            <x-select
                                label="Work station"
                                wire:model="workStationId"
                                :options="$workStationOptions"
                                placeholder="Select station..."
                            />
                            <x-select
                                label="Stage"
                                wire:model="stage"
                                :options="$stageOptions"
                                placeholder="Select stage..."
                            />
                        </div>
                    </x-card>

                    <x-card title="Part" subtitle="Search and select the part being inspected." shadow>
                        @if ($selectedPart)
                            <div class="flex items-center gap-3">
                                <div class="avatar shrink-0">
                                    <div class="h-10 w-10 rounded-full">
                                        <img src="{{ $selectedPart->imageUrl() }}" alt="{{ $selectedPart->part_name }}">
                                    </div>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="truncate font-medium">{{ $selectedPart->part_name }}</p>
                                    <p class="font-mono text-xs text-base-content/60">{{ $selectedPart->part_number }}</p>
                                    <div class="mt-0.5 flex flex-wrap gap-x-2 gap-y-0.5 text-xs text-base-content/50">
                                        <span>Model: {{ $selectedPart->model ?? '—' }}</span>
                                        <span>Variant: {{ $selectedPart->variant ?? '—' }}</span>
                                    </div>
                                </div>
                                <x-button icon="o-x-mark" wire:click="removePart" class="btn-ghost btn-sm shrink-0" />
                            </div>
                        @else
                            <x-input
                                placeholder="Search part number or name..."
                                wire:model.live.debounce="partSearch"
                                icon="o-magnifying-glass"
                                clearable
                            />
                            @if ($partSearchResults->isNotEmpty())
                                <div class="mt-2 divide-y divide-base-300 overflow-hidden rounded-xl border border-base-300">
                                    @foreach ($partSearchResults as $part)
                                        <button
                                            type="button"
                                            wire:click="selectPart({{ $part->id }})"
                                            wire:key="{{ $part->id }}"
                                            class="flex w-full items-center gap-3 px-3 py-2.5 text-left transition hover:bg-base-200"
                                        >
                                            <div class="avatar shrink-0">
                                                <div class="h-8 w-8 rounded-full">
                                                    <img src="{{ $part->imageUrl() }}" alt="{{ $part->part_name }}">
                                                </div>
                                            </div>
                                            <div class="min-w-0 flex-1">
                                                <p class="truncate text-sm font-medium">{{ $part->part_name }}</p>
                                                <p class="font-mono text-xs text-base-content/50">{{ $part->part_number }}</p>
                                            </div>
                                        </button>
                                    @endforeach
                                </div>
                            @elseif (strlen($partSearch) >= 2)
                                <p class="mt-2 text-center text-sm text-base-content/40">No parts match your search.</p>
                            @endif
                        @endif
                    </x-card>
                </div>
            </div>

            {{-- MAIN --}}
            <div class="lg:col-span-8">
                @if (! $selectedPart)
                    <x-card shadow>
                        <div class="flex flex-col items-center gap-5 py-12 text-center">
                            <x-icon name="o-wrench-screwdriver" class="h-12 w-12 text-base-content/30" />
                            <div class="max-w-sm">
                                <p class="text-base font-semibold text-base-content/60">Start your inspection</p>
                                <ol class="mt-3 space-y-2 text-left text-sm text-base-content/40">
                                    <li class="flex items-start gap-2">
                                        <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-base-300 text-xs font-medium text-base-content/50">1</span>
                                        Choose a work station in the sidebar
                                    </li>
                                    <li class="flex items-start gap-2">
                                        <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-base-300 text-xs font-medium text-base-content/50">2</span>
                                        Select the inspection stage
                                    </li>
                                    <li class="flex items-start gap-2">
                                        <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-base-300 text-xs font-medium text-base-content/50">3</span>
                                        Search and pick the part being inspected
                                    </li>
                                </ol>
                            </div>
                        </div>
                    </x-card>
                @else
                    @php
                        $step1 = $workStationId && $stage;
                        $step2 = $step1 && $selectedPart;
                    @endphp
                    <div class="flex items-center gap-1 rounded-xl border border-base-300 bg-base-100/50 px-3 py-2 text-xs font-medium max-sm:gap-0">
                        <div class="flex items-center gap-1.5">
                            <span @class(['flex h-5 w-5 items-center justify-center rounded-full text-[10px] font-bold',
                                'bg-success text-success-content' => $step1,
                                'bg-base-300 text-base-content/50' => ! $step1,
                            ])>{{ $step1 ? '✓' : '1' }}</span>
                            <span @class(['transition-colors', 'text-base-content/60' => ! $step1, 'text-base-content' => $step1])>Station &amp; Stage</span>
                        </div>
                        <x-icon name="o-chevron-right" class="mx-1 h-3 w-3 text-base-content/30 max-sm:mx-0" />
                        <div class="flex items-center gap-1.5">
                            <span @class(['flex h-5 w-5 items-center justify-center rounded-full text-[10px] font-bold',
                                'bg-success text-success-content' => $step2,
                                'bg-base-300 text-base-content/50' => ! $step2,
                            ])>{{ $step2 ? '✓' : '2' }}</span>
                            <span @class(['transition-colors', 'text-base-content/60' => ! $step2, 'text-base-content' => $step2])>Select Part</span>
                        </div>
                        <x-icon name="o-chevron-right" class="mx-1 h-3 w-3 text-base-content/30 max-sm:mx-0" />
                        <div class="flex items-center gap-1.5">
                            <span @class(['flex h-5 w-5 items-center justify-center rounded-full text-[10px] font-bold',
                                'bg-primary text-primary-content' => $step2,
                                'bg-base-300 text-base-content/50' => ! $step2,
                            ])>{{ $step2 ? '3' : '3' }}</span>
                            <span @class(['transition-colors',
                                'text-base-content/60' => ! $step2,
                                'text-primary font-semibold' => $step2,
                            ])>Inspect &amp; Submit</span>
                        </div>
                    </div>

                    <div class="grid gap-6">
                        {{-- Recheck confirmation --}}
                        @if ($needsRecheck)
                            <x-card shadow>
                                <div class="flex flex-col items-center gap-4 py-6 text-center">
                                    <x-icon name="o-exclamation-triangle" class="h-10 w-10 text-warning" />
                                    <div>
                                        <p class="text-lg font-semibold">Already checked OK today</p>
                                        <p class="mt-1 text-sm text-base-content/60">
                                            This part was already inspected with all <span class="font-medium text-success">OK</span> measurements
                                            for this station, stage, and shift. Are you sure you want to recheck?
                                        </p>
                                    </div>
                                    <div class="flex gap-3">
                                        <x-button label="No, go back" link="{{ route('inspections.station-spot.index') }}" />
                                        <x-button label="Yes, recheck" wire:click="confirmRecheck" class="btn-warning" icon="o-check" />
                                    </div>
                                </div>
                            </x-card>
                        @endif

                        {{-- Part history --}}
                        @if (! $needsRecheck && $partHistory && $partHistory['total'] > 0)
                            <x-card title="Inspection history" subtitle="Previous inspections for this part on Station Spot lines." shadow>
                                <div class="mb-4 grid grid-cols-2 gap-3 sm:grid-cols-4">
                                    <div class="rounded-xl border border-base-300 px-4 py-3 text-center">
                                        <p class="text-2xl font-bold text-base-content">{{ $partHistory['total'] }}</p>
                                        <p class="text-xs text-base-content/50">Total</p>
                                    </div>
                                    @foreach ([
                                        'start' => ['label' => 'Start', 'color' => 'text-info'],
                                        'middle' => ['label' => 'Middle', 'color' => 'text-warning'],
                                        'end' => ['label' => 'End', 'color' => 'text-success'],
                                    ] as $key => $cfg)
                                        <div class="rounded-xl border border-base-300 border-t-2 px-4 py-3 text-center"
                                            @class([
                                                'border-t-info/30' => $key === 'start',
                                                'border-t-warning/30' => $key === 'middle',
                                                'border-t-success/30' => $key === 'end',
                                            ])
                                        >
                                            <p class="text-2xl font-bold {{ $cfg['color'] }}">{{ $partHistory['byStage'][$key] ?? 0 }}</p>
                                            <p class="text-xs text-base-content/50">{{ $cfg['label'] }}</p>
                                        </div>
                                    @endforeach
                                </div>
                                @if ($latest = $partHistory['latest'])
                                    <div class="flex flex-wrap items-center gap-x-2 gap-y-1 text-sm text-base-content/60">
                                        <x-icon name="o-clock" class="h-4 w-4" />
                                        <span>Latest:</span>
                                        <span class="text-xs text-base-content/50">{{ $latest->checked_at->diffForHumans() }}</span>
                                        <span class="hidden text-base-content/30 sm:inline">·</span>
                                        <span class="hidden sm:inline">{{ $latest->workStation->name }}</span>
                                        <span class="hidden text-base-content/30 sm:inline">·</span>
                                        <span class="hidden sm:inline">{{ $latest->stage->label() }}</span>
                                    </div>
                                @endif
                            </x-card>
                        @endif

                        {{-- Checklist form --}}
                        @if (! $needsRecheck)
                            <x-card title="Hardware Measurements" subtitle="Enter measurement values for each installed hardware type." shadow>
                                <div class="grid gap-8">
                                    @forelse ($partMappings ?? [] as $index => $mapping)
                                        <fieldset>
                                            <legend class="mb-2 flex items-center gap-2 text-sm font-medium">
                                                <span class="flex h-6 w-6 items-center justify-center rounded-full bg-primary text-xs text-primary-content">{{ $index + 1 }}</span>
                                                {{ $mapping->hardwareType->part_name }}
                                                <span class="font-mono text-[10px] text-base-content/40">({{ $mapping->hardwareType->part_number }})</span>
                                                <span class="text-[10px] text-red-400">*required</span>
                                            </legend>
                                            <div class="flex flex-wrap items-end gap-4">
                                                <div class="w-40">
                                                    <x-input
                                                        label="Measurement ({{ $mapping->measurement_type->label() }})"
                                                        type="number"
                                                        step="0.01"
                                                        wire:model.live="measurements.{{ $mapping->id }}"
                                                        placeholder="0.00"
                                                    />
                                                </div>
                                                @if ($mapping->measurementStandard)
                                                    <div class="pb-2 text-xs text-base-content/50">
                                                        Standard: {{ $mapping->measurementStandard->min_value }} – {{ $mapping->measurementStandard->max_value }} {{ $mapping->measurementStandard->unit }}
                                                    </div>
                                                @endif
                                                @php
                                                    $val = $measurements[$mapping->id] ?? '';
                                                    $judgement = $val !== '' ? $this->autoJudgement((float) $val, $mapping) : null;
                                                @endphp
                                                @if ($judgement)
                                                    <div class="pb-2">
                                                        <x-badge
                                                            :value="$judgement->label()"
                                                            :class="($judgement->badgeClass() ?? 'badge-ghost') . ' badge-sm text-white font-bold'"
                                                        />
                                                    </div>
                                                @endif
                                            </div>
                                        </fieldset>
                                    @empty
                                        <p class="rounded-lg border border-dashed border-base-300 px-4 py-6 text-center text-sm text-base-content/50">
                                            No hardware mappings configured for this part. Set them up in the part editing page.
                                        </p>
                                    @endforelse

                                    <hr class="border-base-300" />

                                    {{-- Remarks --}}
                                    <x-textarea
                                        wire:model="remarks"
                                        label="Remarks"
                                        placeholder="Any additional notes about this inspection..."
                                        rows="2"
                                    />
                                </div>
                            </x-card>
                        @endif
                    </div>
                @endif
            </div>
        </div>

        @if ($selectedPart && ! $needsRecheck)
            <x-slot:actions>
                <x-button label="Cancel" link="{{ route('inspections.station-spot.index') }}" />
                <x-button label="Submit inspection" icon="o-check" class="btn-primary" type="submit" spinner="save" />
            </x-slot:actions>
        @endif
    </x-form>
</div>
