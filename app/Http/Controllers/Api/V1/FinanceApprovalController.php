<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\TransactionResource;
use App\Services\Api\FinanceApprovalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FinanceApprovalController extends Controller
{
    public function __construct(
        private readonly FinanceApprovalService $approvalService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'business_status' => ['sometimes', 'in:DRAFT,SUBMITTED,APPROVED,REJECTED,NEEDS_REVIEW'],
            'period_id' => ['sometimes', 'string'],
            'coop_id' => ['sometimes', 'string'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        $paginator = $this->approvalService->listForReviewer($request->user(), $filters);

        return response()->json([
            'success' => true,
            'message' => 'Daftar transaksi menunggu approval berhasil diambil.',
            'data' => [
                'items' => TransactionResource::collection($paginator->items()),
                'total' => $paginator->total(),
                'per_page' => $paginator->perPage(),
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }

    public function show(Request $request, string $transaction): JsonResponse
    {
        $model = $this->approvalService->findForReviewer($request->user(), $transaction);

        return response()->json([
            'success' => true,
            'message' => 'Detail transaksi berhasil diambil.',
            'data' => new TransactionResource($model),
        ]);
    }

    public function store(Request $request, string $transaction): JsonResponse
    {
        $validated = $request->validate([
            'action' => ['required', 'in:approve,reject'],
            'rejection_reason' => ['required_if:action,reject', 'nullable', 'string', 'min:3', 'max:500'],
        ]);

        $model = $this->approvalService->findForReviewer($request->user(), $transaction);
        $updated = $this->approvalService->review(
            $request->user(),
            $model,
            $validated['action'],
            $validated['rejection_reason'] ?? null,
        );

        $message = $validated['action'] === 'approve'
            ? 'Transaksi berhasil disetujui.'
            : 'Transaksi berhasil ditolak.';

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => new TransactionResource($updated),
        ]);
    }
}
