<?php

use App\Models\Process;
use App\Models\StationType;
use App\Models\WorkStation;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Mary\Traits\Toast;

new
#[Layout('layouts.app')]
#[Title('Edit work station')]
class extends Component {
    use Toast;

    public WorkStation $workStation;

    public string $process_id = '';

    public string $name = '';

    public string $station_type_id = '';

    public function mount(WorkStation $workStation): void
    {
        $this->workStation = $workStation;
        $this->process_id = (string) $workStation->process_id;
        $this->name = $workStation->name;
        $this->station_type_id = (string) $workStation->station_type_id;
    }

    public function processOptions(): array
    {
        return Process::orderBy('name')
            ->get()
            ->map(fn (Process $process) => ['id' => $process->id, 'name' => $process->name])
            ->all();
    }

    public function typeOptions(): array
    {
        return StationType::with('process')->orderBy('name')
            ->get()
            ->map(fn (StationType $st) => [
                'id' => $st->id,
                'name' => $st->name,
                'description' => $st->description,
                'process' => $st->process?->name,
            ])
            ->all();
    }

    public function rules(): array
    {
        return [
            'process_id' => ['required', 'exists:processes,id'],
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('work_stations', 'name')->where('process_id', $this->process_id)->ignore($this->workStation->id),
            ],
            'station_type_id' => ['required', 'exists:work_station_types,id'],
        ];
    }

    public function save(): void
    {
        $this->workStation->update([
            'process_id' => $this->process_id,
            'name' => $this->name,
            'station_type_id' => $this->station_type_id,
        ]);

        $this->success('Work station updated.', position: 'toast-bottom', redirectTo: route('work-stations.index'));
    }

    public function with(): array
    {
        return [
            'processOptions' => $this->processOptions(),
            'typeOptions' => $this->typeOptions(),
        ];
    }
}; ?>

<div>
    <x-header title="Edit work station" subtitle="Update {{ $workStation->name }}." separator>
        <x-slot:actions>
            <x-button label="Back to list" link="{{ route('work-stations.index') }}" icon="o-arrow-left" responsive />
        </x-slot:actions>
    </x-header>

    <x-form wire:submit="save">
        <div class="grid gap-6 lg:grid-cols-12">
            <div class="lg:col-span-8">
                <x-card title="Station details" shadow>
                    <div class="grid gap-4">
                        <x-select label="Process" wire:model="process_id" :options="$processOptions" placeholder="Select process..." />
                        <x-input label="Name" wire:model="name" icon="o-map-pin" />
                    </div>
                </x-card>

                <x-card title="Station type" subtitle="Drives which checklist form and judgement logic this station uses." shadow class="mt-6">
                    <div class="grid gap-3">
                        @foreach ($typeOptions as $option)
                            <label
                                class="flex cursor-pointer items-start gap-3 rounded-xl border p-4 transition
                                    {{ $station_type_id === $option['id'] ? 'border-primary bg-primary/5 ring-1 ring-primary' : 'border-base-300 hover:border-base-content/30' }}"
                            >
                                <input type="radio" wire:model="station_type_id" value="{{ $option['id'] }}" class="radio radio-primary radio-sm mt-0.5" />
                                <div class="min-w-0">
                                    <span class="block font-medium">{{ $option['name'] }}</span>
                                    <span class="mt-0.5 block text-sm text-base-content/60">{{ $option['process'] }} — {{ $option['description'] }}</span>
                                </div>
                            </label>
                        @endforeach
                    </div>
                </x-card>
            </div>

            <div class="lg:col-span-4">
                <div class="sticky top-4 overflow-hidden rounded-2xl border border-base-300 bg-gradient-to-b from-base-200/60 to-base-100 px-6 py-6">
                    <div class="flex items-start gap-2 text-sm text-base-content/70">
                        <x-icon name="o-information-circle" class="mt-0.5 h-4 w-4 shrink-0" />
                        <p>The process and type determine what data is collected during inspections. Changing the type may affect existing inspection records.</p>
                    </div>
                </div>
            </div>
        </div>

        <x-slot:actions>
            <x-button label="Cancel" link="{{ route('work-stations.index') }}" />
            <x-button label="Save changes" icon="o-check" class="btn-primary" type="submit" spinner="save" />
        </x-slot:actions>
    </x-form>
</div>
