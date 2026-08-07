<?php

namespace App\Services\Api;

use App\Models\ProductionPeriod;
use App\Models\Rhpp;
use App\Models\RhppDocument;
use App\Services\RhppCalculationService;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RhppDocumentService
{
    public function __construct(
        private readonly RhppCalculationService $rhppCalculationService,
        private readonly ObjectStorageService $objectStorage,
    ) {}

    /**
     * Hitung angka operasional dan upsert draft RHPP (tanpa PDF).
     *
     * @return array{rhpp: Rhpp, metrics: array<string, float|int>}
     */
    public function generateDraft(string $periodId): array
    {
        $period = ProductionPeriod::find($periodId);

        if (! $period) {
            $this->abort(404, 'Periode tidak ditemukan.');
        }

        if ($period->status !== 'completed') {
            $this->abort(400, 'Tidak dapat menghitung RHPP: Periode belum ditutup.');
        }

        $metrics = $this->rhppCalculationService->calculateMetrics($period);
        $now = now();

        $existing = Rhpp::query()->where('period_id', $period->id)->first();
        if ($existing && $existing->publish_status === 'PUBLISHED') {
            $this->abort(400, 'RHPP sudah dipublish; buat revisi manual jika perlu mengubah angka.');
        }

        $rhpp = DB::transaction(function () use ($period, $metrics, $now): Rhpp {
            $rhpp = Rhpp::firstOrCreate(
                ['period_id' => $period->id],
                [
                    'id' => Str::uuid()->toString(),
                    'publish_status' => 'DRAFT',
                    'sync_status' => 'SYNCED',
                    'created_at_client' => $now,
                    'updated_at_client' => $now,
                    'created_at_server' => $now,
                    'updated_at_server' => $now,
                    'total_income' => $metrics['gross_revenue'],
                    'total_expense' => $metrics['total_cost'],
                    'net_profit' => $metrics['net_profit'],
                ]
            );

            $rhpp->update([
                'total_income' => $metrics['gross_revenue'],
                'total_expense' => $metrics['total_cost'],
                'net_profit' => $metrics['net_profit'],
                'publish_status' => 'DRAFT',
                'sync_status' => 'SYNCED',
                'updated_at_client' => $now,
                'updated_at_server' => $now,
            ]);

            return $rhpp->fresh();
        });

        return [
            'rhpp' => $rhpp,
            'metrics' => $metrics,
        ];
    }

    /**
     * Eksekusi upload dokumen RHPP dalam satu DB transaction:
     *  1. Pre-flight: validasi status periode
     *  2. Upsert Rhpp shell (financial numbers)
     *  3. Store file ke disk 'public'
     *  4. Create RhppDocument record
     *
     * @return array{rhpp: Rhpp, document: RhppDocument}
     */
    public function uploadDocument(
        string $periodId,
        array $validatedData,
        UploadedFile $file,
    ): array {
        // ── Gate: Periode harus sudah dalam status completed ──
        $period = ProductionPeriod::find($periodId);

        if (! $period) {
            $this->abort(404, 'Periode tidak ditemukan.');
        }

        if ($period->status !== 'completed') {
            $this->abort(400, 'Tidak dapat mengunggah dokumen RHPP: Periode belum ditutup.');
        }

        // ── Eksekusi dalam DB transaction ──
        return DB::transaction(function () use ($period, $validatedData, $file): array {
            $now = now();

            // ── Step 1: Upsert Rhpp — cari yang ada atau buat baru ──
            $rhpp = Rhpp::firstOrCreate(
                ['period_id' => $period->id],
                [
                    'id' => Str::uuid()->toString(),
                    'publish_status' => 'DRAFT',
                    'sync_status' => 'SYNCED',
                    'created_at_client' => $now,
                    'updated_at_client' => $now,
                    'created_at_server' => $now,
                    'updated_at_server' => $now,
                    'total_income' => $validatedData['total_income'],
                    'total_expense' => $validatedData['total_expense'],
                    'net_profit' => $validatedData['net_profit'],
                ]
            );

            // Selalu update angka finansial dan pastikan status tetap DRAFT
            $rhpp->update([
                'total_income' => $validatedData['total_income'],
                'total_expense' => $validatedData['total_expense'],
                'net_profit' => $validatedData['net_profit'],
                'publish_status' => 'DRAFT',
                'sync_status' => 'SYNCED',
                'updated_at_client' => $now,
                'updated_at_server' => $now,
            ]);

            // ── Step 2: Simpan file PDF ke object storage ──
            $stored = $this->objectStorage->storeForPeriod($file, $period->id, 'rhpp');

            // ── Step 3: Buat record RhppDocument ──
            $document = RhppDocument::create([
                'id' => Str::uuid()->toString(),
                'Rhpp_id' => $rhpp->id,
                'name' => 'RHPP Final - '.$period->period_code,
                'file_path_local' => $stored['path'],
                'file_url' => $stored['url'],
                'file_type' => 'pdf',
                'sync_status' => 'SYNCED',
                'created_at_client' => $now,
                'updated_at_client' => $now,
                'created_at_server' => $now,
                'updated_at_server' => $now,
            ]);

            return ['rhpp' => $rhpp, 'document' => $document];
        });
    }

    /**
     * Helper: abort dengan format JSON standar.
     */
    private function abort(int $status, string $message): never
    {
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'message' => $message,
                'data' => null,
            ], $status)
        );
    }
}
