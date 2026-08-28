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
     * Aktifkan periode dari draft → active (Modul 3 step 13).
     *
     * @throws HttpResponseException jika validasi apapun gagal
     */
    public function activatePeriod(string $periodId, User $user): ProductionPeriod
    {
        $period = ProductionPeriod::with('floor.coop')->find($periodId);

        if (! $period) {
            $this->abort(404, 'Periode tidak ditemukan.');
        }

        if (in_array($user->role, ['abk', 'investor'], true)) {
            $this->abort(403, 'Role Anda tidak diizinkan mengaktifkan periode.');
        }

        if ($user->role === 'pic') {
            $coopId = $period->floor?->coop_id;
            $isAssigned = CoopUserAssignment::query()
                ->where('user_id', $user->id)
                ->where('coop_id', $coopId)
                ->whereNull('deleted_at')
                ->exists();

            if (! $isAssigned) {
                $this->abort(403, 'Anda tidak memiliki akses ke kandang pada periode ini.');
            }
        }

        if ($period->status === 'active') {
            $this->abort(400, 'Periode ini sudah aktif.');
        }

        if ($period->status === 'completed') {
            $this->abort(400, 'Periode yang sudah ditutup tidak dapat diaktifkan kembali.');
        }

        if ($period->status !== 'draft') {
            $this->abort(400, 'Hanya periode berstatus draft yang dapat diaktifkan.');
        }

        $floorId = $period->floor_id;
        if ($floorId) {
            // Satu lantai hanya boleh punya SATU periode belum selesai.
            $hasOpenOverlap = ProductionPeriod::query()
                ->where('floor_id', $floorId)
                ->where('id', '!=', $period->id)
                ->whereNotIn('status', ['completed', 'closed'])
                ->exists();

            if ($hasOpenOverlap) {
                $this->abort(422, 'Lantai ini masih memiliki periode lain yang belum selesai. Tutup atau selesaikan periode lain terlebih dahulu.');
            }
        }

        return DB::transaction(function () use ($period): ProductionPeriod {
            $period->update([
                'status' => 'active',
                'updated_at_server' => now(),
            ]);

            return $period->fresh();
        });
    }

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
        if (in_array($user->role, ['abk', 'investor'], true)) {
            $this->abort(403, 'Role Anda tidak diizinkan menutup periode.');
        }

        // ── Gate 3: Assignment Check untuk role PIC ──
        // Admin dan Manager memiliki akses global
        if ($user->role === 'pic') {
            $coopId = $period->floor?->coop_id;
            $isAssigned = CoopUserAssignment::query()
                ->where('user_id', $user->id)
                ->where('coop_id', $coopId)
                ->whereNull('deleted_at')
                ->exists();

            if (! $isAssigned) {
                $this->abort(403, 'Anda tidak memiliki akses ke kandang pada periode ini.');
            }
        }

        // ── Gate 4: State Validation — Hanya periode active yang boleh ditutup ──
        if ($period->status === 'completed') {
            $this->abort(400, 'Periode ini sudah ditutup sebelumnya.');
        }

        if ($period->status !== 'active') {
            $this->abort(400, 'Hanya periode aktif yang dapat ditutup. Aktifkan periode terlebih dahulu.');
        }

        // ── Gate 5: Pre-Flight — Cek Daily Activity yang masih menggantung ──
        $hasPendingActivities = DailyActivityHeader::query()
            ->where('period_id', $periodId)
            ->whereIn('business_status', BusinessStatus::pendingValues())
            ->exists();

        if ($hasPendingActivities) {
            $this->abort(
                422,
                'Tidak dapat menutup periode: Masih ada laporan aktivitas harian yang berstatus Draft, Submitted, atau Needs Review.'
            );
        }

        // ── Gate 6: Pre-Flight — Cek Transaksi Keuangan yang masih menggantung ──
        $hasPendingTransactions = Transaction::query()
            ->where('period_id', $periodId)
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
                'end_date' => $period->end_date ?? now()->toDateString(),
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
