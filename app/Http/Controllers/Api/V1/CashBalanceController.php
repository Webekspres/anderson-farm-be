<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\BusinessStatus;
use App\Http\Controllers\Controller;
use App\Models\CoopUserAssignment;
use App\Models\ProductionPeriod;
use App\Models\Transaction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * GET /api/v1/finances/cash-balance?period_id=...
 *
 * Authoritative server-side cash position for a period. Counts every
 * non-rejected, non-deleted transaction (matching the client's local
 * aggregate), split by category type.
 *
 * ponytail: opening_balance is always 0 — there is no cross-period carryover
 * model yet. Upgrade path: persist a per-period opening balance and add it here.
 */
class CashBalanceController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'period_id' => ['required', 'string'],
        ]);

        $user = $request->user();
        $periodId = $validated['period_id'];

        if (! in_array($user->role, ['admin', 'manager', 'finance', 'pic'], true)) {
            $this->abort(403, 'Role Anda tidak dapat melihat saldo kas.');
        }

        $period = ProductionPeriod::with('floor')->find($periodId);
        if (! $period) {
            $this->abort(404, 'Periode tidak ditemukan.');
        }

        if ($user->role === 'pic') {
            $isAssigned = CoopUserAssignment::where('user_id', $user->id)
                ->where('coop_id', $period->floor->coop_id)
                ->whereNull('deleted_at')
                ->exists();

            if (! $isAssigned) {
                $this->abort(403, 'Anda tidak memiliki akses ke kandang pada periode ini.');
            }
        }

        $base = Transaction::query()
            ->where('period_id', $periodId)
            ->where('business_status', '!=', BusinessStatus::Rejected->value);

        $income = (float) (clone $base)
            ->whereHas('category', fn (Builder $q) => $q->whereRaw('UPPER(type) = ?', ['INCOME']))
            ->sum('amount');

        $expense = (float) (clone $base)
            ->whereHas('category', fn (Builder $q) => $q->whereRaw('UPPER(type) = ?', ['EXPENSE']))
            ->sum('amount');

        $openingBalance = 0.0;

        return response()->json([
            'success' => true,
            'message' => 'Saldo kas periode berhasil diambil.',
            'data' => [
                'period_id' => $periodId,
                'opening_balance' => $openingBalance,
                'total_income' => $income,
                'total_expense' => $expense,
                'closing_balance' => $openingBalance + $income - $expense,
            ],
        ]);
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
