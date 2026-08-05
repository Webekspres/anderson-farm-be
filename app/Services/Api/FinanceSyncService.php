<?php

namespace App\Services\Api;

use App\Models\CoopFloor;
use App\Models\CoopUserAssignment;
use App\Models\EmployeeSalary;
use App\Models\ProductionPeriod;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;

class FinanceSyncService
{
    /**
     * Susun payload pull untuk GET /sync/finances.
     *
     * Menerapkan 3 level hierarki:
     * coop_user_assignments → coop_floors → production_periods → records
     *
     * @return array{transactions: Collection, employee_salaries: Collection}
     */
    public function getPullPayload(User $user, ?string $lastSyncTimestamp): array
    {
        // ── Tahap 1: Resolusi scope hierarki ──
        // Semua query berbasis scope ini, kecuali ABK untuk salary-nya sendiri
        $periodIds = $this->resolvePeriodIds($user);

        // ── Tahap 2: Role-based data isolation ──
        if ($user->role === 'abk') {
            return [
                // ABK TIDAK boleh melihat transaksi pengeluaran kandang
                'transactions' => collect(),
                // ABK hanya bisa melihat data gajinya sendiri
                'employee_salaries' => $this->fetchSalariesForAbk($user->id, $lastSyncTimestamp),
            ];
        }

        // PIC, Manager, Admin: akses penuh ke scope kandang mereka
        return [
            'transactions' => $this->fetchTransactions($periodIds, $lastSyncTimestamp),
            'employee_salaries' => $this->fetchSalaries($periodIds, $lastSyncTimestamp),
        ];
    }

    /**
     * Resolusi period_id yang relevan berdasarkan assignment coop user.
     * Admin mendapat semua period.
     */
    private function resolvePeriodIds(User $user): SupportCollection
    {
        if ($user->role === 'admin') {
            return ProductionPeriod::withTrashed()->pluck('id');
        }

        $coopIds = CoopUserAssignment::where('user_id', $user->id)
            ->whereNull('deleted_at')
            ->pluck('coop_id');

        $floorIds = CoopFloor::whereIn('coop_id', $coopIds)
            ->whereNull('deleted_at')
            ->pluck('id');

        return ProductionPeriod::withTrashed()
            ->whereIn('floor_id', $floorIds)
            ->pluck('id');
    }

    /**
     * Fetch transaksi dalam scope period dengan delta sync support.
     * Menggunakan updated_at_server untuk filter karena ini adalah server timestamp.
     */
    private function fetchTransactions(SupportCollection $periodIds, ?string $lastSyncTimestamp): Collection
    {
        return Transaction::withTrashed()
            ->with('category')
            ->whereIn('period_id', $periodIds)
            ->when(
                $lastSyncTimestamp,
                fn ($q) => $q->where('updated_at_server', '>', $lastSyncTimestamp)
            )
            ->get();
    }

    /**
     * Fetch semua gaji dalam scope period (untuk PIC/Manager/Admin).
     * Filter delta via updated_at_server karena kolom tersebut dikontrol server.
     */
    private function fetchSalaries(SupportCollection $periodIds, ?string $lastSyncTimestamp): Collection
    {
        return EmployeeSalary::withTrashed()
            ->whereIn('period_id', $periodIds)
            ->when(
                $lastSyncTimestamp,
                fn ($q) => $q->where('updated_at_server', '>', $lastSyncTimestamp)
            )
            ->get();
    }

    /**
     * Fetch gaji khusus untuk ABK — hanya milik mereka sendiri.
     */
    private function fetchSalariesForAbk(string $userId, ?string $lastSyncTimestamp): Collection
    {
        return EmployeeSalary::withTrashed()
            ->where('employee_id', $userId)
            ->when(
                $lastSyncTimestamp,
                fn ($q) => $q->where('updated_at_server', '>', $lastSyncTimestamp)
            )
            ->get();
    }
}
