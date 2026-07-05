<?php

use App\Enums\WorkStationType;
use App\Models\Part;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;
use Mary\Traits\Toast;

new
#[Layout('layouts.app')]
#[Title('New part')]
class extends Component {
    use Toast, WithFileUploads;

    public string $part_number = '';

    public string $part_name = '';

    public string $model = '';

    public string $variant = '';

    public $photo;

    public array $stationTypes = [];

    public function rules(): array
    {
        return [
            'part_number' => ['required', 'string', 'max:100', 'unique:parts,part_number'],
            'part_name' => ['required', 'string', 'max:255'],
            'model' => ['nullable', 'string', 'max:100'],
            'variant' => ['nullable', 'string', 'max:100'],
            'photo' => ['nullable', 'image', 'max:2048'],
            'stationTypes' => ['required', 'array', 'min:1'],
            'stationTypes.*' => [Rule::enum(WorkStationType::class)],
        ];
    }

    public function save(): void
    {
        $data = $this->validate();

        if ($this->photo) {
            $data['image'] = $this->photo->store('part-images', 'public');
        }

        $part = Part::create($data);
        foreach ($this->stationTypes as $type) {
            $part->stationTypes()->create(['work_station_type' => $type]);
        }

        $this->success('Part created. Now set up its hardware mapping and standards.', position: 'toast-bottom', redirectTo: route('parts.edit', $part));
    }

    public function with(): array
    {
        return [
            'stationTypeOptions' => WorkStationType::cases(),
        ];
    }
}; ?>

<div>
    <x-header title="New part" subtitle="Register a single part. You can add hardware mappings and standards after saving." separator>
        <x-slot:actions>
            <x-button label="Back to list" link="{{ route('parts.index') }}" icon="o-arrow-left" responsive />
        </x-slot:actions>
    </x-header>

    <x-form wire:submit="save">
        <div class="grid gap-6 lg:grid-cols-12">
            <div class="lg:col-span-4">
                <div class="sticky top-4 overflow-hidden rounded-2xl border border-base-300 bg-gradient-to-b from-base-200/60 to-base-100">
                    <div class="flex flex-col items-center gap-3 px-6 py-8">
                        <x-file wire:model="photo" accept="image/png, image/jpeg" class="!w-28">
                            <div class="relative">
                                <img
                                    src="{{ $photo ? $photo->temporaryUrl() : 'https://ui-avatars.com/api/?size=256&background=475569&color=fff&name='.urlencode($part_name ?: 'Part') }}"
                                    class="h-28 w-28 rounded-full object-cover shadow-lg ring-4 ring-base-100"
                                />
                                <span class="absolute bottom-0 right-0 grid h-8 w-8 place-items-center rounded-full bg-primary text-primary-content shadow">
                                    <x-icon name="o-camera" class="h-4 w-4" />
                                </span>
                            </div>
                        </x-file>

                        <div class="text-center">
                            <p class="text-lg font-semibold leading-tight">{{ $part_name ?: 'New part' }}</p>
                            <p class="font-mono text-sm text-base-content/60">{{ $part_number ?: 'No part number yet' }}</p>
                        </div>
                    </div>

                    <div class="space-y-3 border-t border-base-300 bg-base-100/60 px-6 py-5 text-sm text-base-content/70">
                        <div class="flex items-start gap-2">
                            <x-icon name="o-hashtag" class="mt-0.5 h-4 w-4 shrink-0" />
                            <p>Part number must be unique across all parts.</p>
                        </div>
                        <div class="flex items-start gap-2">
                            <x-icon name="o-cube" class="mt-0.5 h-4 w-4 shrink-0" />
                            <p>Model and variant help identify the part across processes.</p>
                        </div>
                        <div class="flex items-start gap-2">
                            <x-icon name="o-arrows-right-left" class="mt-0.5 h-4 w-4 shrink-0" />
                            <p>Select which station type(s) this part is used in. Parts can be shared across station types (e.g. Stamping + Station Spot).</p>
                        </div>
                        <div class="flex items-start gap-2">
                            <x-icon name="o-photo" class="mt-0.5 h-4 w-4 shrink-0" />
                            <p>Photo is optional — JPEG or PNG, up to 2MB.</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="lg:col-span-8">
                <x-card title="Part details" subtitle="Basic information about this part." shadow>
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
                                <label class="flex cursor-pointer items-center gap-2 rounded-xl border px-4 py-3 text-sm transition hover:border-base-content/30 has-[:checked]:border-primary has-[:checked]:bg-primary/5 has-[:checked]:font-medium">
                                    <input type="checkbox" wire:model="stationTypes" value="{{ $st->value }}" class="checkbox checkbox-primary checkbox-sm" />
                                    {{ $st->label() }}
                                </label>
                            @endforeach
                        </div>
                        @error('stationTypes')
                            <p class="mt-1 text-xs text-error">{{ $message }}</p>
                        @enderror
                    </div>
                </x-card>
            </div>
        </div>

        <x-slot:actions>
            <x-button label="Cancel" link="{{ route('parts.index') }}" />
            <x-button label="Create" icon="o-check" class="btn-primary" type="submit" spinner="save" />
        </x-slot:actions>
    </x-form>
</div>
