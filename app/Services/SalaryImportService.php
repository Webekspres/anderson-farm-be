<?php

namespace App\Services;

use App\Models\EmployeeSalary;
use App\Models\ProductionPeriod;
use App\Models\Transaction;
use App\Models\TransactionCategory;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SalaryImportService
{
    private const HEADER_PERIOD_CODE = 'period code';

    private const HEADER_EMPLOYEE_USERNAME = 'employee username';

    private const HEADER_TOTAL_GAJI = 'total gaji';

    /**
     * @var list<string>
     */
    private const REQUIRED_HEADERS = [
        self::HEADER_PERIOD_CODE,
        self::HEADER_EMPLOYEE_USERNAME,
        self::HEADER_TOTAL_GAJI,
    ];

    public function import(UploadedFile $file, User $importer): SalaryImportResult
    {
        $spreadsheet = IOFactory::load($file->getRealPath());
        $sheet = $spreadsheet->getActiveSheet();

        $importErrors = [];
        $columnMap = $this->resolveColumnMap($sheet, $importErrors);

        if ($columnMap === null) {
            $spreadsheet->disconnectWorksheets();

            return SalaryImportResult::validationFailed($importErrors);
        }

        $category = $this->resolveSalaryCategory();

        if ($category === null) {
            $spreadsheet->disconnectWorksheets();

            return SalaryImportResult::validationFailed([
                'Kategori pengeluaran gaji tidak dikonfigurasi di sistem.',
            ]);
        }

        $rawRows = $this->extractDataRows($sheet, $columnMap);
        $spreadsheet->disconnectWorksheets();

        if ($rawRows === []) {
            return SalaryImportResult::success(
                'Data gaji berhasil di-import dan disinkronisasikan ke jurnal keuangan pusat.',
            );
        }

        $periodCodes = collect($rawRows)->pluck('period_code')->filter()->unique()->values();
        $usernames = collect($rawRows)->pluck('username')->filter()->unique()->values();

        $periodsByCode = ProductionPeriod::query()
            ->whereIn('period_code', $periodCodes)
            ->get()
            ->keyBy('period_code');

        $usersByUsername = User::query()
            ->whereIn('username', $usernames)
            ->get()
            ->keyBy('username');

        $periodIds = $periodsByCode->pluck('id');
        $employeeIds = $usersByUsername->pluck('id');

        $existingSalaryKeys = EmployeeSalary::query()
            ->whereIn('period_id', $periodIds)
            ->whereIn('employee_id', $employeeIds)
            ->get()
            ->mapWithKeys(fn (EmployeeSalary $salary) => [
                $this->salaryKey($salary->period_id, $salary->employee_id) => true,
            ]);

        $pendingRows = [];
        $seenRowKeys = [];

        foreach ($rawRows as $raw) {
            $excelRow = $raw['excel_row'];
            $periodCode = $raw['period_code'];
            $username = $raw['username'];
            $amountRaw = $raw['amount_raw'];

            if ($periodCode === '' && $username === '' && ($amountRaw === '' || $amountRaw === null)) {
                continue;
            }

            if ($periodCode === '' || $username === '') {
                continue;
            }

            $rowKey = strtolower($periodCode).'|'.strtolower($username);

            if (isset($seenRowKeys[$rowKey])) {
                $importErrors[] = "Baris [{$excelRow}]: Kombinasi Period Code dan Employee Username duplikat dalam file.";

                continue;
            }

            $seenRowKeys[$rowKey] = true;

            $amount = $this->parseAmount($amountRaw);

            if ($amount === null) {
                $importErrors[] = "Baris [{$excelRow}]: Total Gaji harus berupa angka valid.";

                continue;
            }

            $period = $periodsByCode->get($periodCode);

            if ($period === null) {
                $importErrors[] = "Baris [{$excelRow}]: Period Code '{$periodCode}' tidak ditemukan di sistem.";

                continue;
            }

            $user = $usersByUsername->get($username);

            if ($user === null) {
                $importErrors[] = "Baris [{$excelRow}]: Employee Username '{$username}' tidak ditemukan di sistem.";

                continue;
            }

            if ($user->role !== 'abk') {
                $importErrors[] = "Baris [{$excelRow}]: Employee Username '{$username}' bukan user ABK.";

                continue;
            }

            $salaryKey = $this->salaryKey($period->id, $user->id);

            if ($amount <= 0 && ! isset($existingSalaryKeys[$salaryKey])) {
                continue;
            }

            $pendingRows[] = [
                'excel_row' => $excelRow,
                'period_id' => $period->id,
                'employee_id' => $user->id,
                'username' => $username,
                'amount' => $amount,
            ];
        }

        if ($importErrors !== []) {
            return SalaryImportResult::validationFailed($importErrors);
        }

        if ($pendingRows === []) {
            return SalaryImportResult::success(
                'Data gaji berhasil di-import dan disinkronisasikan ke jurnal keuangan pusat.',
            );
        }

        $now = now();
        $categoryId = $category->id;

        DB::transaction(function () use ($pendingRows, $importer, $now, $categoryId): void {
            foreach ($pendingRows as $row) {
                $salary = EmployeeSalary::query()->updateOrCreate(
                    [
                        'period_id' => $row['period_id'],
                        'employee_id' => $row['employee_id'],
                    ],
                    [
                        'salary_amount' => $row['amount'],
                        'payment_status' => 'paid',
                        'sync_status' => 'SYNCED',
                        'created_at_client' => $now,
                        'updated_at_client' => $now,
                        'created_at_server' => $now,
                        'updated_at_server' => $now,
                        'deleted_at' => null,
                    ],
                );

                $transaction = Transaction::query()->firstOrNew(['salary_id' => $salary->id]);

                if (! $transaction->exists) {
                    $transaction->id = (string) Str::uuid();
                }

                $transaction->fill([
                    'period_id' => $row['period_id'],
                    'coop_id' => null,
                    'user_id' => $importer->id,
                    'category_id' => $categoryId,
                    'harvest_id' => null,
                    'amount' => $row['amount'],
                    'date' => $now,
                    'description' => 'Import gaji ABK - '.$row['username'],
                    'expense_scope' => 'FLOOR_SPECIFIC',
                    'business_status' => 'APPROVED',
                    'approved_by' => $importer->id,
                    'sync_status' => 'SYNCED',
                    'created_at_client' => $transaction->exists ? $transaction->created_at_client : $now,
                    'updated_at_client' => $now,
                    'created_at_server' => $transaction->exists ? $transaction->created_at_server : $now,
                    'updated_at_server' => $now,
                    'deleted_at' => null,
                ]);

                $transaction->save();
            }
        });

        return SalaryImportResult::success(
            'Data gaji berhasil di-import dan disinkronisasikan ke jurnal keuangan pusat.',
        );
    }

    /**
     * @param  list<string>  $importErrors
     * @return array<string, int>|null column key => 1-based column index
     */
    private function resolveColumnMap(Worksheet $sheet, array &$importErrors): ?array
    {
        $headerRow = 1;
        $highestColumn = $sheet->getHighestDataColumn($headerRow);
        $highestColumnIndex = Coordinate::columnIndexFromString($highestColumn);

        $normalizedHeaders = [];

        for ($col = 1; $col <= $highestColumnIndex; $col++) {
            $label = $this->normalizeHeaderLabel(
                (string) $this->cellAt($sheet, $col, $headerRow)->getValue(),
            );

            if ($label !== '') {
                $normalizedHeaders[$label] = $col;
            }
        }

        $columnMap = [];

        foreach (self::REQUIRED_HEADERS as $required) {
            if (! isset($normalizedHeaders[$required])) {
                $importErrors[] = 'Format header tidak sesuai template gaji.';

                return null;
            }

            $columnMap[$required] = $normalizedHeaders[$required];
        }

        return $columnMap;
    }

    /**
     * @param  array<string, int>  $columnMap
     * @return list<array{excel_row: int, period_code: string, username: string, amount_raw: mixed}>
     */
    private function extractDataRows(Worksheet $sheet, array $columnMap): array
    {
        $rows = [];
        $highestRow = $sheet->getHighestDataRow();

        for ($excelRow = 2; $excelRow <= $highestRow; $excelRow++) {
            $periodCode = $this->cellString($sheet, $columnMap[self::HEADER_PERIOD_CODE], $excelRow);
            $username = $this->cellString($sheet, $columnMap[self::HEADER_EMPLOYEE_USERNAME], $excelRow);
            $amountCell = $this->cellAt($sheet, $columnMap[self::HEADER_TOTAL_GAJI], $excelRow);

            $rows[] = [
                'excel_row' => $excelRow,
                'period_code' => $periodCode,
                'username' => $username,
                'amount_raw' => $amountCell->getCalculatedValue(),
            ];
        }

        return $rows;
    }

    private function cellString(Worksheet $sheet, int $columnIndex, int $row): string
    {
        $value = $this->cellAt($sheet, $columnIndex, $row)->getValue();

        if ($value === null) {
            return '';
        }

        return trim((string) $value);
    }

    private function cellAt(Worksheet $sheet, int $columnIndex, int $row): Cell
    {
        $columnLetter = Coordinate::stringFromColumnIndex($columnIndex);

        return $sheet->getCell($columnLetter.$row);
    }

    private function normalizeHeaderLabel(string $label): string
    {
        return strtolower(trim($label));
    }

    private function parseAmount(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }

        if (is_numeric($value)) {
            return (float) $value;
        }

        $normalized = preg_replace('/[^\d.-]/', '', (string) $value);

        if ($normalized === '' || $normalized === '-' || ! is_numeric($normalized)) {
            return null;
        }

        return (float) $normalized;
    }

    private function salaryKey(string $periodId, string $employeeId): string
    {
        return $periodId.'|'.$employeeId;
    }

    private function resolveSalaryCategory(): ?TransactionCategory
    {
        $name = (string) config('export.salary_expense_category_name', 'Gaji Pegawai');

        return TransactionCategory::query()
            ->where('name', $name)
            ->where('type', 'EXPENSE')
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->first();
    }
}
