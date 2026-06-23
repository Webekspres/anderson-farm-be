<?php

namespace App\Services;

use App\Models\CoopUserAssignment;
use App\Models\ProductionPeriod;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SalaryTemplateExportService
{
    private const DEFAULT_HEADER_FILL_ARGB = 'FFC6EFCE';

    private const CURRENCY_FORMAT = '#,##0';

    /**
     * @var list<string>
     */
    private const TABLE_HEADERS = [
        'Period Code',
        'Employee Username',
        'Coop ID',
        'Floor ID',
        'Coop Name',
        'Floor Name',
        'Employee Name',
        'Total Gaji',
    ];

    public function export(): StreamedResponse
    {
        $rows = $this->buildTemplateRows();
        $filename = sprintf('TEMPLATE_GAJI_ABK_%s.xlsx', now()->format('Y-m-d'));

        return response()->streamDownload(function () use ($rows) {
            $spreadsheet = new Spreadsheet;
            $this->writeTemplateSheet($spreadsheet, $rows);

            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
            $spreadsheet->disconnectWorksheets();
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    /**
     * @return list<array{
     *     period_code: string,
     *     employee_username: string,
     *     coop_id: string,
     *     floor_id: string,
     *     coop_name: string,
     *     floor_name: string,
     *     employee_name: string,
     *     total_gaji: float
     * }>
     */
    private function buildTemplateRows(): array
    {
        $activePeriods = ProductionPeriod::query()
            ->where('status', 'active')
            ->with([
                'floor.coop',
                'salaries',
            ])
            ->orderBy('period_code')
            ->get();

        if ($activePeriods->isEmpty()) {
            return [];
        }

        $coopIds = $activePeriods
            ->map(fn (ProductionPeriod $period) => $period->floor?->coop_id)
            ->filter()
            ->unique()
            ->values();

        $abkByCoop = $this->loadAbkAssignmentsByCoop($coopIds);

        $rows = [];

        foreach ($activePeriods as $period) {
            $floor = $period->floor;
            $coop = $floor?->coop;

            if (! $floor || ! $coop) {
                continue;
            }

            $assignments = $abkByCoop->get($coop->id, collect());

            if ($assignments->isEmpty()) {
                continue;
            }

            $salaryByEmployee = $period->salaries->keyBy('employee_id');

            foreach ($assignments as $assignment) {
                $user = $assignment->user;

                if (! $user) {
                    continue;
                }

                $salary = $salaryByEmployee->get($user->id);

                $rows[] = [
                    'period_code' => $period->period_code,
                    'employee_username' => $user->username,
                    'coop_id' => $coop->id,
                    'floor_id' => $floor->id,
                    'coop_name' => $coop->name,
                    'floor_name' => $floor->name,
                    'employee_name' => $user->name,
                    'total_gaji' => (float) ($salary?->salary_amount ?? 0),
                ];
            }
        }

        usort($rows, function (array $a, array $b): int {
            $periodCompare = strcmp($a['period_code'], $b['period_code']);

            if ($periodCompare !== 0) {
                return $periodCompare;
            }

            return strcmp($a['employee_username'], $b['employee_username']);
        });

        return $rows;
    }

    /**
     * @param  Collection<int, string>  $coopIds
     * @return Collection<string, Collection<int, CoopUserAssignment>>
     */
    private function loadAbkAssignmentsByCoop(Collection $coopIds): Collection
    {
        if ($coopIds->isEmpty()) {
            return collect();
        }

        return CoopUserAssignment::query()
            ->whereIn('coop_id', $coopIds)
            ->whereNull('deleted_at')
            ->whereHas('user', fn ($query) => $query->where('role', 'abk'))
            ->with(['user:id,username,name,role'])
            ->get()
            ->groupBy('coop_id');
    }

    /**
     * @param  list<array{
     *     period_code: string,
     *     employee_username: string,
     *     coop_id: string,
     *     floor_id: string,
     *     coop_name: string,
     *     floor_name: string,
     *     employee_name: string,
     *     total_gaji: float
     * }>  $rows
     */
    private function writeTemplateSheet(Spreadsheet $spreadsheet, array $rows): void
    {
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Template Gaji');

        $headerRow = 1;
        $columns = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'];

        foreach (self::TABLE_HEADERS as $index => $label) {
            $sheet->setCellValue($columns[$index].$headerRow, $label);
        }

        $row = $headerRow + 1;

        foreach ($rows as $data) {
            $sheet->setCellValue("A{$row}", $data['period_code']);
            $sheet->setCellValue("B{$row}", $data['employee_username']);
            $sheet->setCellValue("C{$row}", $data['coop_id']);
            $sheet->setCellValue("D{$row}", $data['floor_id']);
            $sheet->setCellValue("E{$row}", $data['coop_name']);
            $sheet->setCellValue("F{$row}", $data['floor_name']);
            $sheet->setCellValue("G{$row}", $data['employee_name']);
            $sheet->setCellValue("H{$row}", $data['total_gaji']);
            $row++;
        }

        $lastTableRow = max($headerRow, $row - 1);
        $tableRange = "A{$headerRow}:H{$lastTableRow}";

        $this->applyFullTableBorders($sheet, $tableRange);
        $this->applyHeaderStyle($sheet, "A{$headerRow}:H{$headerRow}");

        if ($row > $headerRow + 1) {
            $this->applyCurrencyFormat($sheet, 'H'.($headerRow + 1).':H'.($row - 1));
        }

        foreach ($columns as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }
    }

    private function applyFullTableBorders(Worksheet $sheet, string $cellRange): void
    {
        $sheet->getStyle($cellRange)->applyFromArray([
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => 'FF000000'],
                ],
            ],
        ]);
    }

    private function applyHeaderStyle(Worksheet $sheet, string $cellRange): void
    {
        $sheet->getStyle($cellRange)->applyFromArray([
            'font' => [
                'bold' => true,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => $this->headerFillArgb()],
            ],
        ]);
    }

    private function applyCurrencyFormat(Worksheet $sheet, string $cellRange): void
    {
        $sheet->getStyle($cellRange)->getNumberFormat()->setFormatCode(self::CURRENCY_FORMAT);
    }

    private function headerFillArgb(): string
    {
        $color = strtoupper(ltrim((string) config('export.header_table_color', self::DEFAULT_HEADER_FILL_ARGB), '#'));

        if (strlen($color) === 6) {
            return 'FF'.$color;
        }

        if (strlen($color) === 8) {
            return $color;
        }

        return self::DEFAULT_HEADER_FILL_ARGB;
    }
}
