<?php

use App\Jobs\GenerateReport;
use App\Models\Export;
use App\Models\InspectionRecord;
use App\Models\StationType;
use App\Models\WorkStation;
use App\Services\InspectionJudgementService;
use App\Services\InspectionRecordFilter;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Mary\Traits\Toast;

new
#[Layout('layouts.app')]
#[Title('Reports')]
class extends Component {
    use Toast, WithPagination;

    #[Url(history: true)]
    public string $date_from = '';

    #[Url(history: true)]
    public string $date_to = '';

    #[Url(history: true)]
    public string $station_type_id = '';

    #[Url(history: true)]
    public string $work_station_id = '';

    #[Url(history: true)]
    public string $stage = '';

    #[Url(history: true)]
    public string $shift = '';

    #[Url(history: true)]
    public string $search = '';

    #[Url(history: true)]
    public string $judgement = '';

    public bool $showExportConfirm = false;

    public ?int $activeExportId = null;

    public string $exportStatus = '';

    public ?string $exportFileName = null;

    public function mount(): void
    {
        if (empty($this->date_from) && empty($this->date_to)) {
            $this->date_from = now()->format('Y-m-d');
            $this->date_to = now()->format('Y-m-d');
        }
    }

    public function updatedStationTypeId(): void
    {
        $this->work_station_id = '';
        $this->resetPage();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedDateFrom(): void
    {
        $this->resetPage();
    }

    public function updatedDateTo(): void
    {
        $this->resetPage();
    }

    public function updatedStage(): void
    {
        $this->resetPage();
    }

    public function updatedShift(): void
    {
        $this->resetPage();
    }

    public function updatedJudgement(): void
    {
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->date_from = now()->format('Y-m-d');
        $this->date_to = now()->format('Y-m-d');
        $this->station_type_id = '';
        $this->work_station_id = '';
        $this->stage = '';
        $this->shift = '';
        $this->search = '';
        $this->judgement = '';
        $this->resetPage();
    }

    public function filters(): array
    {
        return [
            'date_from' => $this->date_from,
            'date_to' => $this->date_to,
            'station_type_id' => $this->station_type_id,
            'work_station_id' => $this->work_station_id,
            'stage' => $this->stage,
            'shift' => $this->shift,
            'judgement' => $this->judgement,
            'search' => $this->search,
        ];
    }

    public function records(): LengthAwarePaginator
    {
        return (new InspectionRecordFilter($this->filters(), auth()->user()))
            ->queryOrdered()
            ->paginate(25);
    }

    public function openExportConfirm(): void
    {
        if ($this->records()->total() === 0) {
            $this->warning('No records to export.', position: 'toast-bottom');

            return;
        }

        $this->showExportConfirm = true;
    }

    public function export(): void
    {
        $this->showExportConfirm = false;

        $filters = $this->filters();

        $filename = 'inspection-report-'.now()->format('Ymd-His').'.xlsx';
        $path = 'reports/'.$filename;

        $export = Export::create([
            'user_id' => auth()->id(),
            'filename' => $filename,
            'path' => $path,
            'status' => 'queued',
            'filters' => $filters,
        ]);

        GenerateReport::dispatch($export->id, $filters, auth()->user(), $path, $filename);

        $this->activeExportId = $export->id;
        $this->exportStatus = 'queued';
        $this->exportFileName = $filename;
    }

    public function checkExportStatus(): void
    {
        if ($this->activeExportId === null) {
            return;
        }

        $export = Export::find($this->activeExportId);

        if ($export === null) {
            $this->activeExportId = null;

            return;
        }

        $this->exportStatus = $export->status;
        $this->exportFileName = $export->filename;

        if ($export->status === 'completed' || $export->status === 'failed') {
            $this->activeExportId = null;
        }
    }

    public function exportPending(): bool
    {
        return $this->activeExportId !== null
            && $this->exportStatus !== 'completed'
            && $this->exportStatus !== 'failed';
    }

    public function dismissExportProgress(): void
    {
        $this->activeExportId = null;
        $this->exportStatus = '';
        $this->exportFileName = null;
    }

    public function cancelExport(): void
    {
        if ($this->activeExportId === null) {
            return;
        }

        $export = Export::find($this->activeExportId);

        if ($export && in_array($export->status, ['queued', 'processing'], true)) {
            $export->update(['status' => 'failed']);
            $this->success('Export cancelled.', position: 'toast-bottom');
        }

        $this->activeExportId = null;
    }

    public function overallJudgement(InspectionRecord $record): ?string
    {
        return app(InspectionJudgementService::class)->stageOverall($record->fieldValues);
    }

    public function stationTypeOptions(): array
    {
        return StationType::with('process')->orderBy('name')->get()
            ->map(fn (StationType $st) => [
                'id' => $st->id,
                'name' => ($st->process?->name ?? '—').' — '.$st->name,
            ])
            ->all();
    }

    public function workStationOptions(): array
    {
        if (empty($this->station_type_id)) {
            return [];
        }

        return WorkStation::where('station_type_id', $this->station_type_id)
            ->orderBy('name')
            ->get()
            ->map(fn (WorkStation $ws) => ['id' => $ws->id, 'name' => $ws->name])
            ->all();
    }

    public function stageOptions(): array
    {
        return [
            ['id' => 'start', 'name' => 'Start'],
            ['id' => 'middle', 'name' => 'Middle'],
            ['id' => 'end', 'name' => 'End'],
        ];
    }

    public function shiftOptions(): array
    {
        return [
            ['id' => 'day', 'name' => 'Day'],
            ['id' => 'night', 'name' => 'Night'],
        ];
    }

    public function judgementOptions(): array
    {
        return [
            ['id' => 'ok', 'name' => 'OK'],
            ['id' => 'ng', 'name' => 'NG'],
            ['id' => 'repair', 'name' => 'REPAIR'],
        ];
    }

    public function with(): array
    {
        $notifications = auth()->user()->notifications()
            ->where('type', \App\Notifications\ReportReady::class)
            ->latest()
            ->take(5)
            ->get();

        return [
            'records' => $this->records(),
            'exportCount' => $this->records()->total(),
            'notifications' => $notifications,
            'recentExports' => Export::where('user_id', auth()->id())
                ->latest()
                ->take(20)
                ->get(),
            'headers' => [
                ['key' => 'date', 'label' => 'Date', 'class' => 'w-28'],
                ['key' => 'shift', 'label' => 'Shift', 'class' => 'w-16'],
                ['key' => 'station_work', 'label' => 'Station / Work Station', 'class' => 'w-44', 'sortable' => false],
                ['key' => 'part', 'label' => 'Part', 'sortable' => false],
                ['key' => 'stage', 'label' => 'Stage', 'class' => 'w-20'],
                ['key' => 'judgement', 'label' => 'Result', 'class' => 'w-24', 'sortable' => false],
                ['key' => 'checker', 'label' => 'Checker', 'class' => 'w-32'],
                ['key' => 'checked_at', 'label' => 'Checked At', 'class' => 'w-32'],
            ],
        ];
    }
}; ?>

<div>
    <x-header title="Reports" subtitle="Filter and export inspection records." separator progress-indicator />

    {{-- Filters --}}
    <x-card shadow class="mb-6">
        <div class="flex items-center justify-between mb-4 pb-3 border-b border-base-200">
            <div class="flex items-center gap-2">
                <x-icon name="o-funnel" class="w-4 h-4 text-base-content/50" />
                <span class="text-sm font-semibold text-base-content/70">Filters</span>
                @php
                    $activeFilterCount = collect([
                        $date_from, $date_to, $station_type_id, $work_station_id,
                        $stage, $shift, $judgement, $search,
                    ])->filter(fn($v) => !empty($v))->count();
                @endphp
                @if($activeFilterCount > 0)
                    <span class="inline-flex items-center justify-center h-5 w-5 rounded-full bg-primary text-[10px] text-primary-content font-bold">{{ $activeFilterCount }}</span>
                @endif
            </div>
            <x-button label="Reset all" wire:click="resetFilters" icon="o-x-mark" class="btn-ghost btn-xs" />
        </div>
        <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
            <div>
                <x-datetime label="Date from" wire:model.live.debounce.350ms="date_from" icon="o-calendar" type="date" />
            </div>
            <div>
                <x-datetime label="Date to" wire:model.live.debounce.350ms="date_to" icon="o-calendar" type="date" />
            </div>
            <div>
                <x-select label="Station type" wire:model.live.debounce.250ms="station_type_id" placeholder="All station types" :options="$this->stationTypeOptions()" />
            </div>
            <div>
                <x-select label="Work station" wire:model.live.debounce.250ms="work_station_id" placeholder="All work stations" :options="$this->workStationOptions()" />
            </div>
            <div>
                <x-select label="Stage" wire:model.live.debounce.250ms="stage" placeholder="All stages" :options="$this->stageOptions()" />
            </div>
            <div>
                <x-select label="Shift" wire:model.live.debounce.250ms="shift" placeholder="All shifts" :options="$this->shiftOptions()" />
            </div>
            <div>
                <x-select label="Judgement" wire:model.live.debounce.250ms="judgement" placeholder="All results" :options="$this->judgementOptions()" />
            </div>
            <div>
                <x-input label="Search part" wire:model.live.debounce.350ms="search" placeholder="Part number or name..." icon="o-magnifying-glass" clearable />
            </div>
        </div>

        <div class="mt-4 pt-3 border-t border-base-200 flex items-center justify-between">
            <span class="text-sm text-base-content/50">{{ $exportCount }} records found</span>
            <x-button
                label="Export Excel"
                wire:click="openExportConfirm"
                spinner="openExportConfirm"
                icon="o-arrow-down-tray"
                class="btn-primary"
            />
        </div>
    </x-card>

    <x-modal wire:model="showExportConfirm" title="Confirm Export" separator>
        <p class="text-base-content/80">
            Export <span class="font-semibold">{{ $exportCount }}</span> records?
            This may take a moment for large datasets.
        </p>
        <x-slot:actions>
            <x-button label="Cancel" @click="$wire.showExportConfirm = false" />
            <x-button label="Export" icon="o-check" class="btn-primary" wire:click="export" spinner="export" />
        </x-slot:actions>
    </x-modal>

    {{-- Export progress --}}
    @if ($this->exportPending())
        <div wire:poll.500ms="checkExportStatus">
            <x-card shadow class="mb-6 border-l-4 border-l-primary">
                <div class="flex items-center gap-4">
                    <x-icon name="o-arrow-down-tray" class="h-6 w-6 animate-pulse text-primary" />
                    <div class="flex-1">
                        <p class="font-medium">Generating report...</p>
                        <p class="text-sm text-base-content/60">{{ $exportFileName }}</p>
                    </div>
                    <x-button label="Cancel" wire:click="cancelExport" class="btn-ghost btn-sm text-error" spinner="cancelExport" />
                </div>
                <x-progress class="progress-primary h-0.5 mt-3" indeterminate />
            </x-card>
        </div>
    @endif

    @if ($exportStatus === 'completed' && $exportFileName)
        <x-card shadow class="mb-6 border-l-4 border-l-success">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <x-icon name="o-check-circle" class="h-6 w-6 text-success" />
                    <div>
                        <p class="font-medium text-success">Export complete</p>
                        <p class="text-sm text-base-content/60">{{ $exportFileName }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <a href="{{ route('reports.download', basename($exportFileName)) }}" class="btn btn-success btn-sm">
                        <x-icon name="o-arrow-down-tray" class="h-4 w-4" />
                        Download
                    </a>
                    <x-button label="Dismiss" icon="o-x-mark" class="btn-ghost btn-sm"
                        wire:click="dismissExportProgress" />
                </div>
            </div>
        </x-card>
    @endif

    @if ($exportStatus === 'failed')
        <x-card shadow class="mb-6 border-l-4 border-l-error">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <x-icon name="o-exclamation-triangle" class="h-6 w-6 text-error" />
                    <div>
                        <p class="font-medium text-error">Export failed</p>
                        <p class="text-sm text-base-content/60">Please try again or contact support.</p>
                    </div>
                </div>
                <x-button label="Dismiss" icon="o-x-mark" class="btn-ghost btn-sm"
                    wire:click="dismissExportProgress" />
            </div>
        </x-card>
    @endif

    {{-- Recent exports --}}
    @if ($recentExports->isNotEmpty())
        <x-card shadow class="mb-6">
            <x-slot:title>Recent Exports</x-slot:title>
            @foreach ($recentExports as $export)
                @php
                    $isCurrent = $export->id === $activeExportId;
                @endphp
                <div wire:key="{{ 'export-'.$export->id }}" class="flex items-center justify-between py-2 {{ !$loop->last ? 'border-b border-base-200' : '' }} {{ $isCurrent ? 'font-semibold' : '' }}">
                    <div class="flex items-center gap-2 min-w-0">
                        @if ($export->status === 'completed')
                            <x-icon name="o-check-circle" class="h-4 w-4 shrink-0 text-success" />
                        @elseif ($export->status === 'failed')
                            <x-icon name="o-exclamation-triangle" class="h-4 w-4 shrink-0 text-error" />
                        @elseif ($isCurrent)
                            <x-icon name="o-arrow-path" class="h-4 w-4 shrink-0 animate-spin text-primary" />
                        @else
                            <x-icon name="o-clock" class="h-4 w-4 shrink-0 text-base-content/40" />
                        @endif
                        <span class="truncate text-sm">{{ $export->filename }}</span>
                    </div>
                    <div class="flex shrink-0 items-center gap-2">
                        <span class="text-xs text-base-content/40">
                            @if ($export->status === 'completed')
                                {{ $export->sizeForHumans() }}
                            @elseif ($export->status === 'failed')
                                Failed
                            @elseif ($isCurrent)
                                {{ $export->progress }}%
                            @else
                                Queued
                            @endif
                        </span>
                        @if ($export->status === 'completed')
                            <a href="{{ $export->downloadUrl() }}" class="btn btn-sm btn-soft">
                                <x-icon name="o-arrow-down-tray" class="h-4 w-4" />
                            </a>
                        @endif
                    </div>
                </div>
            @endforeach
        </x-card>
    @endif

    {{-- Results table --}}
    <x-card shadow>
        @if ($records->isEmpty())
            <div class="flex flex-col items-center gap-3 py-12 text-base-content/40">
                <x-icon name="o-document-text" class="h-12 w-12" />
                <p class="text-lg font-medium">No records found</p>
                <p class="text-sm">Try adjusting your filters.</p>
            </div>
        @else
            <x-table :headers="$headers" :rows="$records" with-pagination striped class="table-sm">
                @scope('cell_date', $record)
                    <span class="font-mono text-xs">{{ $record->production_date?->format('Y-m-d') ?? '—' }}</span>
                @endscope

                @scope('cell_shift', $record)
                    <x-badge :value="$record->shift?->label()" class="badge-ghost badge-sm" />
                @endscope

                @scope('cell_station_work', $record)
                    <div class="min-w-0">
                        <span class="text-[10px] text-base-content/50 block leading-tight">{{ $record->workStation?->stationType?->name ?? '—' }}</span>
                        <span class="text-xs font-medium">{{ $record->workStation?->name ?? '—' }}</span>
                    </div>
                @endscope

                @scope('cell_part', $record)
                    <div class="min-w-0">
                        <span class="font-mono text-xs font-semibold">{{ $record->part?->part_number ?? '—' }}</span>
                        @if ($record->part?->part_name)
                            <span class="block text-[10px] text-base-content/50 leading-tight truncate max-w-[120px]">{{ $record->part->part_name }}</span>
                        @endif
                    </div>
                @endscope

                @scope('cell_stage', $record)
                    <x-badge :value="$record->stage?->label()" class="badge-outline badge-xs font-semibold" />
                @endscope

                @scope('cell_judgement', $record)
                    @php
                        $result = $this->overallJudgement($record);
                        $badgeClass = match ($result) {
                            'ok' => 'badge-success',
                            'ng' => 'badge-error',
                            'repair' => 'badge-warning',
                            default => 'badge-ghost',
                        };
                    @endphp
                    <x-badge :value="strtoupper($result ?? '—')" :class="$badgeClass . ' badge-xs font-bold'" />
                @endscope

                @scope('cell_checker', $record)
                    <span class="text-xs">{{ $record->checker?->name ?? '—' }}</span>
                @endscope

                @scope('cell_checked_at', $record)
                    <span class="font-mono text-xs">{{ $record->checked_at?->format('Y-m-d H:i') ?? '—' }}</span>
                @endscope
            </x-table>
        @endif
    </x-card>
</div>
