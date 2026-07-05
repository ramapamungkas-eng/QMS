<?php

use App\Models\Part;
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
#[Title('Parts')]
class extends Component {
    use Toast, WithPagination;

    #[Url(history: true)]
    public string $search = '';

    public array $sortBy = ['column' => 'part_number', 'direction' => 'asc'];

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function delete(int $id): void
    {
        $part = Part::findOrFail($id);
        $part->delete();

        $this->success("{$part->part_number} deleted.", position: 'toast-bottom');
    }

    public function headers(): array
    {
        return [
            ['key' => 'id', 'label' => '#', 'class' => 'w-1'],
            ['key' => 'image', 'label' => '', 'class' => 'w-12', 'sortable' => false],
            ['key' => 'part_number', 'label' => 'Part Number', 'class' => 'w-48'],
            ['key' => 'part_name', 'label' => 'Part Name'],
            ['key' => 'model', 'label' => 'Model', 'class' => 'w-32'],
            ['key' => 'variant', 'label' => 'Variant', 'class' => 'w-32'],
            ['key' => 'station_types', 'label' => 'Station Types', 'class' => 'w-40', 'sortable' => false],
        ];
    }

    public function parts(): LengthAwarePaginator
    {
        return Part::query()
            ->with('stationTypes')
            ->when($this->search, function (Builder $query) {
                $query->where('part_number', 'like', "%{$this->search}%")
                    ->orWhere('part_name', 'like', "%{$this->search}%")
                    ->orWhere('model', 'like', "%{$this->search}%")
                    ->orWhere('variant', 'like', "%{$this->search}%");
            })
            ->orderBy(...array_values($this->sortBy))
            ->paginate(10);
    }

    public function with(): array
    {
        return [
            'parts' => $this->parts(),
            'headers' => $this->headers(),
        ];
    }
}; ?>

<div>
    <x-header title="Parts" subtitle="Single parts tracked through Stamping and Welding." separator progress-indicator>
        <x-slot:middle class="!justify-end">
            <x-input placeholder="Search part number, name, model, or variant..." wire:model.live.debounce="search" clearable icon="o-magnifying-glass" />
        </x-slot:middle>
        <x-slot:actions>
            <x-button label="New part" link="{{ route('parts.create') }}" icon="o-plus" class="btn-primary" responsive />
        </x-slot:actions>
    </x-header>

    <x-card shadow>
        <x-table :headers="$headers" :rows="$parts" :sort-by="$sortBy" with-pagination>
            @scope('cell_image', $part)
                <div class="avatar">
                    <div class="h-8 w-8 rounded-full">
                        <img src="{{ $part->imageUrl() }}" alt="{{ $part->part_name }}">
                    </div>
                </div>
            @endscope

            @scope('cell_part_number', $part)
                <span class="font-mono">{{ $part->part_number }}</span>
            @endscope

            @scope('cell_model', $part)
                {{ $part->model ?? '—' }}
            @endscope

            @scope('cell_variant', $part)
                {{ $part->variant ?? '—' }}
            @endscope

            @scope('cell_station_types', $part)
                <div class="flex flex-wrap gap-1">
                    @forelse ($part->stationTypes as $st)
                        @php
                            $type = \App\Enums\WorkStationType::tryFrom($st->work_station_type);
                            $badgeClass = match ($type) {
                                \App\Enums\WorkStationType::Stamping => 'badge-info',
                                \App\Enums\WorkStationType::StationSpot => 'badge-accent',
                                \App\Enums\WorkStationType::PortableSpot => 'badge-warning',
                                \App\Enums\WorkStationType::RobotSpot => 'badge-secondary',
                                default => 'badge-ghost',
                            };
                        @endphp
                        <span class="badge badge-sm {{ $badgeClass }}">{{ $type?->label() ?? $st->work_station_type }}</span>
                    @empty
                        <span class="text-xs text-base-content/40">—</span>
                    @endforelse
                </div>
            @endscope

            @scope('actions', $part)
                <div class="flex gap-1">
                    <x-button icon="o-pencil" link="{{ route('parts.edit', $part) }}" class="btn-ghost btn-sm" />
                    <x-button
                        icon="o-trash"
                        wire:click="delete({{ $part->id }})"
                        wire:confirm="This also removes any hardware mappings and standards for this part. Are you sure?"
                        spinner
                        class="btn-ghost btn-sm text-error"
                    />
                </div>
            @endscope
        </x-table>
    </x-card>
</div>
