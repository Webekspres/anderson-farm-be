<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Period\SyncPeriodInvestorRequest;
use App\Http\Resources\Api\V1\PeriodInvestorResource;
use App\Models\PeriodInvestor;
use App\Models\ProductionPeriod;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class PeriodInvestorController extends Controller
{
    public function index($period_id): JsonResponse
    {
        $period = ProductionPeriod::find($period_id);

        if (! $period) {
            return response()->json([
                'success' => false,
                'message' => 'Periode tidak ditemukan.',
                'data' => null,
            ], 404);
        }

        $items = PeriodInvestor::query()
            ->with('user:id,name')
            ->where('period_id', $period_id)
            ->orderByDesc('created_at_client')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Daftar investor periode berhasil diambil.',
            'data' => PeriodInvestorResource::collection($items),
        ]);
    }

    public function sync(SyncPeriodInvestorRequest $request, $period_id): JsonResponse
    {
        $validated = $request->validated();
        $period = ProductionPeriod::find($period_id);
        if (! $period) {
            return response()->json([
                'success' => false,
                'message' => 'Periode tidak ditemukan.',
                'data' => null,
            ], 404);
        }

        $investors = $validated['investors'] ?? [];

        $result = DB::transaction(function () use ($period_id, $investors) {
            // Hapus semua investor lama
            PeriodInvestor::where('period_id', $period_id)->forceDelete();
            $now = now();
            $created = [];
            foreach ($investors as $data) {
                $created[] = PeriodInvestor::create([
                    'period_id' => $period_id,
                    'user_id' => $data['user_id'],
                    'profit_share_percentage' => $data['profit_share_percentage'],
                    'initial_investment' => $data['initial_investment'] ?? null,
                    'sync_status' => 'PENDING_SYNC',
                    'created_at_client' => $now,
                    'updated_at_client' => $now,
                ]);
            }

            return $created;
        });

        return response()->json([
            'success' => true,
            'message' => 'Investor periode berhasil disinkronisasi.',
            'data' => PeriodInvestorResource::collection($result),
        ], 200);
    }
}
