<?php

namespace App\Services\Api;

use App\Models\ProductionPeriod;
use App\Models\Rhpp;
use App\Models\RhppDocument;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RhppDocumentService
{
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
        string       $periodId,
        array        $validatedData,
        UploadedFile $file,
    ): array {
        // ── Gate: Periode harus sudah dalam status completed ──
        $period = ProductionPeriod::find($periodId);

        if (!$period) {
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
                    'id'             => Str::uuid()->toString(),
                    'publish_status' => 'DRAFT',
                    'sync_status'    => 'SYNCED',
                    'created_at_client' => $now,
                    'updated_at_client' => $now,
                    'created_at_server' => $now,
                    'updated_at_server' => $now,
                    'total_income'   => $validatedData['total_income'],
                    'total_expense'  => $validatedData['total_expense'],
                    'net_profit'     => $validatedData['net_profit'],
                ]
            );

            // Selalu update angka finansial dan pastikan status tetap DRAFT
            $rhpp->update([
                'total_income'   => $validatedData['total_income'],
                'total_expense'  => $validatedData['total_expense'],
                'net_profit'     => $validatedData['net_profit'],
                'publish_status' => 'DRAFT',
                'sync_status'    => 'SYNCED',
                'updated_at_client' => $now,
                'updated_at_server' => $now,
            ]);

            // ── Step 2: Simpan file PDF ke storage ──
            $storedPath = $file->store(
                "rhpp-documents/{$period->id}",
                'public'
            );

            $fileUrl = asset('storage/' . $storedPath);

            // ── Step 3: Buat record RhppDocument ──
            $document = RhppDocument::create([
                'id'             => Str::uuid()->toString(),
                'Rhpp_id'        => $rhpp->id,
                'name'           => 'RHPP Final - ' . $period->period_code,
                'file_path_local' => $storedPath,
                'file_url'       => $fileUrl,
                'file_type'      => 'pdf',
                'sync_status'    => 'SYNCED',
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
                'data'    => null,
            ], $status)
        );
    }
}
