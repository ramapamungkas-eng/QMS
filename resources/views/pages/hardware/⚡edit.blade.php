<?php

use App\Models\HardwareType;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;
use Mary\Traits\Toast;

new
#[Layout('layouts.app')]
#[Title('Edit hardware type')]
class extends Component {
    use Toast, WithFileUploads;

    public HardwareType $hardwareType;

    public string $part_number = '';

    public string $part_name = '';

    public $photo;

    public function mount(HardwareType $hardwareType): void
    {
        $this->hardwareType = $hardwareType;
        $this->part_number = $hardwareType->part_number;
        $this->part_name = $hardwareType->part_name;
    }

    public function rules(): array
    {
        return [
            'part_number' => ['required', 'string', 'max:100', Rule::unique('hardware_types', 'part_number')->ignore($this->hardwareType->id)],
            'part_name' => ['required', 'string', 'max:255'],
            'photo' => ['nullable', 'image', 'max:2048'],
        ];
    }

    public function save(): void
    {
        $data = $this->validate();

        if ($this->photo) {
            $data['image'] = $this->photo->store('hardware-images', 'public');
        }

        $this->hardwareType->update($data);

        $this->success('Hardware type updated.', position: 'toast-bottom', redirectTo: route('hardware.index'));
    }
}; ?>

<div>
    <x-header title="Edit hardware type" subtitle="Update {{ $hardwareType->part_name }}." separator>
        <x-slot:actions>
            <x-button label="Back to list" link="{{ route('hardware.index') }}" icon="o-arrow-left" responsive />
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
                                    src="{{ $photo ? $photo->temporaryUrl() : $hardwareType->imageUrl() }}"
                                    class="h-28 w-28 rounded-full object-cover shadow-lg ring-4 ring-base-100"
                                />
                                <span class="absolute bottom-0 right-0 grid h-8 w-8 place-items-center rounded-full bg-primary text-primary-content shadow">
                                    <x-icon name="o-camera" class="h-4 w-4" />
                                </span>
                            </div>
                        </x-file>

                        <div class="text-center">
                            <p class="text-lg font-semibold leading-tight">{{ $part_name ?: $hardwareType->part_name }}</p>
                            <p class="font-mono text-sm text-base-content/60">{{ $part_number ?: $hardwareType->part_number }}</p>
                        </div>
                    </div>

                    <div class="space-y-3 border-t border-base-300 bg-base-100/60 px-6 py-5 text-sm text-base-content/70">
                        <div class="flex items-start gap-2">
                            <x-icon name="o-hashtag" class="mt-0.5 h-4 w-4 shrink-0" />
                            <p>Part number must be unique across all hardware types.</p>
                        </div>
                        <div class="flex items-start gap-2">
                            <x-icon name="o-photo" class="mt-0.5 h-4 w-4 shrink-0" />
                            <p>Upload a new photo to replace the existing one.</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="lg:col-span-8">
                <x-card title="Hardware details" subtitle="Basic information about this hardware type." shadow>
                    <div class="grid gap-4">
                        <x-input label="Part number" wire:model="part_number" icon="o-hashtag" />
                        <x-input label="Part name" wire:model="part_name" icon="o-wrench" />
                    </div>
                </x-card>
            </div>
        </div>

        <x-slot:actions>
            <x-button label="Cancel" link="{{ route('hardware.index') }}" />
            <x-button label="Save changes" icon="o-check" class="btn-primary" type="submit" spinner="save" />
        </x-slot:actions>
    </x-form>
</div>
