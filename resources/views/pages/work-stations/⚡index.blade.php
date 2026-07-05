<?php

use App\Models\WorkStation;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Mary\Traits\Toast;

new
#[Layout('layouts.app')]
#[Title('Work Stations')]
class extends Component {
    use Toast;

    #[Url(history: true)]
    public string $search = '';

    public function delete(int $id): void
    {
        $workStation = WorkStation::findOrFail($id);
        $workStation->delete();

        $this->success("{$workStation->name} deleted.", position: 'toast-bottom');
    }

    public function with(): array
    {
        $query = WorkStation::with('process')->orderBy('process_id')->orderBy('name');

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', "%{$this->search}%")
                    ->orWhereHas('process', fn ($p) => $p->where('name', 'like', "%{$this->search}%"));
            });
        }

        return [
            'workStations' => $query->get()->groupBy('process.name'),
        ];
    }
}; ?>

<div>
    <x-header title="Work Stations" subtitle="Physical lines for Stamping and Welding." separator progress-indicator>
        <x-slot:middle class="!justify-end">
            <x-input placeholder="Search station name or process..." wire:model.live.debounce="search" clearable icon="o-magnifying-glass" />
        </x-slot:middle>
        <x-slot:actions>
            <x-button label="New work station" link="{{ route('work-stations.create') }}" icon="o-plus" class="btn-primary" responsive />
        </x-slot:actions>
    </x-header>

    <div class="grid gap-6">
        @forelse ($workStations as $processName => $stations)
            <x-card :title="$processName" subtitle="{{ count($stations) }} station(s)" shadow>
                <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($stations as $station)
                        <div class="rounded-xl border border-base-300 bg-base-100 p-4 transition hover:border-base-content/30 hover:shadow-sm">
                            <div class="flex items-start justify-between gap-2">
                                <div class="min-w-0 flex-1">
                                    <p class="truncate font-medium">{{ $station->name }}</p>
                                    <div class="mt-1 flex flex-wrap gap-1">
                                        @php
                                            $badgeClass = match ($station->type->value) {
                                                'stamping' => 'badge-primary',
                                                'station_spot' => 'badge-success',
                                                'portable_spot' => 'badge-warning',
                                                'robot_spot' => 'badge-accent',
                                                default => 'badge-ghost',
                                            };
                                        @endphp
                                        <x-badge :value="$station->type->label()" :class="$badgeClass.' badge-sm'" />
                                    </div>
                                </div>
                                <div class="flex shrink-0 gap-1">
                                    <x-button icon="o-pencil" link="{{ route('work-stations.edit', $station) }}" class="btn-ghost btn-xs" />
                                    <x-button
                                        icon="o-trash"
                                        wire:click="delete({{ $station->id }})"
                                        wire:confirm="Are you sure you want to delete this work station?"
                                        spinner
                                        class="btn-ghost btn-xs text-error"
                                    />
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </x-card>
        @empty
            <x-card shadow>
                <p class="py-8 text-center text-base-content/50">
                    @if ($search)
                        No work stations match "{{ $search }}".
                    @else
                        No work stations yet. <a href="{{ route('work-stations.create') }}" class="link link-primary">Create one</a>.
                    @endif
                </p>
            </x-card>
        @endforelse
    </div>
</div>
