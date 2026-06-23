<?php

namespace App\Services\Api;

use App\Models\Rhpp;
use App\Models\RhppDocument;
use App\Models\PeriodInvestor;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class RhppActionService
{
    public function publishRhpp(string $periodId, string $syncTimestamp): array
    {
        // 1. Fetch RHPP
        $rhpp = Rhpp::where('period_id', $periodId)->first();

        if (!$rhpp) {
            $this->abort(404, 'Draft RHPP belum dibuat.');
        }

        // 2. Check Status
        if ($rhpp->publish_status === 'PUBLISHED') {
            $this->abort(400, 'RHPP sudah dipublikasi.');
        }

        // 3. Check Document
        $hasDocument = RhppDocument::where('Rhpp_id', $rhpp->id)->exists();
        if (!$hasDocument) {
            $this->abort(400, 'Tidak dapat publish: Dokumen PDF RHPP harus diunggah terlebih dahulu.');
        }

        return DB::transaction(function () use ($rhpp, $periodId, $syncTimestamp) {
            $timestamp = Carbon::parse($syncTimestamp);
            $now = now();

            // 4. Update RHPP status
            $rhpp->update([
                'publish_status' => 'PUBLISHED',
                'sync_status' => 'SYNCED',
                'updated_at_client' => $timestamp,
                'updated_at_server' => $now,
            ]);

            // 5. Fetch and calculate dividends for PeriodInvestors
            $investors = PeriodInvestor::where('period_id', $periodId)->get();

            foreach ($investors as $investor) {
                $dividend = ($investor->profit_share_percentage / 100) * $rhpp->net_profit;

                $investor->update([
                    'final_dividend_amount' => $dividend,
                    'is_paid' => false,
                    'sync_status' => 'SYNCED',
                    'updated_at_client' => $timestamp,
                    'updated_at_server' => $now,
                ]);
            }

            return [
                'id' => $rhpp->id,
                'publish_status' => $rhpp->publish_status,
                'net_profit' => $rhpp->net_profit,
            ];
        });
    }

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
