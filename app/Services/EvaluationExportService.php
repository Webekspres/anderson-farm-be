<?php

namespace App\Services;

use App\Models\DailyActivityHeader;
use App\Models\ProductionPeriod;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EvaluationExportService
{
    private const DEFAULT_HEADER_FILL_ARGB = 'FFC6EFCE';

    private const INTEGER_FORMAT = '0';

    private const DECIMAL_FORMAT = '0.00';

    private const FCR_FORMAT = '0.0000';

    private const HEADER_ROW = 1;

    private const DATA_START_ROW = 2;

    /**
     * @var list<string>
     */
    private const COLUMN_LETTERS = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M'];

    /**
     * @var list<string>
     */
    private const COLUMN_HEADERS = [
        'Hari / Umur (Day)',
        'Tanggal',
        'Stok Awal Hari Ini (Ekor)',
        'Mati (Ekor)',
        'Afkir / Cull (Ekor)',
        'Total Deplesi Kumulatif (Ekor)',
        'Total Deplesi Kumulatif (%)',
        'Panen Hari Ini (Ekor)',
        'Tonase Panen Hari Ini (Kg)',
        'Pakan Hari Ini (Kg)',
        'Kumulatif Pakan (Kg)',
        'Realita Bobot / BW (Kg)',
        'FCR Berjalan',
    ];

    public function export(ProductionPeriod $period): StreamedResponse
    {
        $headers = $this->loadHeaders($period->id);
        $detailRows = $this->buildDetailRows($period, $headers);

        $filename = sprintf('EVALUATION_%s.xlsx', $period->period_code);

        return response()->streamDownload(function () use ($detailRows) {
            $spreadsheet = new Spreadsheet;
            $this->writeEvaluationSheet($spreadsheet, $detailRows);

            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
            $spreadsheet->disconnectWorksheets();
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    /**
     * @return Collection<int, DailyActivityHeader>
     */
    private function loadHeaders(string $periodId): Collection
    {
        return DailyActivityHeader::query()
            ->where('period_id', $periodId)
            ->with(['harvests'])
            ->orderBy('age_days')
            ->get();
    }

    /**
     * @param  Collection<int, DailyActivityHeader>  $headers
     * @return list<array{
     *     day: int,
     *     tanggal: string,
     *     opening_stock: int,
     *     mortality: int,
     *     cull: int,
     *     cumulative_deplesi_ekor: int,
     *     cumulative_deplesi_pct: float,
     *     harvest_birds: int,
     *     harvest_kg: float,
     *     feed_kg: float,
     *     cumulative_feed_kg: float,
     *     body_weight: float,
     *     running_fcr: float
     * }>
     */
    private function buildDetailRows(ProductionPeriod $period, Collection $headers): array
    {
        if ($headers->isEmpty()) {
            return [];
        }

        $lastDay = $this->resolveLastDay($period);
        if ($lastDay < 1) {
            return [];
        }

        $headersByAge = $headers->keyBy('age_days');
        $initialPopulation = (int) $period->initial_stock;
        $startDate = $period->start_date->copy()->startOfDay();

        $cumulativeDeplesiEkor = 0;
        $cumulativePakanKg = 0.0;
        $cumulativeHarvestKgBeforeToday = 0.0;
        $lastKnownBw = null;
        $openingStock = $initialPopulation;

        $rows = [];

        for ($day = 1; $day <= $lastDay; $day++) {
            /** @var DailyActivityHeader|null $header */
            $header = $headersByAge->get($day);

            $tanggal = $header?->date
                ? $header->date->format('Y-m-d')
                : $startDate->copy()->addDays($day - 1)->format('Y-m-d');

            $mortality = (int) ($header?->mortality_count ?? 0);
            $cull = (int) ($header?->cull_count ?? 0);
            $feedKg = (float) ($header?->feed_consumption_kg ?? 0);

            $harvestBirds = 0;
            $harvestKg = 0.0;
            if ($header) {
                $harvestBirds = (int) $header->harvests->sum('total_birds');
                $harvestKg = (float) $header->harvests->sum('total_weight');
            }

            $cumulativeDeplesiEkor += $mortality + $cull;
            $cumulativeDeplesiPct = $initialPopulation > 0
                ? round(($cumulativeDeplesiEkor / $initialPopulation) * 100, 2)
                : 0.0;

            $cumulativePakanKg += $feedKg;

            if ($header?->average_weight !== null) {
                $lastKnownBw = (float) $header->average_weight;
            }

            $displayBw = $lastKnownBw ?? 0.0;
            $liveBiomass = $openingStock * $displayBw;
            $denominator = $liveBiomass + $cumulativeHarvestKgBeforeToday;
            $runningFcr = $denominator > 0
                ? round($cumulativePakanKg / $denominator, 4)
                : 0.0;

            $rows[] = [
                'day' => $day,
                'tanggal' => $tanggal,
                'opening_stock' => $openingStock,
                'mortality' => $mortality,
                'cull' => $cull,
                'cumulative_deplesi_ekor' => $cumulativeDeplesiEkor,
                'cumulative_deplesi_pct' => $cumulativeDeplesiPct,
                'harvest_birds' => $harvestBirds,
                'harvest_kg' => $harvestKg,
                'feed_kg' => $feedKg,
                'cumulative_feed_kg' => round($cumulativePakanKg, 2),
                'body_weight' => round($displayBw, 2),
                'running_fcr' => $runningFcr,
            ];

            $openingStock = max(0, $openingStock - ($mortality + $cull + $harvestBirds));
            $cumulativeHarvestKgBeforeToday += $harvestKg;
        }

        return $rows;
    }

    private function resolveLastDay(ProductionPeriod $period): int
    {
        $start = $period->start_date->copy()->startOfDay();

        if ($period->status === 'completed' && $period->end_date) {
            $end = $period->end_date->copy()->startOfDay();
        } else {
            $end = Carbon::now()->startOfDay();
        }

        if ($end->lt($start)) {
            return 0;
        }

        return (int) $start->diffInDays($end, absolute: true) + 1;
    }

    /**
     * @param  list<array{
     *     day: int,
     *     tanggal: string,
     *     opening_stock: int,
     *     mortality: int,
     *     cull: int,
     *     cumulative_deplesi_ekor: int,
     *     cumulative_deplesi_pct: float,
     *     harvest_birds: int,
     *     harvest_kg: float,
     *     feed_kg: float,
     *     cumulative_feed_kg: float,
     *     body_weight: float,
     *     running_fcr: float
     * }>  $detailRows
     */
    private function writeEvaluationSheet(Spreadsheet $spreadsheet, array $detailRows): void
    {
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Evaluasi Performa');

        foreach (self::COLUMN_HEADERS as $index => $label) {
            $sheet->setCellValue(self::COLUMN_LETTERS[$index].self::HEADER_ROW, $label);
        }

        $row = self::DATA_START_ROW;
        foreach ($detailRows as $detail) {
            $sheet->setCellValue("A{$row}", $detail['day']);
            $sheet->setCellValue("B{$row}", $detail['tanggal']);
            $sheet->setCellValue("C{$row}", $detail['opening_stock']);
            $sheet->setCellValue("D{$row}", $detail['mortality']);
            $sheet->setCellValue("E{$row}", $detail['cull']);
            $sheet->setCellValue("F{$row}", $detail['cumulative_deplesi_ekor']);
            $sheet->setCellValue("G{$row}", $detail['cumulative_deplesi_pct']);
            $sheet->setCellValue("H{$row}", $detail['harvest_birds']);
            $sheet->setCellValue("I{$row}", $detail['harvest_kg']);
            $sheet->setCellValue("J{$row}", $detail['feed_kg']);
            $sheet->setCellValue("K{$row}", $detail['cumulative_feed_kg']);
            $sheet->setCellValue("L{$row}", $detail['body_weight']);
            $sheet->setCellValue("M{$row}", $detail['running_fcr']);
            $row++;
        }

        $lastDataRow = $row - 1;
        $lastTableRow = $lastDataRow;

        if (count($detailRows) > 0) {
            $grandTotalRow = $row;
            $sheet->setCellValue("C{$grandTotalRow}", 'GRAND TOTAL');
            $sheet->getStyle("C{$grandTotalRow}")->getFont()->setBold(true);
            $sheet->setCellValue("D{$grandTotalRow}", '=SUM(D'.self::DATA_START_ROW.":D{$lastDataRow})");
            $sheet->setCellValue("E{$grandTotalRow}", '=SUM(E'.self::DATA_START_ROW.":E{$lastDataRow})");
            $sheet->setCellValue("H{$grandTotalRow}", '=SUM(H'.self::DATA_START_ROW.":H{$lastDataRow})");
            $sheet->setCellValue("I{$grandTotalRow}", '=SUM(I'.self::DATA_START_ROW.":I{$lastDataRow})");
            $sheet->setCellValue("J{$grandTotalRow}", '=SUM(J'.self::DATA_START_ROW.":J{$lastDataRow})");
            $sheet->getStyle("D{$grandTotalRow}:E{$grandTotalRow}")->getFont()->setBold(true);
            $sheet->getStyle("H{$grandTotalRow}:J{$grandTotalRow}")->getFont()->setBold(true);
            $lastTableRow = $grandTotalRow;
        }

        $tableRange = 'A'.self::HEADER_ROW.':M'.$lastTableRow;
        $this->applyFullTableBorders($sheet, $tableRange);
        $this->applyHeaderStyle($sheet, 'A'.self::HEADER_ROW.':M'.self::HEADER_ROW);

        if (count($detailRows) > 0) {
            $sheet->getStyle('C'.self::DATA_START_ROW.':C'.$lastDataRow)
                ->getNumberFormat()->setFormatCode(self::INTEGER_FORMAT);
            $sheet->getStyle('D'.self::DATA_START_ROW.':F'.$lastTableRow)
                ->getNumberFormat()->setFormatCode(self::INTEGER_FORMAT);
            $sheet->getStyle('G'.self::DATA_START_ROW.':G'.$lastDataRow)
                ->getNumberFormat()->setFormatCode(self::DECIMAL_FORMAT);
            $sheet->getStyle('H'.self::DATA_START_ROW.':H'.$lastTableRow)
                ->getNumberFormat()->setFormatCode(self::INTEGER_FORMAT);
            $sheet->getStyle('I'.self::DATA_START_ROW.':K'.$lastDataRow)
                ->getNumberFormat()->setFormatCode(self::DECIMAL_FORMAT);
            $sheet->getStyle('L'.self::DATA_START_ROW.':L'.$lastDataRow)
                ->getNumberFormat()->setFormatCode(self::DECIMAL_FORMAT);
            $sheet->getStyle('M'.self::DATA_START_ROW.':M'.$lastDataRow)
                ->getNumberFormat()->setFormatCode(self::FCR_FORMAT);
        }

        foreach (self::COLUMN_LETTERS as $column) {
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
