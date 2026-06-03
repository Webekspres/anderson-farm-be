<?php

namespace App\Services;

use App\Models\ProductionPeriod;
use App\Models\Transaction;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BopExportService
{
    private const DEFAULT_HEADER_FILL_ARGB = 'FFC6EFCE';

    private const TABLE_SPACER_ROWS = 1;

    private const CURRENCY_FORMAT = '#,##0';

    /**
     * Stream laporan BOP (Biaya Operasional Kandang) per periode.
     */
    public function export(ProductionPeriod $period): StreamedResponse
    {
        $transactions = $this->loadExpenseTransactions($period->id);
        $detailRows = $this->buildDetailRows($transactions);
        $summaryRows = $this->buildSummaryRows($detailRows);
        $grandTotal = array_sum(array_column($detailRows, 'amount'));

        $filename = sprintf('BOP_DETAILS_%s.xlsx', $period->period_code);

        return response()->streamDownload(function () use ($period, $detailRows, $summaryRows, $grandTotal) {
            $spreadsheet = new Spreadsheet;

            $this->writeDetailBopSheet($spreadsheet, $period, $detailRows, $grandTotal);
            $this->writeSummaryBopSheet($spreadsheet, $summaryRows, $grandTotal);

            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
            $spreadsheet->disconnectWorksheets();
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    /**
     * @return Collection<int, Transaction>
     */
    private function loadExpenseTransactions(string $periodId): Collection
    {
        return Transaction::query()
            ->where('period_id', $periodId)
            ->whereHas('category', fn ($query) => $query->where('type', 'EXPENSE'))
            ->with(['category', 'coop', 'period.floor'])
            ->orderBy('date')
            ->get();
    }

    /**
     * @param  Collection<int, Transaction>  $transactions
     * @return list<array{
     *     no: int,
     *     tanggal: string,
     *     kategori: string,
     *     deskripsi: string,
     *     expense_scope: string,
     *     lokasi: string,
     *     amount: float,
     *     category_id: string
     * }>
     */
    private function buildDetailRows(Collection $transactions): array
    {
        $rows = [];
        $no = 1;

        foreach ($transactions as $transaction) {
            $rows[] = [
                'no' => $no++,
                'tanggal' => $transaction->date?->format('Y-m-d') ?? '',
                'kategori' => $transaction->category?->name ?? 'N/A',
                'deskripsi' => $transaction->description ?? '',
                'expense_scope' => $transaction->expense_scope,
                'lokasi' => $this->resolveLocation($transaction),
                'amount' => (float) $transaction->amount,
                'category_id' => $transaction->category_id,
            ];
        }

        return $rows;
    }

    /**
     * @param  list<array{category_id: string, kategori: string, amount: float}>  $detailRows
     * @return list<array{kategori: string, total_biaya: float, persentase: float}>
     */
    private function buildSummaryRows(array $detailRows): array
    {
        $grandTotal = array_sum(array_column($detailRows, 'amount'));

        return collect($detailRows)
            ->groupBy('category_id')
            ->map(function (Collection $items) use ($grandTotal) {
                $first = $items->first();
                $totalBiaya = $items->sum('amount');

                return [
                    'kategori' => $first['kategori'],
                    'total_biaya' => (float) $totalBiaya,
                    'persentase' => $grandTotal > 0 ? round(($totalBiaya / $grandTotal) * 100, 2) : 0.0,
                ];
            })
            ->values()
            ->sortBy('kategori')
            ->all();
    }

    private function resolveLocation(Transaction $transaction): string
    {
        if ($transaction->expense_scope === 'COOP_SHARED') {
            return 'Gedung Utama';
        }

        return $transaction->period?->floor?->name ?? 'N/A';
    }

    /**
     * @param  list<array{no: int, tanggal: string, kategori: string, deskripsi: string, expense_scope: string, lokasi: string, amount: float}>  $detailRows
     */
    private function writeDetailBopSheet(
        Spreadsheet $spreadsheet,
        ProductionPeriod $period,
        array $detailRows,
        float $grandTotal,
    ): void {
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Detail BOP');

        $sheet->setCellValue('A1', 'Laporan BOP — Detail');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

        $metaLastRow = $this->writePeriodMetadata($sheet, $period);
        $this->applyOutlineBorder($sheet, "A1:B{$metaLastRow}");

        $headerRow = $metaLastRow + self::TABLE_SPACER_ROWS + 1;
        $columns = ['A', 'B', 'C', 'D', 'E', 'F', 'G'];
        $headers = ['No', 'Tanggal', 'Kategori', 'Deskripsi', 'Expense Scope', 'Lokasi/Lantai', 'Amount (Rp)'];

        foreach ($headers as $index => $label) {
            $sheet->setCellValue($columns[$index].$headerRow, $label);
        }

        $row = $headerRow + 1;
        foreach ($detailRows as $detail) {
            $sheet->setCellValue("A{$row}", $detail['no']);
            $sheet->setCellValue("B{$row}", $detail['tanggal']);
            $sheet->setCellValue("C{$row}", $detail['kategori']);
            $sheet->setCellValue("D{$row}", $detail['deskripsi']);
            $sheet->setCellValue("E{$row}", $detail['expense_scope']);
            $sheet->setCellValue("F{$row}", $detail['lokasi']);
            $sheet->setCellValue("G{$row}", $detail['amount']);
            $row++;
        }

        $totalRow = $row;
        $sheet->setCellValue("F{$totalRow}", 'TOTAL');
        $sheet->getStyle("F{$totalRow}")->getFont()->setBold(true);
        $sheet->setCellValue("G{$totalRow}", $grandTotal);

        $lastTableRow = $totalRow;
        $tableRange = "A{$headerRow}:G{$lastTableRow}";
        $this->applyFullTableBorders($sheet, $tableRange);
        $this->applyHeaderStyle($sheet, "A{$headerRow}:G{$headerRow}");
        $this->applyCurrencyFormat($sheet, "G{$headerRow}:G{$lastTableRow}");

        foreach ($columns as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }
    }

    /**
     * @param  list<array{kategori: string, total_biaya: float, persentase: float}>  $summaryRows
     */
    private function writeSummaryBopSheet(Spreadsheet $spreadsheet, array $summaryRows, float $grandTotal): void
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Summary BOP');

        $sheet->setCellValue('A1', 'Laporan BOP — Ringkasan');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $this->applyOutlineBorder($sheet, 'A1:C1');

        $headerRow = 3;
        $columns = ['A', 'B', 'C'];
        $headers = ['Kategori', 'Total Biaya (Rp)', 'Persentase (%)'];

        foreach ($headers as $index => $label) {
            $sheet->setCellValue($columns[$index].$headerRow, $label);
        }

        $row = $headerRow + 1;
        foreach ($summaryRows as $summary) {
            $sheet->setCellValue("A{$row}", $summary['kategori']);
            $sheet->setCellValue("B{$row}", $summary['total_biaya']);
            $sheet->setCellValue("C{$row}", $summary['persentase']);
            $row++;
        }

        $totalRow = $row;
        $sheet->setCellValue("A{$totalRow}", 'TOTAL');
        $sheet->getStyle("A{$totalRow}")->getFont()->setBold(true);
        $sheet->setCellValue("B{$totalRow}", $grandTotal);
        $sheet->setCellValue("C{$totalRow}", $grandTotal > 0 ? 100 : 0);

        $lastTableRow = $totalRow;
        $tableRange = "A{$headerRow}:C{$lastTableRow}";
        $this->applyFullTableBorders($sheet, $tableRange);
        $this->applyHeaderStyle($sheet, "A{$headerRow}:C{$headerRow}");
        $this->applyCurrencyFormat($sheet, "B{$headerRow}:B{$lastTableRow}");
        $sheet->getStyle("C{$headerRow}:C{$lastTableRow}")
            ->getNumberFormat()
            ->setFormatCode(NumberFormat::FORMAT_NUMBER_00);

        foreach ($columns as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }
    }

    /**
     * Metadata periode (baris terakhir = End Date).
     */
    private function writePeriodMetadata(Worksheet $sheet, ProductionPeriod $period): int
    {
        $row = 2;

        $sheet->setCellValue("A{$row}", 'Period Code:');
        $sheet->setCellValue("B{$row}", $period->period_code);
        $row++;

        $sheet->setCellValue("A{$row}", 'Start Date:');
        $sheet->setCellValue("B{$row}", $period->start_date?->format('Y-m-d') ?? '');
        $row++;

        $sheet->setCellValue("A{$row}", 'End Date:');
        $sheet->setCellValue("B{$row}", $period->end_date?->format('Y-m-d') ?? '-');
        $row++;

        return $row - 1;
    }

    private function applyOutlineBorder(Worksheet $sheet, string $cellRange): void
    {
        $sheet->getStyle($cellRange)->applyFromArray([
            'borders' => [
                'outline' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => 'FF000000'],
                ],
            ],
        ]);
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
