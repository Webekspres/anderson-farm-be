<?php

namespace App\Services;

use App\Models\DailyActivityHeader;
use App\Models\ProductionPeriod;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OvkExportService
{
    /** Warna hijau header default jika HEADER_TABLE_COLOR tidak di-set di .env. */
    private const DEFAULT_HEADER_FILL_ARGB = 'FFC6EFCE';

    /** Baris kosong antara tanggal selesai/mulai dan header tabel Detail. */
    private const DETAIL_TABLE_SPACER_ROWS = 1;

    /**
     * Stream laporan audit pemakaian OVK per periode (Excel: Detail & Summary).
     */
    public function export(ProductionPeriod $period): StreamedResponse
    {
        $headers = $this->loadHeadersForPeriod($period->id);
        $detailRows = $this->buildDetailRows($headers);
        $summaryRows = $this->buildSummaryRows($detailRows);

        $filename = sprintf('OVK_USAGES_%s.xlsx', $period->period_code);

        return response()->streamDownload(function () use ($period, $detailRows, $summaryRows) {
            $spreadsheet = new Spreadsheet;

            $this->writeDetailSheet($spreadsheet, $period, $detailRows);
            $this->writeSummarySheet($spreadsheet, $summaryRows);

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
    private function loadHeadersForPeriod(string $periodId): Collection
    {
        return DailyActivityHeader::query()
            ->where('period_id', $periodId)
            ->with(['ovkUsages.ovkItem'])
            ->orderBy('date')
            ->get();
    }

    /**
     * @param  Collection<int, DailyActivityHeader>  $headers
     * @return list<array{
     *     tanggal: string,
     *     umur_hari: int|null,
     *     nama_barang: string,
     *     tipe: string,
     *     quantity: float,
     *     unit: string,
     *     catatan: string,
     *     ovk_item_id: string
     * }>
     */
    private function buildDetailRows(Collection $headers): array
    {
        $rows = [];

        foreach ($headers as $header) {
            foreach ($header->ovkUsages as $usage) {
                $item = $usage->ovkItem;

                $rows[] = [
                    'tanggal' => $header->date?->format('Y-m-d') ?? '',
                    'umur_hari' => $header->age_days,
                    'nama_barang' => $item?->name ?? 'N/A',
                    'tipe' => $item?->type ?? 'N/A',
                    'quantity' => (float) $usage->quantity,
                    'unit' => $item?->unit ?? 'N/A',
                    'catatan' => $usage->notes ?? '',
                    'ovk_item_id' => $usage->ovk_item_id,
                ];
            }
        }

        return $rows;
    }

    /**
     * @param  list<array{ovk_item_id: string, nama_barang: string, tipe: string, unit: string, quantity: float}>  $detailRows
     * @return list<array{nama_barang: string, tipe: string, unit: string, total_qty: float, jumlah_entri: int}>
     */
    private function buildSummaryRows(array $detailRows): array
    {
        $grouped = collect($detailRows)
            ->groupBy('ovk_item_id')
            ->map(function (Collection $items) {
                $first = $items->first();

                return [
                    'nama_barang' => $first['nama_barang'],
                    'tipe' => $first['tipe'],
                    'unit' => $first['unit'],
                    'total_qty' => $items->sum('quantity'),
                    'jumlah_entri' => $items->count(),
                ];
            })
            ->values()
            ->sortBy('nama_barang')
            ->all();

        return $grouped;
    }

    /**
     * @param  list<array{tanggal: string, umur_hari: int|null, nama_barang: string, tipe: string, quantity: float, unit: string, catatan: string}>  $detailRows
     */
    private function writeDetailSheet(Spreadsheet $spreadsheet, ProductionPeriod $period, array $detailRows): void
    {
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Detail');

        $sheet->setCellValue('A1', 'Laporan Pemakaian OVK — Detail');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

        $lastDateRow = $this->writePeriodMetadata($sheet, $period);
        $this->applyOutlineBorder($sheet, "A1:B{$lastDateRow}");

        // Satu baris kosong setelah tanggal selesai, atau setelah tanggal mulai jika tidak ada tanggal selesai.
        $headerRow = $lastDateRow + self::DETAIL_TABLE_SPACER_ROWS + 1;
        $columnLetters = ['A', 'B', 'C', 'D', 'E', 'F', 'G'];
        $headers = ['Tanggal', 'Umur (Hari)', 'Nama Barang', 'Tipe', 'Dosis/Qty', 'Unit', 'Catatan'];

        foreach ($headers as $index => $label) {
            $sheet->setCellValue($columnLetters[$index].$headerRow, $label);
        }

        $row = $headerRow + 1;
        foreach ($detailRows as $detail) {
            $sheet->setCellValue("A{$row}", $detail['tanggal']);
            $sheet->setCellValue("B{$row}", $detail['umur_hari']);
            $sheet->setCellValue("C{$row}", $detail['nama_barang']);
            $sheet->setCellValue("D{$row}", $detail['tipe']);
            $sheet->setCellValue("E{$row}", $detail['quantity']);
            $sheet->setCellValue("F{$row}", $detail['unit']);
            $sheet->setCellValue("G{$row}", $detail['catatan']);
            $row++;
        }

        $lastDataRow = $row > $headerRow + 1 ? $row - 1 : $headerRow;
        $tableRange = "A{$headerRow}:G{$lastDataRow}";
        $this->applyOutlineBorder($sheet, $tableRange);
        $this->applyGreenHeaderStyle($sheet, "A{$headerRow}:G{$headerRow}");

        foreach ($columnLetters as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }
    }

    /**
     * @param  list<array{nama_barang: string, tipe: string, unit: string, total_qty: float, jumlah_entri: int}>  $summaryRows
     */
    private function writeSummarySheet(Spreadsheet $spreadsheet, array $summaryRows): void
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Summary');

        $sheet->setCellValue('A1', 'Laporan Pemakaian OVK — Ringkasan');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $this->applyOutlineBorder($sheet, 'A1:E1');

        $headerRow = 3;
        $columnLetters = ['A', 'B', 'C', 'D', 'E'];
        $headers = ['Nama Barang', 'Tipe', 'Unit', 'Total Dosis/Qty', 'Jumlah Entri'];

        foreach ($headers as $index => $label) {
            $sheet->setCellValue($columnLetters[$index].$headerRow, $label);
        }

        $row = $headerRow + 1;
        foreach ($summaryRows as $summary) {
            $sheet->setCellValue("A{$row}", $summary['nama_barang']);
            $sheet->setCellValue("B{$row}", $summary['tipe']);
            $sheet->setCellValue("C{$row}", $summary['unit']);
            $sheet->setCellValue("D{$row}", $summary['total_qty']);
            $sheet->setCellValue("E{$row}", $summary['jumlah_entri']);
            $row++;
        }

        $lastDataRow = $row > $headerRow + 1 ? $row - 1 : $headerRow;
        $tableRange = "A{$headerRow}:E{$lastDataRow}";
        $this->applyOutlineBorder($sheet, $tableRange);
        $this->applyGreenHeaderStyle($sheet, "A{$headerRow}:E{$headerRow}");

        foreach ($columnLetters as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }
    }

    /**
     * Menulis metadata periode; mengembalikan nomor baris terakhir metadata.
     */
    private function writePeriodMetadata(Worksheet $sheet, ProductionPeriod $period): int
    {
        $row = 2;

        $sheet->setCellValue("A{$row}", 'Kode Periode:');
        $sheet->setCellValue("B{$row}", $period->period_code);
        $row++;

        $sheet->setCellValue("A{$row}", 'Tanggal Mulai:');
        $sheet->setCellValue("B{$row}", $period->start_date?->format('Y-m-d') ?? '');
        $row++;

        if ($period->end_date) {
            $sheet->setCellValue("A{$row}", 'Tanggal Selesai:');
            $sheet->setCellValue("B{$row}", $period->end_date->format('Y-m-d'));
            $row++;
        }

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

    private function applyGreenHeaderStyle(Worksheet $sheet, string $cellRange): void
    {
        $sheet->getStyle($cellRange)->applyFromArray([
            'font' => [
                'bold' => true,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['argb' => $this->headerFillArgb()],
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                    'color' => ['argb' => 'FF000000'],
                ],
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
