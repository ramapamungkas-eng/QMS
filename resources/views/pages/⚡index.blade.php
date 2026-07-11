<?php

use App\Enums\Shift;
use App\Models\StationType;
use App\Services\ChecklistTemplateService;
use App\Services\InspectionStatsService;
use App\Support\ShiftResolver;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

new
#[Layout('layouts.app')]
#[Title('Dashboard')]
class extends Component
{
    public string $greeting;

    public Shift $shift;

    public string $productionDate;

    #[Url(history: true)]
    public string $search = '';

    public function mount(): void
    {
        $now = now();
        $hour = (int) $now->format('H');
        $this->greeting = match (true) {
            $hour < 12 => 'Good morning',
            $hour < 17 => 'Good afternoon',
            default => 'Good evening',
        };

        [$this->shift, $this->productionDate] = ShiftResolver::resolve($now);
    }

    public function accessibleTypes(): array
    {
        return app(ChecklistTemplateService::class)->accessibleTypes();
    }

    public function typeStats(): Collection
    {
        $statsService = app(InspectionStatsService::class);
        $types = $this->accessibleTypes();

        $allStats = $statsService->allByTypes($this->productionDate, $types);
        $statsByType = collect($allStats)->keyBy('id');

        return collect($types)
            ->map(function (StationType $type) use ($statsByType) {
                $slug = $type->slug;
                $stats = $statsByType->get($type->id, [
                    'total' => 0, 'ok' => 0, 'ng' => 0, 'pass_rate' => 0,
                ]);

                return [
                    'id' => $type->id,
                    'type' => $type,
                    'label' => $type->name,
                    'icon' => $type->icon,
                    'process' => $type->process?->name ?? '—',
                    'description' => $type->description,
                    'total' => $stats['total'],
                    'ok' => $stats['ok'],
                    'ng' => $stats['ng'],
                    'pass_rate' => $stats['pass_rate'],
                    'route_index' => route("inspections.{$slug}.index"),
                    'route_create' => route("inspections.{$slug}.create"),
                ];
            })
            ->filter(function (array $stat) {
                if ($this->search === '') {
                    return true;
                }

                $term = strtolower($this->search);

                return str_contains(strtolower($stat['label']), $term)
                    || str_contains(strtolower($stat['process']), $term)
                    || str_contains(strtolower($stat['description']), $term);
            });
    }

    public function headers(): array
    {
        return [
            ['key' => 'label', 'label' => 'Station Type', 'class' => 'w-48'],
            ['key' => 'process', 'label' => 'Process', 'class' => 'w-32'],
            ['key' => 'description', 'label' => 'Description', 'class' => 'min-w-[12rem]'],
            ['key' => 'total', 'label' => 'Total', 'class' => 'w-20 text-right'],
            ['key' => 'ok', 'label' => 'OK', 'class' => 'w-20 text-right'],
            ['key' => 'ng', 'label' => 'NG', 'class' => 'w-20 text-right'],
            ['key' => 'pass_rate', 'label' => 'Pass Rate', 'class' => 'w-28 text-right'],
        ];
    }

    public function todaySummary(): array
    {
        $types = $this->accessibleTypes();

        return app(InspectionStatsService::class)->overallSummary($this->productionDate, $types);
    }

    public function recentNgItems(): Collection
    {
        $types = $this->accessibleTypes();

        return app(InspectionStatsService::class)->recentNgRecords($this->productionDate, $types);
    }

    public function with(): array
    {
        return [
            'typeStats' => $this->typeStats(),
            'headers' => $this->headers(),
            'summary' => $this->todaySummary(),
            'recentNg' => $this->recentNgItems(),
            'user' => auth()->user(),
        ];
    }
}; ?>

<div class="space-y-6">
    {{-- Welcome header --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl md:text-3xl font-extrabold text-base-content">
                {{ $greeting }}, {{ $user->name }}
            </h1>
            <p class="text-sm text-base-content/60 mt-1">
                <span class="font-semibold">{{ $user->role->label() }}</span>
                &middot;
                <span class="inline-flex items-center gap-1">
                    <x-icon name="o-calendar" class="w-3.5 h-3.5" />
                    {{ Carbon::parse($productionDate)->format('D, d M Y') }}
                </span>
                &middot;
                <span class="inline-flex items-center gap-1">
                    <x-icon name="{{ $shift === 'Day' ? 'o-sun' : 'o-moon' }}" class="w-3.5 h-3.5" />
                    {{ $shift }} Shift
                </span>
            </p>
        </div>
    </div>

    {{-- Summary stats row --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 md:gap-4">
        <div class="rounded-2xl border border-base-300 bg-base-100 p-3 md:p-4 kpi-accent kpi-accent-total">
            <div class="flex items-center gap-2 md:gap-3">
                <div class="grid h-9 w-9 md:h-11 md:w-11 shrink-0 place-items-center rounded-xl bg-primary/10 text-primary">
                    <x-icon name="o-clipboard-document-check" class="w-4 h-4 md:w-5 md:h-5" />
                </div>
                <div class="min-w-0 flex-1">
                    <div class="text-[10px] md:text-xs text-base-content/50 uppercase font-bold tracking-wider">Total Today</div>
                    <div class="text-xl md:text-2xl font-extrabold mt-0.5">{{ $summary['total'] }}</div>
                    <div class="flex items-center gap-2 mt-1">
                        <span class="text-[10px] text-base-content/40 flex items-center gap-1">
                            <x-icon name="o-cube" class="w-3 h-3" /> {{ $summary['parts_checked'] }} parts
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <div class="rounded-2xl border border-base-300 bg-base-100 p-3 md:p-4 kpi-accent kpi-accent-ok">
            <div class="flex items-center gap-2 md:gap-3">
                <div class="grid h-9 w-9 md:h-11 md:w-11 shrink-0 place-items-center rounded-xl bg-success/10 text-success">
                    <x-icon name="o-check-circle" class="w-4 h-4 md:w-5 md:h-5" />
                </div>
                <div class="min-w-0 flex-1">
                    <div class="text-[10px] md:text-xs text-base-content/50 uppercase font-bold tracking-wider">OK</div>
                    <div class="text-xl md:text-2xl font-extrabold mt-0.5 text-success">{{ $summary['ok'] }}</div>
                </div>
            </div>
        </div>

        <div class="rounded-2xl border p-3 md:p-4 kpi-accent kpi-accent-ng {{ $summary['ng'] > 0 ? 'border-error/40 bg-error/5' : 'border-base-300 bg-base-100' }}">
            <div class="flex items-center gap-2 md:gap-3">
                <div class="grid h-9 w-9 md:h-11 md:w-11 shrink-0 place-items-center rounded-xl bg-error/10 text-error">
                    <x-icon name="o-x-circle" class="w-4 h-4 md:w-5 md:h-5" />
                </div>
                <div class="min-w-0 flex-1">
                    <div class="text-[10px] md:text-xs text-base-content/50 uppercase font-bold tracking-wider">NG</div>
                    <div class="text-xl md:text-2xl font-extrabold mt-0.5 text-error">{{ $summary['ng'] }}</div>
                    <div class="text-[10px] md:text-[11px] {{ $summary['ng'] > 0 ? 'text-error/70 font-medium' : 'text-base-content/40' }}">
                        {{ $summary['ng'] > 0 ? 'Needs countermeasure' : 'No rejects' }}
                    </div>
                </div>
            </div>
        </div>

        <div class="rounded-2xl border border-base-300 bg-base-100 p-3 md:p-4 kpi-accent kpi-accent-rate">
            <div class="flex items-center gap-2 md:gap-3">
                <div class="radial-progress {{ $summary['pass_rate'] >= 80 ? 'text-success' : ($summary['pass_rate'] >= 50 ? 'text-warning' : 'text-error') }} shrink-0"
                     style="--value:{{ $summary['pass_rate'] }}; --size:2.75rem; --thickness: 4px;"
                     role="progressbar"
                     aria-valuenow="{{ $summary['pass_rate'] }}">
                    <span class="text-[10px] md:text-[11px] font-bold text-base-content">{{ $summary['pass_rate'] }}%</span>
                </div>
                <div class="min-w-0 flex-1">
                    <div class="text-[10px] md:text-xs text-base-content/50 uppercase font-bold tracking-wider">Pass Rate</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Navigation table --}}
    <x-card shadow class="border border-base-200">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-4 mb-1 border-b border-base-200">
            <div>
                <h3 class="font-bold text-base-content">Inspection Boards</h3>
                <p class="text-xs text-base-content/50">Click a row to open its inspection board.</p>
            </div>
            <x-input
                placeholder="Search station type or process..."
                wire:model.live.debounce.350ms="search"
                clearable
                icon="o-magnifying-glass"
                class="w-full sm:w-64"
            />
        </div>

        @if ($typeStats->isEmpty())
            <div class="flex flex-col items-center gap-3 py-16 text-center">
                <div class="grid h-14 w-14 place-items-center rounded-full bg-base-200">
                    <x-icon name="o-inbox" class="h-7 w-7 text-base-content/30" />
                </div>
                <div>
                    <p class="font-semibold text-base-content/70">No inspection boards match your search</p>
                    <p class="text-sm text-base-content/45">Try adjusting your search term.</p>
                </div>
                @if ($search !== '')
                    <x-button label="Clear search" wire:click="$set('search', '')" icon="o-x-mark" class="btn-sm mt-1" />
                @endif
            </div>
        @else
            <x-table :headers="$headers" :rows="$typeStats" link="{route_index}">
                @scope('cell_label', $stat)
                    <div class="flex items-center gap-3">
                        <div class="grid h-9 w-9 shrink-0 place-items-center rounded-xl bg-primary/10 text-primary">
                            <x-icon name="{{ $stat['icon'] }}" class="w-4 h-4" />
                        </div>
                        <span class="font-bold text-base-content">{{ $stat['label'] }}</span>
                    </div>
                @endscope

                @scope('cell_total', $stat)
                    <div class="text-right font-mono text-sm">{{ $stat['total'] }}</div>
                @endscope

                @scope('cell_ok', $stat)
                    <div class="text-right font-mono text-sm text-success">{{ $stat['ok'] }}</div>
                @endscope

                @scope('cell_ng', $stat)
                    <div class="text-right font-mono text-sm {{ $stat['ng'] > 0 ? 'text-error font-semibold' : 'text-base-content/60' }}">
                        {{ $stat['ng'] }}
                    </div>
                @endscope

                @scope('cell_pass_rate', $stat)
                    <div class="text-right font-mono text-sm">
                        <span class="{{ $stat['pass_rate'] >= 80 ? 'text-success' : ($stat['pass_rate'] >= 50 ? 'text-warning' : 'text-error') }}">
                            {{ $stat['pass_rate'] }}%
                        </span>
                    </div>
                @endscope

                @scope('actions', $stat)
                    <div class="flex items-center justify-end gap-2">
                        <x-button icon="o-plus" link="{{ $stat['route_create'] }}" class="btn-ghost btn-xs" tooltip="New inspection" />
                        <x-button icon="o-arrow-right" link="{{ $stat['route_index'] }}" class="btn-primary btn-xs" tooltip="View board" />
                    </div>
                @endscope
            </x-table>
        @endif
    </x-card>

    {{-- Recent NG alerts --}}
    @if($recentNg->isNotEmpty())
        <x-card shadow class="border border-error/20">
            <x-slot:title>
                <div class="flex items-center gap-2">
                    <x-icon name="o-exclamation-triangle" class="w-5 h-5 text-error" />
                    <span class="font-bold text-base-content">Recent Rejects (NG)</span>
                    <x-badge value="Today" class="badge-error badge-sm text-white font-semibold" />
                </div>
            </x-slot:title>

            <div class="overflow-x-auto">
                <table class="table table-compact w-full text-xs md:text-sm">
                    <thead>
                        <tr class="bg-base-200/50">
                            <th class="py-2">Time</th>
                            <th class="py-2">Part</th>
                            <th class="py-2 hidden sm:table-cell">Station</th>
                            <th class="py-2">Checker</th>
                            <th class="py-2 hidden md:table-cell">Stage</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($recentNg as $record)
                            <tr wire:key="{{ 'ng-'.$record->id }}" class="hover:bg-error/5">
                                <td class="font-mono py-2">{{ $record->checked_at?->format('H:i') ?? '—' }}</td>
                                <td class="py-2">
                                    <span class="font-semibold">{{ $record->part?->part_number }}</span>
                                    <span class="text-base-content/50 hidden sm:inline"> {{ $record->part?->part_name }}</span>
                                </td>
                                <td class="py-2 hidden sm:table-cell">{{ $record->workStation?->name }}</td>
                                <td class="py-2">{{ $record->checker?->name }}</td>
                                <td class="py-2 hidden md:table-cell">
                                    <x-badge :value="$record->stage?->label()" class="badge-neutral badge-xs font-bold uppercase" />
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-card>
    @endif
</div>
