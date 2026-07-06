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
use Livewire\Component;

new
#[Layout('layouts.app')]
#[Title('Dashboard')]
class extends Component
{
    public string $greeting;

    public Shift $shift;

    public string $productionDate;

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

        return collect($this->accessibleTypes())->map(function (StationType $type) use ($statsService) {
            $stats = $statsService->dailyByType($type, $this->productionDate);
            $slug = $type->slug;

            return [
                'type' => $type,
                'label' => $type->name,
                'icon' => $type->icon,
                'description' => $type->description,
                'total' => $stats['total'],
                'ok' => $stats['ok'],
                'ng' => $stats['ng'],
                'pass_rate' => $stats['pass_rate'],
                'route_index' => route("inspections.{$slug}.index"),
                'route_create' => route("inspections.{$slug}.create"),
            ];
        });
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
        <div class="rounded-2xl border border-base-300 bg-base-100 p-3 md:p-4">
            <div class="flex items-center gap-2 md:gap-3">
                <div class="grid h-9 w-9 md:h-11 md:w-11 shrink-0 place-items-center rounded-xl bg-base-content/5 text-base-content">
                    <x-icon name="o-clipboard-document-check" class="w-4 h-4 md:w-5 md:h-5" />
                </div>
                <div class="min-w-0 flex-1">
                    <div class="text-[10px] md:text-xs text-base-content/50 uppercase font-bold tracking-wider">Total Today</div>
                    <div class="text-xl md:text-2xl font-extrabold mt-0.5">{{ $summary['total'] }}</div>
                    <div class="text-[9px] md:text-[10px] text-base-content/40 mt-1">{{ $summary['parts_checked'] }} parts</div>
                </div>
            </div>
        </div>

        <div class="rounded-2xl border border-base-300 bg-base-100 p-3 md:p-4">
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

        <div class="rounded-2xl border p-3 md:p-4 {{ $summary['ng'] > 0 ? 'border-error/40 bg-error/5' : 'border-base-300 bg-base-100' }}">
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

        <div class="rounded-2xl border border-base-300 bg-base-100 p-3 md:p-4">
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

    {{-- Inspection type quick-action cards --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        @foreach($typeStats as $stat)
            <x-card shadow class="border border-base-200">
                <div class="flex items-start gap-4">
                    <div class="grid h-12 w-12 shrink-0 place-items-center rounded-2xl bg-primary/10 text-primary">
                        <x-icon name="{{ $stat['icon'] }}" class="w-6 h-6" />
                    </div>
                    <div class="min-w-0 flex-1">
                        <h3 class="font-bold text-base-content text-lg">{{ $stat['label'] }}</h3>
                        <p class="text-xs text-base-content/50 mt-0.5 line-clamp-2">{{ $stat['description'] }}</p>

                        <div class="flex items-center gap-3 mt-3 text-sm">
                            <span class="font-semibold">{{ $stat['total'] }} inspections</span>
                            @if($stat['total'] > 0)
                                <span class="flex items-center gap-1 text-success">
                                    <x-icon name="o-check-circle" class="w-3.5 h-3.5" /> {{ $stat['ok'] }}
                                </span>
                                @if($stat['ng'] > 0)
                                    <span class="flex items-center gap-1 text-error">
                                        <x-icon name="o-x-circle" class="w-3.5 h-3.5" /> {{ $stat['ng'] }}
                                    </span>
                                @endif
                            @endif
                        </div>

                        <div class="flex items-center gap-2 mt-3">
                            <x-button label="New inspection" link="{{ $stat['route_create'] }}" icon="o-plus" class="btn-primary btn-sm" />
                            <x-button label="View board" link="{{ $stat['route_index'] }}" icon="o-arrow-right" class="btn-ghost btn-sm" />
                        </div>
                    </div>
                </div>
            </x-card>
        @endforeach
    </div>

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
                            <tr class="hover:bg-error/5">
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
