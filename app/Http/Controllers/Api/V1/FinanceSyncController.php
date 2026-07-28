<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Sync\SyncGetFinancesRequest;
use App\Http\Requests\Api\V1\Sync\SyncPostFinanceRequest;
use App\Http\Resources\Api\V1\EmployeeSalaryResource;
use App\Http\Resources\Api\V1\TransactionResource;
use App\Models\CoopUserAssignment;
use App\Models\ProductionPeriod;
use App\Models\Transaction;
use App\Models\TransactionCategory;
use App\Models\User;
use App\Services\Api\FinanceSyncService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class FinanceSyncController extends Controller
{
    public function __construct(
        private readonly FinanceSyncService $financeService,
    ) {}

    /**
     * GET /api/v1/sync/finances
     *
     * Pull delta transaksi dan status gaji berdasarkan scope coop user.
     * Role ABK hanya mendapat gaji milik sendiri, tanpa data transaksi.
     */
    public function index(SyncGetFinancesRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();
        $lastSyncTimestamp = $request->validated('last_sync_timestamp');

        $payload = $this->financeService->getPullPayload($user, $lastSyncTimestamp);

        return response()->json([
            'success' => true,
            'current_server_timestamp' => now()->toIso8601String(),
            'data' => [
                'transactions' => TransactionResource::collection($payload['transactions']),
                'employee_salaries' => EmployeeSalaryResource::collection($payload['employee_salaries']),
            ],
        ]);
    }

    /**
     * POST /api/v1/sync/finances
     *
     * Push bulk transaksi pengeluaran/pemasukan dari SQLite ke server.
     * Role ABK dan Investor ditolak di Form Request; PIC butuh assignment kandang.
     */
    public function store(SyncPostFinanceRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        $payloadTransactions = $request->validated('transactions');
        $serverTimestamp = now();
        $syncResults = [];

        // Proses setiap transaksi di luar DB transaction agar setiap item
        // bisa memiliki status individual (SUCCESS / FAILED) tanpa rollback semua.
        foreach ($payloadTransactions as $transactionPayload) {
            $syncResults[] = $this->processTransaction($transactionPayload, $user, $serverTimestamp);
        }

        $successCount = collect($syncResults)->where('status', 'SUCCESS')->count();
        $failedCount = collect($syncResults)->where('status', 'FAILED')->count();

        $messageParts = [];
        if ($successCount > 0) {
            $messageParts[] = "{$successCount} berhasil disinkronkan";
        }
        if ($failedCount > 0) {
            $messageParts[] = "{$failedCount} gagal";
        }

        return response()->json([
            'success' => true,
            'message' => 'Sinkronisasi selesai. '.implode(', ', $messageParts).'.',
            'server_timestamp' => $serverTimestamp->toIso8601String(),
            'data' => [
                'sync_results' => $syncResults,
            ],
        ]);
    }

    /**
     * Memproses satu item transaksi dari payload:
     * 1. Validasi tipe EXPENSE/INCOME vs kategori
     * 2. Validasi periode aktif
     * 3. Cek otorisasi assignment kandang (untuk role PIC)
     * 4. Upsert dengan idempotency untuk status APPROVED
     *
     * @param  array<string, mixed>  $payload
     * @return array{id: string, status: string, server_id: int|null, error_message: string|null}
     */
    private function processTransaction(
        array $payload,
        User $user,
        Carbon $serverTimestamp,
    ): array {
        $transactionId = $payload['id'];
        $payloadType = strtoupper((string) $payload['type']);

        // ── Step 1: Kategori harus cocok dengan tipe payload ──
        $category = TransactionCategory::query()->find($payload['category_id']);

        if (! $category) {
            return [
                'id' => $transactionId,
                'status' => 'FAILED',
                'server_id' => null,
                'error_message' => 'Kategori transaksi tidak ditemukan.',
            ];
        }

        if (strtoupper((string) $category->type) !== $payloadType) {
            return [
                'id' => $transactionId,
                'status' => 'FAILED',
                'server_id' => null,
                'error_message' => 'Tipe transaksi tidak cocok dengan kategori yang dipilih.',
            ];
        }

        // ── Step 2: Validasi periode aktif ──
        $period = ProductionPeriod::with('floor')->find($payload['period_id']);

        if (! $period || $period->status !== 'active') {
            return [
                'id' => $transactionId,
                'status' => 'FAILED',
                'server_id' => null,
                'error_message' => 'Periode tidak ditemukan atau sudah ditutup. Tidak dapat menambah transaksi.',
            ];
        }

        // ── Step 3: Security check untuk role PIC ──
        // Admin, Manager, dan Finance memiliki akses global; PIC harus assignment ke coop.
        if ($user->role === 'pic') {
            $isAssigned = CoopUserAssignment::where('user_id', $user->id)
                ->where('coop_id', $period->floor->coop_id)
                ->whereNull('deleted_at')
                ->exists();

            if (! $isAssigned) {
                return [
                    'id' => $transactionId,
                    'status' => 'FAILED',
                    'server_id' => null,
                    'error_message' => 'Anda tidak memiliki akses ke kandang pada periode ini.',
                ];
            }
        }

        // ── Step 4: Idempotency — Cek apakah sudah ada di server ──
        $existingTransaction = Transaction::withTrashed()->find($transactionId);

        // Jika sudah APPROVED, hanya boleh update receipt_path_local
        if ($existingTransaction && $existingTransaction->business_status === 'APPROVED') {
            $existingTransaction->update([
                'receipt_path_local' => $payload['receipt_image_path_local'] ?? $existingTransaction->receipt_path_local,
                'updated_at_server' => $serverTimestamp,
            ]);

            return [
                'id' => $existingTransaction->id,
                'status' => 'SUCCESS',
                'server_id' => $existingTransaction->server_id,
                'error_message' => null,
            ];
        }

        // ── Step 5: Upsert — Buat atau perbarui transaksi ──
        $transaction = Transaction::withTrashed()->updateOrCreate(
            ['id' => $transactionId],
            [
                'period_id' => $payload['period_id'],
                'user_id' => $user->id,
                'category_id' => $payload['category_id'],
                'date' => $payload['transaction_date'],
                'amount' => $payload['amount'],
                'description' => $payload['description'] ?? null,
                'receipt_path_local' => $payload['receipt_image_path_local'] ?? null,
                'expense_scope' => $payload['expense_scope'] ?? 'FLOOR_SPECIFIC',
                'coop_id' => isset($payload['expense_scope']) && $payload['expense_scope'] === 'COOP_SHARED' ? ($payload['coop_id'] ?? null) : null,
                'business_status' => $existingTransaction?->business_status ?? 'DRAFT',
                'sync_status' => 'SYNCED',
                'created_at_client' => $payload['created_at_client'],
                'created_at_server' => $existingTransaction?->created_at_server ?? $serverTimestamp,
                'updated_at_client' => $payload['updated_at_client'],
                'updated_at_server' => $serverTimestamp,
                'deleted_at' => null, // Restore jika sebelumnya soft-deleted
            ],
        );

        return [
            'id' => $transaction->id,
            'status' => 'SUCCESS',
            'server_id' => $transaction->server_id,
            'error_message' => null,
        ];
    }
}
