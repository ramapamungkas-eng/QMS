<?php

namespace App\Jobs;

use App\Exports\InspectionReportExport;
use App\Models\Export;
use App\Models\User;
use App\Notifications\ReportReady;
use App\Services\InspectionRecordFilter;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Maatwebsite\Excel\Facades\Excel;

class GenerateReport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @param  array<string, mixed>  $filters
     */
    public function __construct(
        protected int $exportId,
        protected array $filters,
        protected User $user,
        protected string $path,
        protected string $fileName,
    ) {}

    public function handle(): void
    {
        $export = Export::findOrFail($this->exportId);

        $export->update(['status' => 'processing', 'progress' => 5]);

        $totalRows = (new InspectionRecordFilter($this->filters, $this->user))->query()->count();

        $export->update([
            'total_rows' => $totalRows,
            'progress' => 10,
        ]);

        Excel::store(
            new InspectionReportExport($this->filters, $this->user),
            $this->path,
            'public',
        );

        $export->update([
            'status' => 'completed',
            'progress' => 100,
            'completed_at' => now(),
        ]);

        $this->user->notify(new ReportReady($this->path, $this->fileName));
    }
}
