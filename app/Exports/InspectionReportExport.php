<?php

namespace App\Exports;

use App\Models\InspectionRecord;
use App\Models\User;
use App\Services\InspectionJudgementService;
use App\Services\InspectionRecordFilter;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class InspectionReportExport
{
    /** @var array<string, mixed> */
    protected array $filters;

    protected ?User $user;

    /** @param array<string, mixed> $filters */
    public function __construct(array $filters, ?User $user = null)
    {
        $this->filters = $filters;
        $this->user = $user;
    }

    public function generate(string $path): void
    {
        $query = (new InspectionRecordFilter($this->filters, $this->user))->queryOrdered();

        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Inspection Records');

        $sheet->fromArray($this->headings(), null, 'A1');

        $row = 2;
        $query->chunk(200, function ($records) use ($sheet, &$row) {
            foreach ($records as $record) {
                $sheet->fromArray($this->map($record), null, "A{$row}");
                $row++;
            }
        });

        $this->applyStyles($sheet);

        $writer = new Xlsx($spreadsheet);
        $writer->save($path);

        $spreadsheet->disconnectWorksheets();
    }

    /** @return list<string> */
    private function headings(): array
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

    /** @return list<mixed> */
    private function map(InspectionRecord $record): array
    {
        $service = app(InspectionJudgementService::class);
        $judgement = $service->stageOverall($record->fieldValues);

        $remarks = $record->fieldValues
            ->filter(fn ($fv) => ! empty($fv->remarks))
            ->pluck('remarks')
            ->implode('; ');

        return [
            $record->production_date instanceof Carbon ? $record->production_date->format('Y-m-d') : (string) ($record->production_date ?? '—'),
            $record->shift->label(),
            $record->workStation?->stationType?->name,
            $record->workStation?->name,
            $record->part?->part_number,
            $record->part?->part_name,
            $record->stage->label(),
            strtoupper($judgement ?? '—'),
            $record->checker?->name,
            $record->checked_at?->format('Y-m-d H:i'),
            $remarks ?: '—',
        ];
    }

    private function applyStyles(Worksheet $sheet): void
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
    }
}
