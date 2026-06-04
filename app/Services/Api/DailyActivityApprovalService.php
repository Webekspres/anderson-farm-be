<?php

namespace App\Services\Api;

use App\Enums\BusinessStatus;
use App\Jobs\NotifyAbkOfDailyActivityReviewJob;
use App\Models\CoopUserAssignment;
use App\Models\DailyActivityHeader;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\DB;

class DailyActivityApprovalService
{
    /**
     * @param  array{period_id?: string, coop_id?: string, date_from?: string, date_to?: string, business_status?: string, per_page?: int}  $filters
     */
    public function listForReviewer(User $user, array $filters): LengthAwarePaginator
    {
        $this->ensureReviewerRole($user);

        $perPage = $filters['per_page'] ?? 15;
        $businessStatus = $filters['business_status'] ?? BusinessStatus::Submitted->value;

        $query = DailyActivityHeader::query()
            ->with(['user', 'period.floor.coop'])
            ->where('business_status', $businessStatus)
            ->orderByDesc('updated_at_server');

        $this->applyReviewerScope($query, $user);

        if (! empty($filters['period_id'])) {
            $query->where('period_id', $filters['period_id']);
        }

        if (! empty($filters['coop_id'])) {
            $query->whereHas('period.floor', fn (Builder $q) => $q->where('coop_id', $filters['coop_id']));
        }

        if (! empty($filters['date_from'])) {
            $query->whereDate('date', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('date', '<=', $filters['date_to']);
        }

        return $query->paginate($perPage);
    }

    public function findForReviewer(User $user, string $headerId): DailyActivityHeader
    {
        $this->ensureReviewerRole($user);

        $header = DailyActivityHeader::query()
            ->with([
                'user',
                'period.floor.coop',
                'dynamicLogs',
                'harvests',
                'ovkUsages',
                'photos',
                'dailyChecklistLogs',
            ])
            ->find($headerId);

        if (! $header) {
            $this->abort(404, 'Laporan harian tidak ditemukan.');
        }

        if (! $this->canReviewHeader($user, $header)) {
            $this->abort(403, 'Anda tidak memiliki akses ke laporan harian ini.');
        }

        return $header;
    }

    public function review(User $reviewer, DailyActivityHeader $header, string $action, ?string $rejectionReason): DailyActivityHeader
    {
        $this->ensureReviewerRole($reviewer);

        if (! $this->canReviewHeader($reviewer, $header)) {
            $this->abort(403, 'Anda tidak memiliki akses ke laporan harian ini.');
        }

        if ($header->business_status !== BusinessStatus::Submitted->value) {
            $this->abort(422, 'Hanya laporan berstatus Submitted yang dapat ditinjau.');
        }

        $header->loadMissing('period');
        if (! $header->period || $header->period->status !== 'active') {
            $this->abort(422, 'Periode tidak aktif. Tidak dapat memproses persetujuan.');
        }

        $now = now();
        $newStatus = $action === 'approve'
            ? BusinessStatus::Approved->value
            : BusinessStatus::Rejected->value;

        $updatedHeader = DB::transaction(function () use ($header, $reviewer, $newStatus, $rejectionReason, $now, $action) {
            $lockedHeader = DailyActivityHeader::query()
                ->where('id', $header->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedHeader->business_status !== BusinessStatus::Submitted->value) {
                $this->abort(422, 'Hanya laporan berstatus Submitted yang dapat ditinjau.');
            }

            $lockedHeader->update([
                'business_status' => $newStatus,
                'approved_by' => $reviewer->id,
                'rejection_reason' => $action === 'reject' ? $rejectionReason : null,
                'updated_at_server' => $now,
            ]);

            return $lockedHeader->fresh([
                'user',
                'period.floor.coop',
                'dynamicLogs',
                'harvests',
                'ovkUsages',
                'photos',
                'dailyChecklistLogs',
            ]);
        });

        NotifyAbkOfDailyActivityReviewJob::dispatch(
            $updatedHeader->id,
            $action,
            $rejectionReason,
        )->afterResponse();

        return $updatedHeader;
    }

    public function canReviewHeader(User $user, DailyActivityHeader $header): bool
    {
        if ($user->role === 'admin') {
            return true;
        }

        if ($user->role !== 'manager') {
            return false;
        }

        $header->loadMissing('period.floor.coop.farm.area');
        $coop = $header->period?->floor?->coop;

        if (! $coop) {
            return false;
        }

        $areaManagerId = $coop->farm?->area?->manager_id;
        if ($areaManagerId === $user->id) {
            return true;
        }

        return CoopUserAssignment::query()
            ->where('user_id', $user->id)
            ->where('coop_id', $coop->id)
            ->whereNull('deleted_at')
            ->exists();
    }

    /**
     * @param  Builder<DailyActivityHeader>  $query
     */
    private function applyReviewerScope(Builder $query, User $user): void
    {
        if ($user->role === 'admin') {
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
        if (! in_array($user->role, ['admin', 'manager'], true)) {
            $this->abort(403, 'Akses ditolak. Hanya Manager atau Admin yang dapat mengakses modul approval.');
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
