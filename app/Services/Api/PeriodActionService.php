<?php

namespace App\Services\Api;

use App\Enums\BusinessStatus;
use App\Models\CoopUserAssignment;
use App\Models\DailyActivityHeader;
use App\Models\ProductionPeriod;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\DB;

class PeriodActionService
{
    /**
     * Status yang dianggap "menggantung" untuk Transaksi Keuangan.
     * PENDING_APPROVAL setara dengan SUBMITTED di konteks transaksi.
     *
     * @var list<string>
     */
    private const PENDING_TRANSACTION_STATUSES = ['DRAFT', 'SUBMITTED', 'NEEDS_REVIEW'];

    /**
     * Eksekusi penutupan periode dengan serangkaian validasi ketat.
     *
     * @throws HttpResponseException jika validasi apapun gagal
     */
    public function closePeriod(string $periodId, array $data, User $user): ProductionPeriod
    {
        // ── Gate 1: Temukan periode ──
        $period = ProductionPeriod::with('floor.coop')->find($periodId);

        if (! $period) {
            $this->abort(404, 'Periode tidak ditemukan.');
        }

        // ── Gate 2: RBAC — Tolak abk dan investor ──
        if (in_array($user->role, ['abk', 'investor'])) {
            $this->abort(403, 'Role Anda tidak diizinkan menutup periode.');
        }

        // ── Gate 3: Assignment Check untuk role PIC ──
        // Admin dan Manager memiliki akses global
        if ($user->role === 'pic') {
            $coopId = $period->floor?->coop_id;
            $isAssigned = CoopUserAssignment::where('user_id', $user->id)
                ->where('coop_id', $coopId)
                ->whereNull('deleted_at')
                ->exists();

            if (! $isAssigned) {
                $this->abort(403, 'Anda tidak memiliki akses ke kandang pada periode ini.');
            }
        }

        // ── Gate 4: State Validation — Cegah double-close ──
        if ($period->status === 'completed') {
            $this->abort(400, 'Periode ini sudah ditutup sebelumnya.');
        }

        // ── Gate 5: Pre-Flight — Cek Daily Activity yang masih menggantung ──
        $hasPendingActivities = DailyActivityHeader::where('period_id', $periodId)
            ->whereIn('business_status', BusinessStatus::pendingValues())
            ->exists();

        if ($hasPendingActivities) {
            $this->abort(
                422,
                'Tidak dapat menutup periode: Masih ada laporan aktivitas harian yang berstatus Draft, Submitted, atau Needs Review.'
            );
        }

        // ── Gate 6: Pre-Flight — Cek Transaksi Keuangan yang masih menggantung ──
        $hasPendingTransactions = Transaction::where('period_id', $periodId)
            ->whereIn('business_status', self::PENDING_TRANSACTION_STATUSES)
            ->exists();

        if ($hasPendingTransactions) {
            $this->abort(
                422,
                'Tidak dapat menutup periode: Masih ada transaksi keuangan yang belum disetujui (Draft/Submitted).'
            );
        }

        // ── Execution: Semua gate lolos → Lock periode dalam DB transaction ──
        return DB::transaction(function () use ($period, $data): ProductionPeriod {
            $period->update([
                'status' => 'completed',
                'closed_at' => now(),
                'closing_reason' => $data['closing_reason'],
            ]);

            return $period->fresh();
        });
    }

    /**
     * Helper: throw HttpResponseException dengan format respons JSON standar.
     */
    private function abort(int $statusCode, string $message): never
    {
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'message' => $message,
                'data' => null,
            ], $statusCode)
        );
    }
}
