<?php

namespace App\Exports;

use App\Enums\UserRole;
use App\Models\InspectionRecord;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class InspectionReportExport implements FromQuery, ShouldAutoSize, ShouldQueue, WithChunkReading, WithHeadings, WithMapping, WithStyles, WithTitle
{
    protected array $filters;

    protected ?User $user;

    public function __construct(array $filters, ?User $user = null)
    {
        $this->filters = $filters;
        $this->user = $user;
    }

    public function title(): string
    {
        return 'Inspection Records';
    }

    public function query(): Builder
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
            $query->whereHas('fieldValues', function (Builder $q) use ($judgement) {
                $q->where('auto_judgement', $judgement)
                    ->orWhere(function (Builder $q) use ($judgement) {
                        $q->whereHas('field', fn (Builder $f) => $f->where('field_type', 'enum'))
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

        return $query->orderBy('production_date', 'desc')
            ->orderBy('checked_at', 'desc');
    }

    public function headings(): array
    {
        return [
            'Production Date',
            'Shift',
            'Station Type',
            'Work Station',
            'Part Number',
            'Part Name',
            'Stage',
            'Judgement',
            'Checker',
            'Checked At',
            'Remarks',
        ];
    }

    public function map($record): array
    {
        $judgement = $this->overallJudgement($record);
        $remarks = $record->fieldValues
            ->filter(fn ($fv) => ! empty($fv->remarks))
            ->pluck('remarks')
            ->implode('; ');

        return [
            $record->production_date?->format('Y-m-d'),
            $record->shift?->label() ?? $record->shift,
            $record->workStation?->stationType?->name ?? '—',
            $record->workStation?->name ?? '—',
            $record->part?->part_number ?? '—',
            $record->part?->part_name ?? '—',
            $record->stage?->label() ?? $record->stage,
            strtoupper($judgement ?? '—'),
            $record->checker?->name ?? '—',
            $record->checked_at?->format('Y-m-d H:i'),
            $remarks ?: '—',
        ];
    }

    protected function overallJudgement(InspectionRecord $record): ?string
    {
        $autoJudgements = $record->fieldValues
            ->filter(fn ($fv) => $fv->field?->has_auto_judge)
            ->pluck('auto_judgement')
            ->filter();

        if ($autoJudgements->isNotEmpty()) {
            return $autoJudgements->contains('ng') ? 'ng' : 'ok';
        }

        $enumValues = $record->fieldValues
            ->where('field.field_type', 'enum')
            ->pluck('value');

        if ($enumValues->isNotEmpty()) {
            $lower = $enumValues->map(fn ($v) => strtolower($v));
            if ($lower->contains('ng')) {
                return 'ng';
            }
            if ($lower->contains('repair')) {
                return 'repair';
            }

            return 'ok';
        }

        $booleans = $record->fieldValues
            ->where('field.field_type', 'boolean')
            ->pluck('value');

        if ($booleans->isNotEmpty()) {
            return $booleans->contains('0') ? 'ng' : 'ok';
        }

        return null;
    }

    public function chunkSize(): int
    {
        return 200;
    }

    public function styles(Worksheet $sheet): array
    {
        $highestRow = $sheet->getHighestRow();
        $highestColumn = $sheet->getHighestColumn();

        // Header style
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

        // Row styles
        for ($row = 2; $row <= $highestRow; $row++) {
            $judgement = $sheet->getCell("H{$row}")->getValue();

            // Alternating row colors
            if ($row % 2 === 0) {
                $sheet->getStyle("A{$row}:{$highestColumn}{$row}")->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->setStartColor(new Color('FFF8F9FA'));
            }

            // Highlight NG rows
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

        // Borders for entire data range
        $sheet->getStyle("A1:{$highestColumn}{$highestRow}")->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => 'FFDEE2E6'],
                ],
            ],
        ]);

        // Header row height
        $sheet->getRowDimension(1)->setRowHeight(24);

        return [];
    }
}
