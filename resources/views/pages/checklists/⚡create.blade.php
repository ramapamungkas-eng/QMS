<?php

use App\Models\ChecklistTemplate;
use App\Models\StationType;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Mary\Traits\Toast;

new
#[Layout('layouts.app')]
#[Title('New checklist template')]
class extends Component {
    use Toast;

    public string $station_type_id = '';

    public string $name = '';

    public bool $active = true;

    public function rules(): array
    {
        return [
            'station_type_id' => ['required', 'exists:work_station_types,id'],
            'name' => ['required', 'string', 'max:255'],
            'active' => ['boolean'],
        ];
    }

    public function save(): void
    {
        $data = $this->validate();

        ChecklistTemplate::create([
            'station_type_id' => $data['station_type_id'],
            'name' => $data['name'],
            'active' => $data['active'],
        ]);

        $this->success('Template created. Now add sections and fields.', position: 'toast-bottom', redirectTo: route('checklists.index'));
    }

    public function with(): array
    {
        return [
            'stationTypeOptions' => StationType::with('process')->orderBy('name')->get()->map(
                fn (StationType $st) => ['id' => $st->id, 'name' => "{$st->process->name} — {$st->name}"],
            )->all(),
        ];
    }
}; ?>

<div>
    <x-header title="New checklist template" subtitle="Define which station type this template applies to." separator>
        <x-slot:actions>
            <x-button label="Back to list" link="{{ route('checklists.index') }}" icon="o-arrow-left" responsive />
        </x-slot:actions>
    </x-header>

    <x-form wire:submit="save">
        <x-card title="Template info" subtitle="One active template per station type." shadow>
            <div class="grid gap-4">
                <x-select
                    label="Station Type"
                    wire:model="station_type_id"
                    :options="$stationTypeOptions"
                    placeholder="Select station type..."
                    hint="Each station type can have only one active template."
                />
                <x-input label="Template name" wire:model="name" icon="o-document-text" />
                <x-toggle label="Active" wire:model="active" />
            </div>

            <x-slot:actions>
                <x-button label="Cancel" link="{{ route('checklists.index') }}" />
                <x-button label="Create" icon="o-check" class="btn-primary" type="submit" spinner="save" />
            </x-slot:actions>
        </x-card>
    </x-form>
</div>
