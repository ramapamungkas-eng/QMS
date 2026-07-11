<?php

use App\Models\HardwareType;
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
#[Title('Hardware Types')]
class extends Component {
    use Toast, WithPagination;

    #[Url(history: true)]
    public string $search = '';

    public array $sortBy = ['column' => 'part_name', 'direction' => 'asc'];

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function delete(int $id): void
    {
        $hardwareType = HardwareType::withCount('partMappings')->findOrFail($id);

        if ($hardwareType->part_mappings_count > 0) {
            $this->error(
                "Can't delete — {$hardwareType->part_name} is still mapped to ".
                    "{$hardwareType->part_mappings_count} part(s). Remove those mappings first.",
                position: 'toast-bottom'
            );

            return;
        }

        $hardwareType->delete();

        $this->success("{$hardwareType->part_name} deleted.", position: 'toast-bottom');
    }

    public function headers(): array
    {
        return [
            ['key' => 'id', 'label' => '#', 'class' => 'w-1'],
            ['key' => 'image', 'label' => '', 'class' => 'w-12', 'sortable' => false],
            ['key' => 'part_number', 'label' => 'Part Number', 'class' => 'w-48'],
            ['key' => 'part_name', 'label' => 'Part Name'],
            ['key' => 'part_mappings_count', 'label' => 'Used By', 'class' => 'w-32'],
        ];
    }

    public function hardwareTypes(): LengthAwarePaginator
    {
        return HardwareType::query()
            ->withCount('partMappings')
            ->when($this->search, function (Builder $query) {
                $query->where('part_number', 'like', "%{$this->search}%")
                    ->orWhere('part_name', 'like', "%{$this->search}%");
            })
            ->orderBy(...array_values($this->sortBy))
            ->paginate(10);
    }

    public function with(): array
    {
        return [
            'hardwareTypes' => $this->hardwareTypes(),
            'headers' => $this->headers(),
        ];
    }
}; ?>

<div>
    <x-header title="Hardware Types" subtitle="Nuts, bolts, and other hardware used in Station Spot welding." separator progress-indicator>
        <x-slot:middle class="!justify-end">
            <x-input placeholder="Search part number or name..." wire:model.live.debounce.350ms="search" clearable icon="o-magnifying-glass" />
        </x-slot:middle>
        <x-slot:actions>
            <x-button label="New hardware" link="{{ route('hardware.create') }}" icon="o-plus" class="btn-primary" responsive />
        </x-slot:actions>
    </x-header>

    <x-card shadow>
        <x-table :headers="$headers" :rows="$hardwareTypes" :sort-by="$sortBy" with-pagination>
            @scope('cell_image', $hardwareType)
                <div class="avatar">
                    <div class="h-8 w-8 rounded-full">
                        <img src="{{ $hardwareType->imageUrl() }}" alt="{{ $hardwareType->part_name }}">
                    </div>
                </div>
            @endscope

            @scope('cell_part_number', $hardwareType)
                <span class="font-mono">{{ $hardwareType->part_number }}</span>
            @endscope

            @scope('cell_part_mappings_count', $hardwareType)
                <x-badge :value="$hardwareType->part_mappings_count.' part(s)'" class="badge-outline" />
            @endscope

            @scope('actions', $hardwareType)
                <div class="flex gap-1">
                    <x-button icon="o-pencil" link="{{ route('hardware.edit', $hardwareType) }}" class="btn-ghost btn-sm" />
                    <x-button
                        icon="o-trash"
                        wire:click="delete({{ $hardwareType->id }})"
                        wire:confirm="Are you sure you want to delete this hardware type?"
                        spinner
                        class="btn-ghost btn-sm text-error"
                    />
                </div>
            @endscope
        </x-table>
    </x-card>
</div>
