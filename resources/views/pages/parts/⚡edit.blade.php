<?php

use App\Enums\MeasurementType;
use App\Models\HardwareType;
use App\Models\Part;
use App\Models\PartHardwareMapping;
use App\Models\StationType;
use App\Models\WeldLengthStandard;
use App\Models\WorkStation;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;
use Mary\Traits\Toast;

new
#[Layout('layouts.app')]
#[Title('Edit part')]
class extends Component {
    use Toast, WithFileUploads;

    public Part $part;

    // --- Basic fields ---
    public string $part_number = '';

    public string $part_name = '';

    public string $model = '';

    public string $variant = '';

    public $photo;

    // --- Hardware mapping modal state (Station Spot) ---
    public bool $showMappingModal = false;

    public ?int $editingMappingId = null;

    public string $mapping_hardware_type_id = '';

    public string $mapping_measurement_type = '';

    public int $mapping_usage_qty = 1;

    public string $mapping_min_value = '';

    public string $mapping_max_value = '';

    public string $mapping_unit = '';

    // --- Weld length standard modal state (Robot Spot) ---
    public bool $showWeldModal = false;

    public ?int $editingWeldStandardId = null;

    public string $weld_work_station_id = '';

    public string $weld_min_length = '';

    public string $weld_max_length = '';

    public string $weld_unit = 'mm';

    public array $stationTypes = [];

    public function mount(Part $part): void
    {
        $this->part = $part;
        $this->part_number = $part->part_number;
        $this->part_name = $part->part_name;
        $this->model = (string) $part->model;
        $this->variant = (string) $part->variant;
        $this->stationTypes = $part->stationTypes->pluck('station_type_id')->map(fn ($id) => (string) $id)->toArray();
    }

    public function rules(): array
    {
        return [
            'part_number' => ['required', 'string', 'max:100', Rule::unique('parts', 'part_number')->ignore($this->part->id)],
            'part_name' => ['required', 'string', 'max:255'],
            'model' => ['nullable', 'string', 'max:100'],
            'variant' => ['nullable', 'string', 'max:100'],
            'photo' => ['nullable', 'image', 'max:2048'],
            'stationTypes' => ['required', 'array', 'min:1'],
            'stationTypes.*' => ['exists:work_station_types,id'],
        ];
    }

    public function save(): void
    {
        $data = $this->validate();

        if ($this->photo) {
            $data['image'] = $this->photo->store('part-images', 'public');
        }

        $this->part->update($data);
        $this->part->stationTypes()->delete();
        foreach ($this->stationTypes as $typeId) {
            $this->part->stationTypes()->create(['station_type_id' => $typeId]);
        }

        $this->success('Part updated.', position: 'toast-bottom');
    }

    // --- Hardware mapping CRUD (nested under this part) ---

    public function hardwareTypeOptions(): array
    {
        return HardwareType::orderBy('part_name')
            ->get()
            ->map(fn (HardwareType $hardwareType) => ['id' => $hardwareType->id, 'name' => "{$hardwareType->part_name} ({$hardwareType->part_number})"])
            ->all();
    }

    public function measurementTypeOptions(): array
    {
        return array_map(
            fn (MeasurementType $type) => ['id' => $type->value, 'name' => $type->label()],
            MeasurementType::cases(),
        );
    }

    public function stationTypeOptions(): array
    {
        return StationType::orderBy('name')->get()->all();
    }

    public function openCreateMapping(): void
    {
        $this->reset([
            'editingMappingId',
            'mapping_hardware_type_id',
            'mapping_measurement_type',
            'mapping_usage_qty',
            'mapping_min_value',
            'mapping_max_value',
            'mapping_unit',
        ]);
        $this->mapping_usage_qty = 1;
        $this->resetValidation();
        $this->showMappingModal = true;
    }

    public function openEditMapping(int $mappingId): void
    {
        $mapping = PartHardwareMapping::with('measurementStandard')->findOrFail($mappingId);

        $this->editingMappingId = $mapping->id;
        $this->mapping_hardware_type_id = (string) $mapping->hardware_type_id;
        $this->mapping_measurement_type = $mapping->measurement_type->value;
        $this->mapping_usage_qty = $mapping->usage_qty;
        $this->mapping_min_value = (string) optional($mapping->measurementStandard)->min_value;
        $this->mapping_max_value = (string) optional($mapping->measurementStandard)->max_value;
        $this->mapping_unit = optional($mapping->measurementStandard)->unit ?? $mapping->measurement_type->defaultUnit();

        $this->resetValidation();
        $this->showMappingModal = true;
    }

    public function saveMapping(): void
    {
        $uniqueRule = Rule::unique('part_hardware_mappings', 'hardware_type_id')
            ->where(fn ($query) => $query->where('part_id', $this->part->id)->where('measurement_type', $this->mapping_measurement_type));

        if ($this->editingMappingId) {
            $uniqueRule->ignore($this->editingMappingId);
        }

        $data = $this->validate([
            'mapping_hardware_type_id' => ['required', 'exists:hardware_types,id', $uniqueRule],
            'mapping_measurement_type' => ['required', Rule::enum(MeasurementType::class)],
            'mapping_usage_qty' => ['required', 'integer', 'min:1', 'max:255'],
            'mapping_min_value' => ['required', 'numeric', 'lt:mapping_max_value'],
            'mapping_max_value' => ['required', 'numeric'],
            'mapping_unit' => ['required', 'string', 'max:20'],
        ]);

        $mappingData = [
            'hardware_type_id' => $data['mapping_hardware_type_id'],
            'measurement_type' => $data['mapping_measurement_type'],
            'usage_qty' => $data['mapping_usage_qty'],
        ];

        $standardData = [
            'min_value' => $data['mapping_min_value'],
            'max_value' => $data['mapping_max_value'],
            'unit' => $data['mapping_unit'],
        ];

        if ($this->editingMappingId) {
            $mapping = PartHardwareMapping::findOrFail($this->editingMappingId);
            $mapping->update($mappingData);
        } else {
            $mapping = $this->part->hardwareMappings()->create($mappingData);
        }

        $mapping->measurementStandard()->updateOrCreate([], $standardData);

        $this->showMappingModal = false;
        $this->success('Hardware mapping saved.', position: 'toast-bottom');
    }

    public function deleteMapping(int $mappingId): void
    {
        PartHardwareMapping::findOrFail($mappingId)->delete();

        $this->success('Hardware mapping removed.', position: 'toast-bottom');
    }

    // --- Weld length standards CRUD (per work station for Robot Spot) ---

    public function robotWorkStationOptions(): array
    {
        return WorkStation::whereHas('stationType', fn ($q) => $q->where('slug', 'robot-spot'))
            ->orderBy('name')
            ->get()
            ->map(fn (WorkStation $ws) => ['id' => $ws->id, 'name' => $ws->name])
            ->all();
    }

    public function openCreateWeldStandard(): void
    {
        $this->reset([
            'editingWeldStandardId',
            'weld_work_station_id',
            'weld_min_length',
            'weld_max_length',
            'weld_unit',
        ]);
        $this->weld_unit = 'mm';
        $this->resetValidation();
        $this->showWeldModal = true;
    }

    public function openEditWeldStandard(int $standardId): void
    {
        $standard = WeldLengthStandard::findOrFail($standardId);

        $this->editingWeldStandardId = $standard->id;
        $this->weld_work_station_id = (string) $standard->work_station_id;
        $this->weld_min_length = (string) $standard->min_length;
        $this->weld_max_length = (string) $standard->max_length;
        $this->weld_unit = $standard->unit;

        $this->resetValidation();
        $this->showWeldModal = true;
    }

    public function saveWeldStandard(): void
    {
        $data = $this->validate([
            'weld_work_station_id' => ['required', 'exists:work_stations,id'],
            'weld_min_length' => ['required', 'numeric', 'lt:weld_max_length'],
            'weld_max_length' => ['required', 'numeric'],
            'weld_unit' => ['required', 'string', 'max:20'],
        ]);

        $standardData = [
            'work_station_id' => $data['weld_work_station_id'],
            'min_length' => $data['weld_min_length'],
            'max_length' => $data['weld_max_length'],
            'unit' => $data['weld_unit'],
        ];

        if ($this->editingWeldStandardId) {
            WeldLengthStandard::findOrFail($this->editingWeldStandardId)->update($standardData);
        } else {
            $this->part->weldLengthStandards()->create($standardData);
        }

        $this->showWeldModal = false;
        $this->success('Weld length standard saved.', position: 'toast-bottom');
    }

    public function deleteWeldStandard(int $standardId): void
    {
        WeldLengthStandard::findOrFail($standardId)->delete();

        $this->success('Weld length standard removed.', position: 'toast-bottom');
    }

    public function with(): array
    {
        return [
            'mappings' => $this->part->hardwareMappings()->with(['hardwareType', 'measurementStandard'])->get(),
            'hardwareTypeOptions' => $this->hardwareTypeOptions(),
            'measurementTypeOptions' => $this->measurementTypeOptions(),
            'stationTypeOptions' => $this->stationTypeOptions(),
            'stationSpotId' => StationType::where('slug', 'station-spot')->value('id'),
            'robotSpotId' => StationType::where('slug', 'robot-spot')->value('id'),
            'weldStandards' => $this->part->weldLengthStandards()->with('workStation')->get(),
            'robotWorkStationOptions' => $this->robotWorkStationOptions(),
            'mappingHeaders' => [
                ['key' => 'hardware', 'label' => 'Hardware'],
                ['key' => 'measurement', 'label' => 'Measurement'],
                ['key' => 'usage_qty', 'label' => 'Usage Qty', 'class' => 'w-24'],
                ['key' => 'standard', 'label' => 'Standard'],
                ['key' => 'actions', 'label' => '', 'class' => 'w-24', 'sortable' => false],
            ],
            'weldHeaders' => [
                ['key' => 'work_station', 'label' => 'Work Station', 'class' => 'w-40'],
                ['key' => 'standard', 'label' => 'Standard'],
                ['key' => 'actions', 'label' => '', 'class' => 'w-24', 'sortable' => false],
            ],
        ];
    }
}; ?>

<div>
    <x-header title="Edit part" subtitle="{{ $part->part_number }} — {{ $part->part_name }}" separator>
        <x-slot:actions>
            <x-button label="Back to list" link="{{ route('parts.index') }}" icon="o-arrow-left" responsive />
        </x-slot:actions>
    </x-header>

    <div class="grid gap-6 lg:grid-cols-12">
        <!-- SIDEBAR -->
        <div class="lg:col-span-3">
            <div class="sticky top-4 overflow-hidden rounded-2xl border border-base-300 bg-gradient-to-b from-base-200/60 to-base-100">
                <div class="flex flex-col items-center gap-3 px-6 py-8">
                    <x-file wire:model="photo" accept="image/png, image/jpeg" class="!w-28">
                        <div class="relative">
                            <img
                                src="{{ $photo ? $photo->temporaryUrl() : $part->imageUrl() }}"
                                class="h-28 w-28 rounded-full object-cover shadow-lg ring-4 ring-base-100"
                            />
                            <span class="absolute bottom-0 right-0 grid h-8 w-8 place-items-center rounded-full bg-primary text-primary-content shadow">
                                <x-icon name="o-camera" class="h-4 w-4" />
                            </span>
                        </div>
                    </x-file>

                    <div class="text-center">
                        <p class="text-lg font-semibold leading-tight">{{ $part_name ?: $part->part_name }}</p>
                        <p class="font-mono text-sm text-base-content/60">{{ $part_number ?: $part->part_number }}</p>
                    </div>
                </div>

                <div class="space-y-3 border-t border-base-300 bg-base-100/60 px-6 py-5 text-sm text-base-content/70">
                    <div class="flex items-start gap-2">
                        <x-icon name="o-information-circle" class="mt-0.5 h-4 w-4 shrink-0" />
                        <p>A part can be associated with multiple station types (e.g. Stamping + Station Spot) or just one (e.g. only Portable Spot).</p>
                    </div>
                    <div class="flex items-start gap-2">
                        <x-icon name="o-photo" class="mt-0.5 h-4 w-4 shrink-0" />
                        <p>Upload a photo to help identify the part visually.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- MAIN -->
        <div class="grid gap-6 lg:col-span-9">
            <!-- BASIC INFO -->
            <x-form wire:submit="save">
                <x-card title="Identity" subtitle="Basic part information." shadow>
                    <div class="grid gap-4 sm:grid-cols-2">
                        <x-input label="Part number" wire:model="part_number" icon="o-hashtag" class="sm:col-span-2" />
                        <x-input label="Part name" wire:model="part_name" icon="o-cube" class="sm:col-span-2" />
                        <x-input label="Model" wire:model="model" icon="o-tag" />
                        <x-input label="Variant" wire:model="variant" icon="o-tag" />
                    </div>

                    <hr class="my-6 border-base-300" />

                    <div>
                        <p class="mb-2 text-sm font-medium">Station Types</p>
                        <p class="mb-3 text-xs text-base-content/50">This part is inspected at:</p>
                        <div class="flex flex-wrap gap-4">
                            @foreach ($stationTypeOptions as $st)
                                <label wire:key="{{ 'st-'.$st->id }}" class="flex cursor-pointer items-center gap-2 rounded-xl border px-4 py-3 text-sm transition hover:border-base-content/30 has-[:checked]:border-primary has-[:checked]:bg-primary/5 has-[:checked]:font-medium">
                                    <input type="checkbox" wire:model.live="stationTypes" value="{{ $st->id }}" class="checkbox checkbox-primary checkbox-sm" />
                                    {{ $st->name }}
                                </label>
                            @endforeach
                        </div>
                        @error('stationTypes')
                            <p class="mt-1 text-xs text-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <x-slot:actions>
                        <x-button label="Save changes" icon="o-check" class="btn-primary" type="submit" spinner="save" />
                    </x-slot:actions>
                </x-card>
            </x-form>

            @if (in_array($stationSpotId, $stationTypes))
            <!-- HARDWARE & MEASUREMENT STANDARDS (STATION SPOT) -->
            <x-card title="Hardware & Measurement Standards" subtitle="Configure hardware types and measurement tolerances for Station Spot welding judgement." shadow>
                <x-slot:menu>
                    <x-button label="Add hardware" icon="o-plus" class="btn-sm" wire:click="openCreateMapping" />
                </x-slot:menu>

                @if ($mappings->isEmpty())
                    <p class="rounded-lg border border-dashed border-base-300 px-4 py-6 text-center text-sm text-base-content/50">
                        No hardware mapped yet.
                    </p>
                @else
                    <x-table :headers="$mappingHeaders" :rows="$mappings" sortable>
                        @scope('cell_hardware', $mapping)
                            <div>
                                <span class="font-mono text-sm">{{ $mapping->hardwareType->part_number }}</span>
                                <span class="block text-xs text-base-content/60">{{ $mapping->hardwareType->part_name }}</span>
                            </div>
                        @endscope

                        @scope('cell_measurement', $mapping)
                            <x-badge :value="$mapping->measurement_type->label()" class="badge-outline" />
                        @endscope

                        @scope('cell_usage_qty', $mapping)
                            {{ $mapping->usage_qty }}
                        @endscope

                        @scope('cell_standard', $mapping)
                            @if ($mapping->measurementStandard)
                                <span class="font-mono text-sm">{{ $mapping->measurementStandard->min_value }}–{{ $mapping->measurementStandard->max_value }} {{ $mapping->measurementStandard->unit }}</span>
                            @else
                                <span class="text-base-content/40">not set</span>
                            @endif
                        @endscope

                        @scope('cell_actions', $mapping)
                            <div class="flex gap-1">
                                <x-button icon="o-pencil" wire:click="openEditMapping({{ $mapping->id }})" class="btn-ghost btn-sm" />
                                <x-button
                                    icon="o-trash"
                                    wire:click="deleteMapping({{ $mapping->id }})"
                                    wire:confirm="Remove this hardware mapping and its standard?"
                                    spinner
                                    class="btn-ghost btn-sm text-error"
                                />
                            </div>
                        @endscope
                    </x-table>
                @endif
            </x-card>
            @endif

            @if (in_array($robotSpotId, $stationTypes))
            <!-- WELD LENGTH STANDARDS (ROBOT SPOT) -->
            <x-card title="Weld Length Standards" subtitle="Per work station. Used for Robot Spot welding judgement." shadow>
                <x-slot:menu>
                    <x-button label="Add standard" icon="o-plus" class="btn-sm" wire:click="openCreateWeldStandard" />
                </x-slot:menu>

                @if ($weldStandards->isEmpty())
                    <p class="rounded-lg border border-dashed border-base-300 px-4 py-6 text-center text-sm text-base-content/50">
                        No weld length standards configured for this part.
                    </p>
                @else
                    <x-table :headers="$weldHeaders" :rows="$weldStandards" sortable>
                        @scope('cell_work_station', $standard)
                            <span class="font-medium">{{ $standard->workStation?->name ?? '—' }}</span>
                        @endscope

                        @scope('cell_standard', $standard)
                            <span class="font-mono text-sm">{{ $standard->min_length }}–{{ $standard->max_length }} {{ $standard->unit }}</span>
                        @endscope

                        @scope('cell_actions', $standard)
                            <div class="flex gap-1">
                                <x-button icon="o-pencil" wire:click="openEditWeldStandard({{ $standard->id }})" class="btn-ghost btn-sm" />
                                <x-button
                                    icon="o-trash"
                                    wire:click="deleteWeldStandard({{ $standard->id }})"
                                    wire:confirm="Remove this weld length standard?"
                                    spinner
                                    class="btn-ghost btn-sm text-error"
                                />
                            </div>
                        @endscope
                    </x-table>
                @endif
            </x-card>
            @endif
        </div>
    </div>

    <!-- ADD/EDIT HARDWARE MAPPING MODAL -->
    <x-modal wire:model="showMappingModal" :title="$editingMappingId ? 'Edit hardware mapping' : 'Add hardware mapping'" separator>
        <div class="grid gap-4">
            <x-select label="Hardware" wire:model="mapping_hardware_type_id" :options="$hardwareTypeOptions" placeholder="Select hardware..." />
            @error('mapping_hardware_type_id')
                <p class="mt-1 text-xs text-error">{{ $message }}</p>
            @enderror
            <x-select label="Measurement type" wire:model="mapping_measurement_type" :options="$measurementTypeOptions" placeholder="Select..." />
            @error('mapping_measurement_type')
                <p class="mt-1 text-xs text-error">{{ $message }}</p>
            @enderror
            <x-input label="Usage qty" type="number" min="1" wire:model="mapping_usage_qty" hint="How many are physically installed (checker still enters one measurement)." />
            @error('mapping_usage_qty')
                <p class="mt-1 text-xs text-error">{{ $message }}</p>
            @enderror

            <div class="grid grid-cols-3 gap-3">
                <x-input label="Min value" wire:model="mapping_min_value" />
                <x-input label="Max value" wire:model="mapping_max_value" />
                <x-input label="Unit" wire:model="mapping_unit" />
            </div>
            @error('mapping_min_value')
                <p class="mt-1 text-xs text-error">{{ $message }}</p>
            @enderror
            @error('mapping_max_value')
                <p class="mt-1 text-xs text-error">{{ $message }}</p>
            @enderror
            @error('mapping_unit')
                <p class="mt-1 text-xs text-error">{{ $message }}</p>
            @enderror
        </div>

        <x-slot:actions>
            <x-button label="Cancel" @click="$wire.showMappingModal = false" />
            <x-button label="Save" icon="o-check" class="btn-primary" wire:click="saveMapping" spinner="saveMapping" />
        </x-slot:actions>
    </x-modal>

    <!-- ADD/EDIT WELD LENGTH STANDARD MODAL -->
    <x-modal wire:model="showWeldModal" :title="$editingWeldStandardId ? 'Edit weld length standard' : 'Add weld length standard'" separator>
        <div class="grid gap-4">
            <x-select label="Work Station" wire:model="weld_work_station_id" :options="$robotWorkStationOptions" placeholder="Select robot work station..." />
            @error('weld_work_station_id')
                <p class="mt-1 text-xs text-error">{{ $message }}</p>
            @enderror

            <div class="grid grid-cols-3 gap-3">
                <x-input label="Min length" wire:model="weld_min_length" />
                <x-input label="Max length" wire:model="weld_max_length" />
                <x-input label="Unit" wire:model="weld_unit" />
            </div>
            @error('weld_min_length')
                <p class="mt-1 text-xs text-error">{{ $message }}</p>
            @enderror
            @error('weld_max_length')
                <p class="mt-1 text-xs text-error">{{ $message }}</p>
            @enderror
            @error('weld_unit')
                <p class="mt-1 text-xs text-error">{{ $message }}</p>
            @enderror
        </div>

        <x-slot:actions>
            <x-button label="Cancel" @click="$wire.showWeldModal = false" />
            <x-button label="Save" icon="o-check" class="btn-primary" wire:click="saveWeldStandard" spinner="saveWeldStandard" />
        </x-slot:actions>
    </x-modal>
</div>
