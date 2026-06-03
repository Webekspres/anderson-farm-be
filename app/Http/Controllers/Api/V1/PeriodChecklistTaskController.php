<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Period\IndexPeriodChecklistTaskRequest;
use App\Http\Resources\Api\V1\ChecklistTaskResource;
use App\Models\ChecklistTask;
use Illuminate\Http\JsonResponse;

class PeriodChecklistTaskController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    public function index(IndexPeriodChecklistTaskRequest $request): JsonResponse
    {
        $period = $request->period();

        $tasks = ChecklistTask::query()
            ->where('period_id', $period->id)
            ->where('is_active', true)
            ->orderBy('created_at_client')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Daftar tugas checklist SOP berhasil diambil.',
            'data' => ChecklistTaskResource::collection($tasks),
        ]);
    }
}
