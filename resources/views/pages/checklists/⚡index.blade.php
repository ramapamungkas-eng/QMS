<?php

use App\Models\ChecklistTemplate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Mary\Traits\Toast;

new
#[Layout('layouts.app')]
#[Title('Checklist Templates')]
class extends Component {
    use Toast;

    public function delete(int $id): void
    {
        $template = ChecklistTemplate::findOrFail($id);
        $template->delete();

        $this->success("Template deleted.", position: 'toast-bottom');
    }

    public function with(): array
    {
        return [
            'templates' => ChecklistTemplate::with('stationType.process')->orderBy('name')->get(),
            'headers' => [
                ['key' => 'station_type', 'label' => 'Process / Station Type', 'class' => 'w-64', 'sortable' => false],
                ['key' => 'name', 'label' => 'Template Name'],
                ['key' => 'active', 'label' => 'Status', 'class' => 'w-24', 'sortable' => false],
                ['key' => 'actions', 'label' => '', 'class' => 'w-24', 'sortable' => false],
            ],
        ];
    }
}; ?>

<div>
    <x-header title="Checklist Templates" subtitle="Define the inspection form layout per station type." separator progress-indicator>
        <x-slot:actions>
            <x-button label="Tutorial" link="{{ route('checklists.tutorial') }}" icon="o-book-open" class="btn-ghost" responsive />
            <x-button label="New template" link="{{ route('checklists.create') }}" icon="o-plus" class="btn-primary" responsive />
        </x-slot:actions>
    </x-header>

    <x-card shadow>
        <x-table :headers="$headers" :rows="$templates">
            @scope('cell_station_type', $template)
                <div>
                    <span class="text-xs text-base-content/50">{{ $template->stationType?->process?->name ?? '—' }}</span>
                    <span class="block text-sm font-medium">{{ $template->stationType?->name ?? '—' }}</span>
                </div>
            @endscope

            @scope('cell_active', $template)
                <x-badge :value="$template->active ? 'Active' : 'Inactive'" :class="$template->active ? 'badge-success' : 'badge-ghost'" />
            @endscope

            @scope('actions', $template)
                <div class="flex gap-1">
                    <x-button icon="o-pencil" link="{{ route('checklists.edit', $template) }}" class="btn-ghost btn-sm" />
                    <x-button
                        icon="o-trash"
                        wire:click="delete({{ $template->id }})"
                        wire:confirm="Delete this template and all its sections & fields?"
                        spinner
                        class="btn-ghost btn-sm text-error"
                    />
                </div>
            @endscope
        </x-table>
    </x-card>
</div>
