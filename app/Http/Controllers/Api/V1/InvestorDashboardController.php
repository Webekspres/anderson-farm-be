<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\PeriodInvestor;
use App\Models\ProductionPeriod;
use App\Models\Rhpp;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InvestorDashboardController extends Controller
{
    /**
     * GET /api/v1/investor/dashboard
     *
     * Ringkasan ROI ringan untuk investor yang sedang login.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user === null) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
                'data' => null,
            ], 401);
        }

        if ($user->role !== 'investor' && $user->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Hanya investor (atau admin) yang dapat mengakses dashboard investor.',
                'data' => null,
            ], 403);
        }

        $assignmentsQuery = PeriodInvestor::query()
            ->with(['period:id,period_code,status,start_date,end_date,closed_at'])
            ->whereNull('deleted_at');

        if ($user->role === 'investor') {
            $assignmentsQuery->where('user_id', $user->id);
        }

        $assignments = $assignmentsQuery->get();

        $periodIds = $assignments->pluck('period_id')->unique()->values();
        $rhpps = Rhpp::query()
            ->whereIn('period_id', $periodIds)
            ->get()
            ->keyBy('period_id');

        $items = $assignments->map(function (PeriodInvestor $assignment) use ($rhpps) {
            /** @var ProductionPeriod|null $period */
            $period = $assignment->period;
            $rhpp = $rhpps->get($assignment->period_id);
            $isPublished = $rhpp?->publish_status === 'PUBLISHED';

            $initial = (float) ($assignment->initial_investment ?? 0);
            $dividend = (float) ($assignment->final_dividend_amount ?? 0);
            $roiPercent = $initial > 0 ? round((($dividend - $initial) / $initial) * 100, 2) : null;

            return [
                'period_id' => $assignment->period_id,
                'period_code' => $period?->period_code,
                'period_status' => $period?->status,
                'profit_share_percentage' => $assignment->profit_share_percentage,
                'initial_investment' => $initial,
                'final_dividend_amount' => $dividend,
                'is_paid' => (bool) $assignment->is_paid,
                'roi_percent' => $roiPercent,
                'rhpp_net_profit' => $isPublished ? $rhpp?->net_profit : null,
                'rhpp_publish_status' => $isPublished ? $rhpp?->publish_status : null,
            ];
        })->values();

        $totalInvested = $items->sum('initial_investment');
        $totalDividend = $items->sum('final_dividend_amount');

        return response()->json([
            'success' => true,
            'message' => 'Dashboard investor berhasil diambil.',
            'data' => [
                'summary' => [
                    'period_count' => $items->count(),
                    'total_invested' => $totalInvested,
                    'total_dividend' => $totalDividend,
                    'net_roi_amount' => $totalDividend - $totalInvested,
                ],
                'periods' => $items,
            ],
        ]);
    }
}
