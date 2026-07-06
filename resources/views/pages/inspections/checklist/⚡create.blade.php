<?php

use App\Enums\InspectionStage;
use App\Models\InspectionRecord;
use App\Models\Part;
use App\Models\StationType;
use App\Models\WeldLengthStandard;
use App\Models\WorkStation;
use App\Services\AutoJudgementService;
use App\Services\ChecklistTemplateService;
use App\Support\ShiftResolver;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Mary\Traits\Toast;

new
#[Layout('layouts.app')]
#[Title('New Inspection')]
class extends Component {
    use Toast;

    public ?StationType $workStationType = null;

    public string $workStationId = '';

    public string $stage = '';

    public string $partSearch = '';

    public ?int $partId = null;

    public array $values = [];

    public array $groupData = [];

    public bool $recheckConfirmed = false;

    public function mount(): void
    {
        $this->workStationType = StationType::where('slug', request()->segment(2))->first();

        $options = $this->workStationOptions();

        if (count($options) === 1) {
            $this->workStationId = (string) $options[0]['id'];
        }
    }

    public function updatedWorkStationId(): void
    {
        $this->recheckConfirmed = false;
        $this->values = [];
        $this->groupData = [];
        $this->initFieldValues();
    }

    public function updatedStage(): void
    {
        $this->recheckConfirmed = false;
    }

    public function updatedPartId(): void
    {
        $this->recheckConfirmed = false;
        $this->values = [];
        $this->groupData = [];
    }

    public function confirmRecheck(): void
    {
        $this->recheckConfirmed = true;
    }

    public function weldStandardExists(): bool
    {
        if (! $this->workStationId || ! $this->partId) {
            return false;
        }

        return WeldLengthStandard::where('part_id', $this->partId)
            ->where('work_station_id', $this->workStationId)
            ->exists();
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

        return InspectionRecord::where('part_id', $this->partId)
            ->where('work_station_id', $this->workStationId)
            ->where('stage', $this->stage)
            ->whereDate('production_date', $productionDate)
            ->whereHas('fieldValues', fn ($q) => $q->where('auto_judgement', 'ok'))
            ->exists();
    }

    public function workStationOptions(): array
    {
        return WorkStation::where('station_type_id', $this->workStationType->id)
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

        $this->initFieldValues();
    }

    protected function initFieldValues(): void
    {
        if (! $this->partId) {
            return;
        }

        $template = app(ChecklistTemplateService::class)->forType($this->workStationType);

        if ($template === null) {
            return;
        }

        foreach ($template->sections as $section) {
            if ($section->label === 'Weld Length Measurement' && ! $this->weldStandardExists()) {
                continue;
            }

            if ($section->allow_multiple && $section->source_type === 'part_hardware_mappings') {
                $mappings = Part::findOrFail($this->partId)->hardwareMappings()->with('hardwareType', 'measurementStandard')->get();

                foreach ($mappings as $mapping) {
                    $standard = $mapping->measurementStandard;

                    $row = [
                        'source_id' => $mapping->id,
                        'source_label' => $mapping->hardwareType?->part_name . ' (' . $mapping->hardwareType?->part_number . ')',
                        'standard' => $standard
                            ? $standard->min_value . ' – ' . $standard->max_value . ' ' . $standard->unit
                            : null,
                        'values' => [],
                    ];

                    foreach ($section->fields as $field) {
                        $row['values'][$field->field_key] = '';
                    }

                    $this->groupData[] = $row;
                }
            } else {
                foreach ($section->fields as $field) {
                    if (! array_key_exists($field->field_key, $this->values)) {
                        $this->values[$field->field_key] = '';
                    }
                }
            }
        }
    }

    public function removePart(): void
    {
        $this->partId = null;
        $this->partSearch = '';
        $this->values = [];
        $this->groupData = [];
    }

    public function partHistory(): ?array
    {
        if (! $this->partId) {
            return null;
        }

        $records = InspectionRecord::where('part_id', $this->partId)
            ->whereHas('workStation', fn ($q) => $q->where('station_type_id', $this->workStationType->id))
            ->with('fieldValues.field')
            ->latest('checked_at')
            ->get();

        return [
            'total' => $records->count(),
            'byStage' => $records->groupBy(fn ($r) => $r->stage->value)->map->count(),
            'latest' => $records->first(),
        ];
    }

    public function rules(): array
    {
        $template = app(ChecklistTemplateService::class)->forType($this->workStationType);
        $rules = [
            'workStationId' => ['required', 'exists:work_stations,id'],
            'stage' => ['required', Rule::enum(InspectionStage::class)],
            'partId' => ['required', 'exists:parts,id'],
        ];

        if ($template === null) {
            return $rules;
        }

        $skipWeld = ! $this->weldStandardExists();

        foreach ($template->sections as $section) {
            if ($section->label === 'Weld Length Measurement' && $skipWeld) {
                continue;
            }

            if ($section->allow_multiple) {
                foreach ($section->fields as $field) {
                    if ($field->required) {
                        $rules["groupData.*.values.{$field->field_key}"] = ['required', $this->fieldValidationRule($field)];
                    } else {
                        $rules["groupData.*.values.{$field->field_key}"] = ['nullable', $this->fieldValidationRule($field)];
                    }
                }
            } else {
                foreach ($section->fields as $field) {
                    if ($field->required) {
                        $rules["values.{$field->field_key}"] = ['required', $this->fieldValidationRule($field)];
                    } else {
                        $rules["values.{$field->field_key}"] = ['nullable', $this->fieldValidationRule($field)];
                    }
                }
            }
        }

        return $rules;
    }

    protected function fieldValidationRule(\App\Models\ChecklistField $field): string
    {
        return match ($field->field_type) {
            'boolean' => 'in:0,1',
            'numeric' => 'numeric',
            'enum' => Rule::in($field->options ?? []),
            default => 'string|max:1000',
        };
    }

    public function save(): void
    {
        $data = $this->validate();
        $autoJudgementService = app(AutoJudgementService::class);
        $skipWeld = ! $this->weldStandardExists();

        $record = DB::transaction(function () use ($autoJudgementService, $skipWeld) {
            $record = InspectionRecord::create([
                'part_id' => $this->partId,
                'work_station_id' => $this->workStationId,
                'stage' => $this->stage,
            ]);

            $template = app(ChecklistTemplateService::class)->forType($this->workStationType);

            if ($template === null) {
                return $record;
            }

            foreach ($template->sections as $section) {
                if ($section->label === 'Weld Length Measurement' && $skipWeld) {
                    continue;
                }

                if ($section->allow_multiple) {
                    foreach ($this->groupData as $index => $group) {
                        foreach ($section->fields as $field) {
                            $value = $group['values'][$field->field_key] ?? '';
                            $autoJudgement = $autoJudgementService->judge(
                                $field, $value, $group['source_id'] ?? null, $record
                            );

                            $record->fieldValues()->create([
                                'field_id' => $field->id,
                                'value' => $value !== '' ? $value : null,
                                'auto_judgement' => $autoJudgement,
                                'group_index' => $index,
                                'source_id' => $group['source_id'] ?? null,
                            ]);
                        }
                    }
                } else {
                    foreach ($section->fields as $field) {
                        $value = $this->values[$field->field_key] ?? '';
                        $autoJudgement = $autoJudgementService->judge($field, $value, null, $record);

                        $record->fieldValues()->create([
                            'field_id' => $field->id,
                            'value' => $value !== '' ? $value : null,
                            'auto_judgement' => $autoJudgement,
                            'group_index' => 0,
                        ]);
                    }
                }
            }

            return $record;
        });

        $slug = $this->workStationType->slug;

        $this->success(
            "{$this->workStationType->name} inspection recorded.",
            position: 'toast-bottom',
            redirectTo: route("inspections.{$slug}.index"),
        );
    }

    public function with(): array
    {
        $templateService = app(ChecklistTemplateService::class);
        $template = $templateService->forType($this->workStationType);

        $backUrl = route('inspections.' . $this->workStationType->slug . '.index');

        return [
            'template' => $template,
            'backUrl' => $backUrl,
            'workStationOptions' => $this->workStationOptions(),
            'stageOptions' => $this->stageOptions(),
            'partHistory' => $this->partHistory(),
            'partSearchResults' => strlen($this->partSearch) >= 2
                ? Part::where(function ($q) {
                    $q->where('part_number', 'like', "%{$this->partSearch}%")
                        ->orWhere('part_name', 'like', "%{$this->partSearch}%");
                })
                    ->whereHas('stationTypes', fn ($q) => $q->where('station_type_id', $this->workStationType->id))
                    ->orderBy('part_number')
                    ->limit(8)
                    ->get()
                : collect(),
            'selectedPart' => $this->partId ? Part::where('id', $this->partId)
                ->whereHas('stationTypes', fn ($q) => $q->where('station_type_id', $this->workStationType->id))
                ->first() : null,
            'needsRecheck' => $this->needsRecheckConfirmation(),
            'weldStandardExists' => $this->weldStandardExists(),
        ];
    }
}; ?>

<div>
    <x-header title="New {{ $workStationType->name }} Inspection" subtitle="{{ $workStationType->description }}" separator>
        <x-slot:actions>
            <x-button label="Back to records" link="{{ $backUrl }}" icon="o-arrow-left" responsive />
        </x-slot:actions>
    </x-header>

    <x-form wire:submit="save">
        <div class="grid gap-6 lg:grid-cols-12">
            {{-- SIDEBAR: Context selection --}}
            <div class="lg:col-span-4">
                <div class="sticky top-4 grid gap-6 max-lg:static max-lg:grid-cols-1 max-lg:gap-4">
                    <x-card title="Inspection context" subtitle="What, where, and when." shadow>
                        <div class="grid gap-4">
                            @if (count($workStationOptions) > 1)
                                <x-select
                                    label="Work station"
                                    wire:model="workStationId"
                                    :options="$workStationOptions"
                                    placeholder="Select station..."
                                />
                            @endif

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

            {{-- MAIN: History + Checklist --}}
            <div class="lg:col-span-8">
                @if (! $selectedPart)
                    <x-card shadow>
                        <div class="flex flex-col items-center gap-5 py-12 text-center">
                            <x-icon name="o-clipboard-document" class="h-12 w-12 text-base-content/30" />
                            <div class="max-w-sm">
                                <p class="text-base font-semibold text-base-content/60">Start your inspection</p>
                                <ol class="mt-3 space-y-2 text-left text-sm text-base-content/40">
                                    @if (count($workStationOptions) > 1)
                                        <li class="flex items-start gap-2">
                                            <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-base-300 text-xs font-medium text-base-content/50">1</span>
                                            Choose a work station in the sidebar
                                        </li>
                                        <li class="flex items-start gap-2">
                                            <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-base-300 text-xs font-medium text-base-content/50">2</span>
                                            Select the inspection stage
                                        </li>
                                    @else
                                        <li class="flex items-start gap-2">
                                            <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-base-300 text-xs font-medium text-base-content/50">1</span>
                                            Select the inspection stage
                                        </li>
                                    @endif
                                    <li class="flex items-start gap-2">
                                        <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-base-300 text-xs font-medium text-base-content/50">3</span>
                                        Search and pick the part being inspected
                                    </li>
                                </ol>
                            </div>
                        </div>
                    </x-card>
                @else
                    {{-- Step progress indicator --}}
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
                            <span @class(['transition-colors', 'text-base-content/60' => ! $step1, 'text-base-content' => $step1])>
                                @if (count($workStationOptions) > 1) Station &amp; Stage @else Stage @endif
                            </span>
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
                                            This part was already inspected with an <span class="font-medium text-success">OK</span> result
                                            for this station, stage, and shift. Are you sure you want to recheck?
                                        </p>
                                    </div>
                                    <div class="flex gap-3">
                                        <x-button label="No, go back" link="{{ $backUrl }}" />
                                        <x-button label="Yes, recheck" wire:click="confirmRecheck" class="btn-warning" icon="o-check" />
                                    </div>
                                </div>
                            </x-card>
                        @endif

                        {{-- Part history --}}
                        @if (! $needsRecheck && $partHistory && $partHistory['total'] > 0)
                            <x-card title="Inspection history" subtitle="Previous inspections for this part." shadow>
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
                                        <span>{{ $latest->checked_at->diffForHumans() }}</span>
                                        <span class="hidden text-base-content/30 sm:inline">·</span>
                                        <span class="hidden sm:inline">{{ $latest->workStation->name }}</span>
                                        <span class="hidden text-base-content/30 sm:inline">·</span>
                                        <span class="hidden sm:inline">{{ $latest->stage->label() }}</span>
                                    </div>
                                @endif
                            </x-card>
                        @endif

                        {{-- Checklist form --}}
                        @if (! $needsRecheck && $template)
                            @foreach ($template->sections as $sectionIndex => $section)
                                @continue ($section->label === 'Weld Length Measurement' && ! $weldStandardExists)

                                <x-card title="{{ $section->label }}" shadow>
                                    <div class="grid gap-6">
                                        @if ($section->allow_multiple && $section->source_type === 'part_hardware_mappings')
                                            {{-- Multi-row section (e.g. Station Spot hardware measurements) --}}
                                            @forelse ($groupData as $groupIndex => $group)
                                                <div class="rounded-xl border border-base-300 p-4">
                                                    <div class="mb-3 flex items-baseline justify-between gap-2">
                                                        <p class="text-sm font-semibold text-base-content/70">
                                                            {{ $group['source_label'] }}
                                                        </p>
                                                        @if ($group['standard'])
                                                            <p class="shrink-0 text-xs text-base-content/50">
                                                                Standard: <span class="font-mono font-medium text-base-content/70">{{ $group['standard'] }}</span>
                                                            </p>
                                                        @endif
                                                    </div>
                                                    <div class="grid gap-4">
                                                        @foreach ($section->fields as $field)
                                                            <div>
                                                                @if ($field->field_type === 'numeric')
                                                                    <x-input
                                                                        label="{{ $group['source_label'] }} — {{ $field->label }}"
                                                                        wire:model="groupData.{{ $groupIndex }}.values.{{ $field->field_key }}"
                                                                        type="number"
                                                                        step="any"
                                                                        placeholder="Enter value{{ $field->unit ? ' ('.$field->unit.')' : '' }}"
                                                                    />
                                                                @elseif ($field->field_type === 'text')
                                                                    <x-textarea
                                                                        label="{{ $field->label }}"
                                                                        wire:model="groupData.{{ $groupIndex }}.values.{{ $field->field_key }}"
                                                                        rows="2"
                                                                    />
                                                                @endif
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            @empty
                                                <p class="text-sm text-base-content/40">No hardware mappings configured for this part.</p>
                                            @endforelse
                                        @else
                                            {{-- Standard fields --}}
                                            @foreach ($section->fields as $field)
                                                <fieldset>
                                                    <legend class="mb-2 flex items-center gap-2 text-sm font-medium">
                                                        <span class="flex h-6 w-6 items-center justify-center rounded-full bg-primary text-xs text-primary-content">{{ $sectionIndex + 1 }}</span>
                                                        {{ $field->label }}
                                                        @if ($field->required)
                                                            <span class="text-[10px] text-red-400">*required</span>
                                                        @endif
                                                    </legend>

                                                    @if ($field->field_type === 'boolean')
                                                        <div class="flex flex-wrap gap-3">
                                                            @foreach ([['value' => '0', 'label' => 'No'], ['value' => '1', 'label' => 'Yes']] as $opt)
                                                                <label
                                                                    class="flex cursor-pointer items-center gap-2 rounded-xl border px-4 py-2.5 text-sm transition
                                                                        {{ ($values[$field->field_key] ?? '') === $opt['value'] ? 'border-primary bg-primary/5 font-medium' : 'border-base-300 hover:border-base-content/30' }}"
                                                                >
                                                                    <input type="radio" wire:model.live="values.{{ $field->field_key }}" value="{{ $opt['value'] }}" class="radio radio-primary radio-sm" />
                                                                    {{ $opt['label'] }}
                                                                </label>
                                                            @endforeach
                                                        </div>
                                                    @elseif ($field->field_type === 'enum')
                                                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                                                            @foreach ($field->options ?? [] as $option)
                                                                <label
                                                                    class="flex cursor-pointer flex-col gap-1 rounded-xl border p-3 text-sm transition
                                                                        {{ ($values[$field->field_key] ?? '') === $option ? 'border-primary bg-primary/5 ring-1 ring-primary' : 'border-base-300 hover:border-base-content/30' }}"
                                                                >
                                                                    <div class="flex items-center gap-2">
                                                                        <input type="radio" wire:model.live="values.{{ $field->field_key }}" value="{{ $option }}" class="radio radio-primary radio-sm" />
                                                                        <span class="font-medium">{{ $option }}</span>
                                                                    </div>
                                                                </label>
                                                            @endforeach
                                                        </div>
                                                    @elseif ($field->field_type === 'numeric')
                                                        <x-input
                                                            wire:model="values.{{ $field->field_key }}"
                                                            type="number"
                                                            step="any"
                                                            placeholder="Enter value{{ $field->unit ? ' ('.$field->unit.')' : '' }}"
                                                        />
                                                    @elseif ($field->field_type === 'text')
                                                        <x-textarea
                                                            wire:model="values.{{ $field->field_key }}"
                                                            rows="2"
                                                            placeholder="Enter {{ strtolower($field->label) }}"
                                                        />
                                                    @endif
                                                </fieldset>
                                            @endforeach
                                        @endif
                                    </div>
                                </x-card>
                            @endforeach
                        @endif
                    </div>
                @endif
            </div>
        </div>

        @if ($selectedPart && ! $needsRecheck)
            <x-slot:actions>
                <x-button label="Cancel" link="{{ $backUrl }}" />
                <x-button label="Submit inspection" icon="o-check" class="btn-primary" type="submit" spinner="save" />
            </x-slot:actions>
        @endif
    </x-form>
</div>
