<?php

namespace App\Services;

use App\Models\HarvestEntry;
use App\Models\ProductionPeriod;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RhppExportService
{
    public function __construct(private RhppCalculationService $calculationService) {}

    /**
     * Export RHPP data in requested format.
     */
    public function export(ProductionPeriod $period, string $format): StreamedResponse
    {
        $metrics = $this->calculationService->calculateMetrics($period);

        return match ($format) {
            'excel' => $this->exportToExcel($period, $metrics),
            'pdf' => $this->exportToPdf($period, $metrics),
        };
    }

    /**
     * Export to Excel format.
     */
    private function exportToExcel(ProductionPeriod $period, array $metrics): StreamedResponse
    {
        $filename = sprintf('DRAFT_RHPP_%s.xlsx', $period->id);

        return response()->streamDownload(function () use ($period, $metrics) {
            $spreadsheet = new Spreadsheet;
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Summary');

            $row = 1;
            $sheet->setCellValue("A{$row}", 'RHPP Export Summary');
            $sheet->getStyle("A{$row}")->getFont()->setBold(true)->setSize(14);
            $row += 2;

            $sheet->setCellValue("A{$row}", 'Period ID:');
            $sheet->setCellValue("B{$row}", $period->id);
            $row++;

            $sheet->setCellValue("A{$row}", 'Coop:');
            $sheet->setCellValue("B{$row}", $period->floor?->coop?->name ?? 'N/A');
            $row += 2;

            $sheet->setCellValue("A{$row}", 'Gross Revenue');
            $sheet->setCellValue("B{$row}", $metrics['gross_revenue']);
            $row++;

            $sheet->setCellValue("A{$row}", 'Total Cost');
            $sheet->setCellValue("B{$row}", $metrics['total_cost']);
            $row++;

            $netProfitRow = $row;
            $sheet->setCellValue("A{$row}", 'Net Profit');
            $sheet->setCellValue("B{$row}", $metrics['net_profit']);
            $sheet->getStyle("B{$netProfitRow}")->getFont()->setBold(true)->setColor(new Color('FF008000'));
            $row++;

            $sheet->setCellValue("A{$row}", 'FCR (Feed Conversion Ratio)');
            $sheet->setCellValue("B{$row}", $metrics['fcr']);
            $row++;

            $sheet->setCellValue("A{$row}", 'IP (Index Performance)');
            $sheet->setCellValue("B{$row}", $metrics['ip']);
            $row++;

            $sheet->setCellValue("A{$row}", 'Profitability Margin (%)');
            $sheet->setCellValue("B{$row}", $metrics['profitability_margin']);

            $sheet->getColumnDimension('A')->setAutoSize(true);
            $sheet->getColumnDimension('B')->setAutoSize(true);

            $harvestSheet = $spreadsheet->createSheet();
            $harvestSheet->setTitle('Harvests');
            $harvestSheet->setCellValue('A1', 'Date');
            $harvestSheet->setCellValue('B1', 'Weight (kg)');
            $harvestSheet->setCellValue('C1', 'Price/kg');
            $harvestSheet->setCellValue('D1', 'Total Revenue');
            $harvestSheet->getStyle('A1:D1')->getFont()->setBold(true);

            $harvestRow = 2;
            foreach ($period->dailyActivityHeaders as $header) {
                foreach ($header->harvests as $harvest) {
                    $harvestSheet->setCellValue("A{$harvestRow}", $header->date?->format('Y-m-d') ?? '');
                    $harvestSheet->setCellValue("B{$harvestRow}", $harvest->total_weight);
                    $harvestSheet->setCellValue("C{$harvestRow}", $harvest->price_per_kg);
                    $harvestSheet->setCellValue("D{$harvestRow}", $this->harvestRevenue($harvest));
                    $harvestRow++;
                }
            }

            foreach (['A', 'B', 'C', 'D'] as $column) {
                $harvestSheet->getColumnDimension($column)->setAutoSize(true);
            }

            $costsSheet = $spreadsheet->createSheet();
            $costsSheet->setTitle('Costs');
            $costsSheet->setCellValue('A1', 'Description');
            $costsSheet->setCellValue('B1', 'Amount');
            $costsSheet->getStyle('A1:B1')->getFont()->setBold(true);

            $costRow = 2;
            $costsSheet->setCellValue("A{$costRow}", 'Initial DOC Cost');
            $costsSheet->setCellValue("B{$costRow}", $period->initial_doc_cost ?? 0);
            $costRow++;

            $costsSheet->setCellValue("A{$costRow}", 'Total Feed Consumption (kg)');
            $costsSheet->setCellValue("B{$costRow}", $metrics['feed_consumption']);
            $costRow++;

            $costsSheet->setCellValue("A{$costRow}", 'Operating Expenses');
            $costsSheet->setCellValue("B{$costRow}", $metrics['total_cost'] - ($period->initial_doc_cost ?? 0));

            $costsSheet->getColumnDimension('A')->setAutoSize(true);
            $costsSheet->getColumnDimension('B')->setAutoSize(true);

            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
            $spreadsheet->disconnectWorksheets();
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    /**
     * Export to PDF format.
     */
    private function exportToPdf(ProductionPeriod $period, array $metrics): StreamedResponse
    {
        $filename = sprintf('DRAFT_RHPP_%s.pdf', $period->id);

        $html = view('rhpp.export-pdf', [
            'period' => $period,
            'metrics' => $metrics,
        ])->render();

        return response()->streamDownload(function () use ($html) {
            $pdf = \PDF::loadHTML($html);
            echo $pdf->stream();
        }, $filename, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    /**
     * @param  HarvestEntry  $harvest
     */
    private function harvestRevenue(object $harvest): float
    {
        if ($harvest->total_revenue !== null) {
            return (float) $harvest->total_revenue;
        }

        return (float) (($harvest->total_weight ?? 0) * ($harvest->price_per_kg ?? 0));
    }
}
