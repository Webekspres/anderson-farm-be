<?php

namespace App\Services;

use App\Models\HarvestEntry;
use App\Models\ProductionPeriod;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class HarvestExportService
{
    private const DEFAULT_HEADER_FILL_ARGB = 'FFC6EFCE';

    private const CURRENCY_FORMAT = '#,##0';

    private const INTEGER_FORMAT = '0';

    private const DECIMAL_FORMAT = '0.00';

    private const HEADER_ROW = 1;

    private const DATA_START_ROW = 2;

    /**
     * @var list<string>
     */
    private const COLUMN_LETTERS = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J'];

    /**
     * @var list<string>
     */
    private const COLUMN_HEADERS = [
        'Tanggal Panen',
        'Umur Ayam (Hari)',
        'Rit Ke-',
        'Nama Pembeli / Bakul',
        'No. Surat Jalan (DO)',
        'Jumlah (Ekor)',
        'Total Berat (Kg)',
        'Rata-rata Bobot (Kg)',
        'Harga per Kg (Rp)',
        'Total Nilai Omzet (Rp)',
    ];

    public function export(ProductionPeriod $period): StreamedResponse
    {
        $entries = $this->loadHarvestEntries($period->id);
        $detailRows = $this->buildDetailRows($entries);

        $filename = sprintf('HARVEST_%s.xlsx', $period->period_code);

        return response()->streamDownload(function () use ($detailRows) {
            $spreadsheet = new Spreadsheet;
            $this->writeHarvestSheet($spreadsheet, $detailRows);

            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
            $spreadsheet->disconnectWorksheets();
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    /**
     * @return Collection<int, HarvestEntry>
     */
    private function loadHarvestEntries(string $periodId): Collection
    {
        return HarvestEntry::query()
            ->whereHas('header', fn ($query) => $query->where('period_id', $periodId))
            ->with(['header.period.floor.coop'])
            ->join('daily_activity_headers', 'harvest_entries.header_id', '=', 'daily_activity_headers.id')
            ->orderBy('daily_activity_headers.date')
            ->orderBy('harvest_entries.rit_number')
            ->select('harvest_entries.*')
            ->get();
    }

    /**
     * @param  Collection<int, HarvestEntry>  $entries
     * @return list<array{
     *     tanggal: string,
     *     umur_hari: int|null,
     *     rit_number: int,
     *     buyer_name: string|null,
     *     delivery_order_no: string|null,
     *     total_birds: int,
     *     total_weight: float,
     *     average_weight: float,
     *     price_per_kg: float,
     *     total_revenue: float
     * }>
     */
    private function buildDetailRows(Collection $entries): array
    {
        $rows = [];

        foreach ($entries as $entry) {
            $header = $entry->header;
            $totalBirds = (int) $entry->total_birds;
            $totalWeight = (float) $entry->total_weight;

            $rows[] = [
                'tanggal' => $header?->date?->format('Y-m-d') ?? '',
                'umur_hari' => $header?->age_days,
                'rit_number' => (int) $entry->rit_number,
                'buyer_name' => $entry->buyer_name,
                'delivery_order_no' => $entry->delivery_order_no,
                'total_birds' => $totalBirds,
                'total_weight' => $totalWeight,
                'average_weight' => $totalBirds > 0 ? round($totalWeight / $totalBirds, 2) : 0.0,
                'price_per_kg' => (float) $entry->price_per_kg,
                'total_revenue' => (float) $entry->total_revenue,
            ];
        }

        return $rows;
    }

    /**
     * @param  list<array{
     *     tanggal: string,
     *     umur_hari: int|null,
     *     rit_number: int,
     *     buyer_name: string|null,
     *     delivery_order_no: string|null,
     *     total_birds: int,
     *     total_weight: float,
     *     average_weight: float,
     *     price_per_kg: float,
     *     total_revenue: float
     * }>  $detailRows
     */
    private function writeHarvestSheet(Spreadsheet $spreadsheet, array $detailRows): void
    {
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Rekap Panen');

        foreach (self::COLUMN_HEADERS as $index => $label) {
            $sheet->setCellValue(self::COLUMN_LETTERS[$index].self::HEADER_ROW, $label);
        }

        $row = self::DATA_START_ROW;
        foreach ($detailRows as $detail) {
            $sheet->setCellValue("A{$row}", $detail['tanggal']);
            $sheet->setCellValue("B{$row}", $detail['umur_hari']);
            $sheet->setCellValue("C{$row}", $detail['rit_number']);
            $sheet->setCellValue("D{$row}", $detail['buyer_name'] ?? '');
            $sheet->setCellValue("E{$row}", $detail['delivery_order_no'] ?? '');
            $sheet->setCellValue("F{$row}", $detail['total_birds']);
            $sheet->setCellValue("G{$row}", $detail['total_weight']);
            $sheet->setCellValue("H{$row}", $detail['average_weight']);
            $sheet->setCellValue("I{$row}", $detail['price_per_kg']);
            $sheet->setCellValue("J{$row}", $detail['total_revenue']);
            $row++;
        }

        $lastDataRow = $row - 1;
        $lastTableRow = $lastDataRow;

        if (count($detailRows) > 0) {
            $grandTotalRow = $row;
            $sheet->setCellValue("E{$grandTotalRow}", 'GRAND TOTAL');
            $sheet->getStyle("E{$grandTotalRow}")->getFont()->setBold(true);
            $sheet->setCellValue("F{$grandTotalRow}", '=SUM(F'.self::DATA_START_ROW.":F{$lastDataRow})");
            $sheet->setCellValue("G{$grandTotalRow}", '=SUM(G'.self::DATA_START_ROW.":G{$lastDataRow})");
            $sheet->setCellValue("J{$grandTotalRow}", '=SUM(J'.self::DATA_START_ROW.":J{$lastDataRow})");
            $sheet->getStyle("F{$grandTotalRow}:G{$grandTotalRow}")->getFont()->setBold(true);
            $sheet->getStyle("J{$grandTotalRow}")->getFont()->setBold(true);
            $lastTableRow = $grandTotalRow;
        }

        $tableRange = 'A'.self::HEADER_ROW.':J'.$lastTableRow;
        $this->applyFullTableBorders($sheet, $tableRange);
        $this->applyHeaderStyle($sheet, 'A'.self::HEADER_ROW.':J'.self::HEADER_ROW);

        if (count($detailRows) > 0) {
            $dataRange = 'F'.self::DATA_START_ROW.':F'.$lastTableRow;
            $sheet->getStyle($dataRange)->getNumberFormat()->setFormatCode(self::INTEGER_FORMAT);

            $weightRange = 'G'.self::DATA_START_ROW.':G'.$lastTableRow;
            $sheet->getStyle($weightRange)->getNumberFormat()->setFormatCode(self::DECIMAL_FORMAT);

            $averageRange = 'H'.self::DATA_START_ROW.':H'.$lastDataRow;
            $sheet->getStyle($averageRange)->getNumberFormat()->setFormatCode(self::DECIMAL_FORMAT);

            $this->applyCurrencyFormat($sheet, 'I'.self::DATA_START_ROW.':J'.$lastTableRow);
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
