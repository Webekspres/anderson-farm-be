<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Approval\IndexDailyActivityApprovalRequest;
use App\Http\Requests\Api\V1\Approval\ReviewDailyActivityApprovalRequest;
use App\Http\Requests\Api\V1\Approval\ShowDailyActivityApprovalRequest;
use App\Http\Resources\Api\V1\DailyActivityHeaderResource;
use App\Services\Api\DailyActivityApprovalService;
use Illuminate\Http\JsonResponse;

class DailyActivityApprovalController extends Controller
{
    public function __construct(
        private readonly DailyActivityApprovalService $approvalService,
    ) {
        $this->middleware('auth:sanctum');
    }

    public function index(IndexDailyActivityApprovalRequest $request): JsonResponse
    {
        $paginator = $this->approvalService->listForReviewer(
            $request->user(),
            $request->validated(),
        );

        return response()->json([
            'success' => true,
            'message' => 'Daftar laporan menunggu approval berhasil diambil.',
            'data' => [
                'items' => DailyActivityHeaderResource::collection($paginator->items()),
                'total' => $paginator->total(),
                'per_page' => $paginator->perPage(),
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }

    public function show(ShowDailyActivityApprovalRequest $request, string $daily_activity): JsonResponse
    {
        $header = $this->approvalService->findForReviewer($request->user(), $daily_activity);

        return response()->json([
            'success' => true,
            'message' => 'Detail laporan harian berhasil diambil.',
            'data' => new DailyActivityHeaderResource($header),
        ]);
    }

    public function store(ReviewDailyActivityApprovalRequest $request, string $daily_activity): JsonResponse
    {
        $header = $this->approvalService->findForReviewer($request->user(), $daily_activity);

        $validated = $request->validated();
        $updatedHeader = $this->approvalService->review(
            $request->user(),
            $header,
            $validated['action'],
            $validated['rejection_reason'] ?? null,
        );

        $message = $validated['action'] === 'approve'
            ? 'Laporan harian berhasil disetujui.'
            : 'Laporan harian berhasil ditolak.';

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => new DailyActivityHeaderResource($updatedHeader),
        ]);
    }
}
