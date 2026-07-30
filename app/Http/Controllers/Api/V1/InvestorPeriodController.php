<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Investor\ShowInvestorPeriodRequest;
use App\Models\PeriodInvestor;
use App\Models\Rhpp;
use Illuminate\Http\JsonResponse;

class InvestorPeriodController extends Controller
{
    /**
     * GET /api/v1/investor/periods/{period_id}
     *
     * Detail periode investasi + ringkasan RHPP published (jika ada).
     */
    public function show(ShowInvestorPeriodRequest $request, string $period_id): JsonResponse
    {
        $user = $request->user();

        $assignmentQuery = PeriodInvestor::query()
            ->with(['period:id,period_code,status,start_date,end_date,closed_at'])
            ->where('period_id', $period_id)
            ->whereNull('deleted_at');

        if ($user?->role === 'investor') {
            $assignmentQuery->where('user_id', $user->id);
        }

        $assignment = $assignmentQuery->first();

        if ($assignment === null) {
            return response()->json([
                'success' => false,
                'message' => 'Periode investasi tidak ditemukan atau tidak terkait akun Anda.',
                'data' => null,
            ], 404);
        }

        $period = $assignment->period;
        $rhpp = Rhpp::query()
            ->where('period_id', $period_id)
            ->where('publish_status', 'PUBLISHED')
            ->whereNull('deleted_at')
            ->first();

        $initial = (float) ($assignment->initial_investment ?? 0);
        $dividend = (float) ($assignment->final_dividend_amount ?? 0);
        $roiPercent = $initial > 0 ? round((($dividend - $initial) / $initial) * 100, 2) : null;

        return response()->json([
            'success' => true,
            'message' => 'Detail periode investasi berhasil diambil.',
            'data' => [
                'period_id' => $assignment->period_id,
                'period_code' => $period?->period_code,
                'period_status' => $period?->status,
                'start_date' => $period?->start_date?->toDateString(),
                'end_date' => $period?->end_date?->toDateString(),
                'closed_at' => $period?->closed_at?->toIso8601String(),
                'profit_share_percentage' => $assignment->profit_share_percentage,
                'initial_investment' => $initial,
                'final_dividend_amount' => $dividend,
                'is_paid' => (bool) $assignment->is_paid,
                'roi_percent' => $roiPercent,
                'rhpp' => $rhpp === null ? null : [
                    'id' => $rhpp->id,
                    'publish_status' => $rhpp->publish_status,
                    'total_income' => $rhpp->total_income,
                    'total_expense' => $rhpp->total_expense,
                    'net_profit' => $rhpp->net_profit,
                    'published_at' => $rhpp->updated_at_server?->toIso8601String()
                        ?? $rhpp->updated_at?->toIso8601String(),
                ],
            ],
        ]);
    }
}
