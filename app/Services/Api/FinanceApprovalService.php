<?php

namespace App\Services\Api;

use App\Enums\BusinessStatus;
use App\Models\CoopUserAssignment;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\DB;

/**
 * Approval workflow for finance transactions (expenses/incomes).
 * Mirrors DailyActivityApprovalService but scopes over Transaction.
 */
class FinanceApprovalService
{
    /**
     * @param  array{period_id?: string, coop_id?: string, business_status?: string, per_page?: int}  $filters
     */
    public function listForReviewer(User $user, array $filters): LengthAwarePaginator
    {
        $this->ensureReviewerRole($user);

        $perPage = $filters['per_page'] ?? 15;
        $businessStatus = $filters['business_status'] ?? BusinessStatus::Submitted->value;

        $query = Transaction::query()
            ->with(['user', 'category', 'period.floor.coop'])
            ->where('business_status', $businessStatus)
            ->orderByDesc('updated_at_server');

        $this->applyReviewerScope($query, $user);

        if (! empty($filters['period_id'])) {
            $query->where('period_id', $filters['period_id']);
        }

        if (! empty($filters['coop_id'])) {
            $query->whereHas('period.floor', fn (Builder $q) => $q->where('coop_id', $filters['coop_id']));
        }

        return $query->paginate($perPage);
    }

    public function findForReviewer(User $user, string $transactionId): Transaction
    {
        $this->ensureReviewerRole($user);

        $transaction = Transaction::query()
            ->with(['user', 'category', 'period.floor.coop'])
            ->find($transactionId);

        if (! $transaction) {
            $this->abort(404, 'Transaksi tidak ditemukan.');
        }

        if (! $this->canReview($user, $transaction)) {
            $this->abort(403, 'Anda tidak memiliki akses ke transaksi ini.');
        }

        return $transaction;
    }

    public function review(User $reviewer, Transaction $transaction, string $action, ?string $rejectionReason): Transaction
    {
        $this->ensureReviewerRole($reviewer);

        if (! $this->canReview($reviewer, $transaction)) {
            $this->abort(403, 'Anda tidak memiliki akses ke transaksi ini.');
        }

        if ($transaction->business_status !== BusinessStatus::Submitted->value) {
            $this->abort(422, 'Hanya transaksi berstatus Submitted yang dapat ditinjau.');
        }

        $now = now();
        $newStatus = $action === 'approve'
            ? BusinessStatus::Approved->value
            : BusinessStatus::Rejected->value;

        return DB::transaction(function () use ($transaction, $reviewer, $newStatus, $rejectionReason, $now, $action) {
            $locked = Transaction::query()
                ->where('id', $transaction->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($locked->business_status !== BusinessStatus::Submitted->value) {
                $this->abort(422, 'Hanya transaksi berstatus Submitted yang dapat ditinjau.');
            }

            $locked->update([
                'business_status' => $newStatus,
                'approved_by' => $reviewer->id,
                'rejection_reason' => $action === 'reject' ? $rejectionReason : null,
                'updated_at_server' => $now,
            ]);

            return $locked->fresh(['user', 'category', 'period.floor.coop']);
        });
    }

    public function canReview(User $user, Transaction $transaction): bool
    {
        if (in_array($user->role, ['admin', 'finance'], true)) {
            return true;
        }

        if ($user->role !== 'manager') {
            return false;
        }

        $transaction->loadMissing('period.floor.coop.farm.area');
        $coop = $transaction->period?->floor?->coop;

        if (! $coop) {
            return false;
        }

        if ($coop->farm?->area?->manager_id === $user->id) {
            return true;
        }

        return CoopUserAssignment::query()
            ->where('user_id', $user->id)
            ->where('coop_id', $coop->id)
            ->whereNull('deleted_at')
            ->exists();
    }

    /**
     * @param  Builder<Transaction>  $query
     */
    private function applyReviewerScope(Builder $query, User $user): void
    {
        if (in_array($user->role, ['admin', 'finance'], true)) {
            return;
        }

        $assignedCoopIds = CoopUserAssignment::query()
            ->where('user_id', $user->id)
            ->whereNull('deleted_at')
            ->pluck('coop_id');

        $query->whereHas('period.floor.coop', function (Builder $coopQuery) use ($user, $assignedCoopIds) {
            $coopQuery->where(function (Builder $scope) use ($user, $assignedCoopIds) {
                $scope->whereHas('farm.area', fn (Builder $areaQuery) => $areaQuery->where('manager_id', $user->id));

                if ($assignedCoopIds->isNotEmpty()) {
                    $scope->orWhereIn('id', $assignedCoopIds);
                }
            });
        });
    }

    private function ensureReviewerRole(User $user): void
    {
        if (! in_array($user->role, ['admin', 'manager', 'finance'], true)) {
            $this->abort(403, 'Akses ditolak. Hanya Manager, Finance, atau Admin yang dapat mengakses modul approval keuangan.');
        }
    }

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
