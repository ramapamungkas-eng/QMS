<?php

namespace App\Jobs;

use App\Enums\UserRole;
use App\Exports\InspectionReportExport;
use App\Models\Export;
use App\Models\InspectionRecord;
use App\Models\User;
use App\Notifications\ReportReady;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class GenerateReport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

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

        $export->update(['status' => 'processing', 'progress' => 2]);

        $query = $this->buildQuery();

        $totalRows = (clone $query)->count();

        $export->update([
            'total_rows' => $totalRows,
            'progress' => 5,
        ]);

        $reportExport = new InspectionReportExport($this->filters);

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Inspection Records');

        $headers = $reportExport->headings();
        foreach ($headers as $col => $header) {
            $sheet->setCellValue([$col + 1, 1], $header);
        }

        $rowNum = 2;
        $processed = 0;

        $dataQuery = $query->orderBy('production_date', 'desc')
            ->orderBy('checked_at', 'desc');

        foreach ($dataQuery->cursor() as $record) {
            $rowData = $reportExport->map($record);
            foreach ($rowData as $col => $value) {
                $sheet->setCellValue([$col + 1, $rowNum], $value);
            }
            $rowNum++;
            $processed++;

            $chunk = 200;
            if ($processed % $chunk === 0 && $totalRows > 0) {
                $progress = min(95, (int) round(($processed / $totalRows) * 100));
                $export->update(['progress' => $progress]);
            }
        }

        $lastRow = $rowNum - 1;
        $lastCol = count($headers);

        $this->applyStyles($sheet, $lastRow, $lastCol);

        $writer = new Xlsx($spreadsheet);
        $fullPath = Storage::disk('public')->path($this->path);
        $writer->save($fullPath);
        $spreadsheet->disconnectWorksheets();

        $export->update([
            'status' => 'completed',
            'progress' => 100,
            'completed_at' => now(),
        ]);

        $this->user->notify(new ReportReady($this->path, $this->fileName));
    }

    private function buildQuery(): Builder
    {
        $query = InspectionRecord::query()
            ->with([
                'part',
                'workStation.stationType',
                'checker',
                'fieldValues.field',
            ]);

        if (! empty($this->filters['date_from'])) {
            $query->whereDate('production_date', '>=', Carbon::parse($this->filters['date_from']));
        }

        if (! empty($this->filters['date_to'])) {
            $query->whereDate('production_date', '<=', Carbon::parse($this->filters['date_to']));
        }

        if (! empty($this->filters['station_type_id'])) {
            $query->whereHas('workStation', fn (Builder $q) => $q->where('station_type_id', $this->filters['station_type_id']));
        }

        if (! empty($this->filters['work_station_id'])) {
            $query->where('work_station_id', $this->filters['work_station_id']);
        }

        if (! empty($this->filters['stage'])) {
            $query->where('stage', $this->filters['stage']);
        }

        if (! empty($this->filters['shift'])) {
            $query->where('shift', $this->filters['shift']);
        }

        if (! empty($this->filters['judgement'])) {
            $judgement = $this->filters['judgement'];
            $query->where(function (Builder $q) use ($judgement) {
                $q->whereHas('fieldValues', fn (Builder $fv) => $fv->where('auto_judgement', $judgement))
                    ->orWhereHas('fieldValues', function (Builder $fv) use ($judgement) {
                        $fv->whereHas('field', fn (Builder $f) => $f->where('field_type', 'enum'))
                            ->where('value', $judgement);
                    });
            });
        }

        if (! empty($this->filters['search'])) {
            $search = $this->filters['search'];
            $query->whereHas('part', fn (Builder $q) => $q
                ->where('part_number', 'like', "%{$search}%")
                ->orWhere('part_name', 'like', "%{$search}%"));
        }

        if ($this->user && $this->user->role === UserRole::Checker) {
            $query->whereHas('workStation', fn (Builder $q) => $q->where('process_id', $this->user->process_id));
        }

        return $query;
    }

    private function applyStyles(Worksheet $sheet, int $lastRow, int $lastCol): void
    {
        $highestColumn = $sheet->getHighestColumn();

        $sheet->getStyle("A1:{$highestColumn}1")->applyFromArray([
            'font' => [
                'bold' => true,
                'color' => ['argb' => Color::COLOR_WHITE],
                'size' => 11,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => 'FF1E3A5F'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        for ($row = 2; $row <= $lastRow; $row++) {
            if ($row % 2 === 0) {
                $sheet->getStyle("A{$row}:{$highestColumn}{$row}")->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->setStartColor(new Color('FFF8F9FA'));
            }

            $judgement = $sheet->getCell("H{$row}")->getValue();

            if (strtoupper($judgement) === 'NG') {
                $sheet->getStyle("A{$row}:{$highestColumn}{$row}")->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['argb' => 'FFFDE8E8'],
                    ],
                    'font' => [
                        'color' => ['argb' => 'FFDC3545'],
                    ],
                ]);
            }

            if (strtoupper($judgement) === 'REPAIR') {
                $sheet->getStyle("A{$row}:{$highestColumn}{$row}")->applyFromArray([
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['argb' => 'FFFFF3CD'],
                    ],
                ]);
            }
        }

        $sheet->getStyle("A1:{$highestColumn}{$lastRow}")->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => 'FFDEE2E6'],
                ],
            ],
        ]);

        $sheet->getRowDimension(1)->setRowHeight(24);
    }
}
