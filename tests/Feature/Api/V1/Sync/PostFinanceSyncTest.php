<?php

use App\Models\Coop;
use App\Models\CoopFloor;
use App\Models\CoopUserAssignment;
use App\Models\ProductionPeriod;
use App\Models\Transaction;
use App\Models\TransactionCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

// ──────────────────────────────────────────────────────────────
// Helpers
// ──────────────────────────────────────────────────────────────

/**
 * Buat state lengkap: Coop → CoopFloor → ProductionPeriod (active).
 */
function createActivePeriod(string $picId): ProductionPeriod
{
    $coop  = Coop::factory()->create();
    $floor = CoopFloor::factory()->create(['coop_id' => $coop->id]);

    return ProductionPeriod::factory()->create([
        'floor_id' => $floor->id,
        'pic_id'   => $picId,
        'status'   => 'active',
    ]);
}

/**
 * Buat payload standar satu item transaksi EXPENSE.
 */
function buildTransactionPayload(
    string $periodId,
    string $categoryId,
    array  $overrides = [],
): array {
    return array_merge([
        'id'                       => Str::uuid()->toString(),
        'period_id'                => $periodId,
        'category_id'              => $categoryId,
        'transaction_date'         => '2026-04-27T10:00:00Z',
        'type'                     => 'EXPENSE',
        'amount'                   => 150000.00,
        'description'              => 'Beli solar genset',
        'receipt_image_path_local' => '/images/receipts/xxx.jpg',
        'created_at_client'        => '2026-04-27T10:05:00Z',
        'updated_at_client'        => '2026-04-27T10:05:00Z',
    ], $overrides);
}

// ──────────────────────────────────────────────────────────────
// Test Suite
// ──────────────────────────────────────────────────────────────

describe('POST /api/v1/sync/finances', function () {

    // ── Happy Path ────────────────────────────────────────────

    it('allows PIC with valid assignment to push EXPENSE — SUCCESS', function () {
        $pic      = User::factory()->create(['role' => 'pic']);
        $period   = createActivePeriod($pic->id);
        $category = TransactionCategory::factory()->create(['type' => 'EXPENSE']);

        // Buat assignment PIC ke coop yang dimiliki floor period ini
        CoopUserAssignment::factory()->create([
            'user_id' => $pic->id,
            'coop_id' => $period->floor->coop_id,
        ]);

        $transactionId = Str::uuid()->toString();
        $payload = [
            'sync_timestamp' => now()->toIso8601String(),
            'transactions'   => [
                buildTransactionPayload($period->id, $category->id, ['id' => $transactionId]),
            ],
        ];

        $response = $this->actingAs($pic)->postJson('/api/v1/sync/finances', $payload);

        $response->assertOk();
        $response->assertJsonStructure([
            'success',
            'message',
            'server_timestamp',
            'data' => [
                'sync_results' => [
                    '*' => ['id', 'status', 'server_id', 'error_message'],
                ],
            ],
        ]);
        $response->assertJsonPath('data.sync_results.0.id', $transactionId);
        $response->assertJsonPath('data.sync_results.0.status', 'SUCCESS');

        $this->assertDatabaseHas('transactions', [
            'id'          => $transactionId,
            'sync_status' => 'SYNCED',
            'user_id'     => $pic->id,
        ]);
    });

    it('allows manager to push EXPENSE with global access (no assignment needed) — SUCCESS', function () {
        $manager  = User::factory()->create(['role' => 'manager']);
        $pic      = User::factory()->create(['role' => 'pic']);
        $period   = createActivePeriod($pic->id);
        $category = TransactionCategory::factory()->create(['type' => 'EXPENSE']);

        // Manager tidak perlu assignment ke coop

        $payload = [
            'sync_timestamp' => now()->toIso8601String(),
            'transactions'   => [
                buildTransactionPayload($period->id, $category->id),
            ],
        ];

        $response = $this->actingAs($manager)->postJson('/api/v1/sync/finances', $payload);

        $response->assertOk();
        $response->assertJsonPath('data.sync_results.0.status', 'SUCCESS');
    });

    // ── RBAC ─────────────────────────────────────────────────

    it('returns 403 when user role is abk', function () {
        $abk = User::factory()->create(['role' => 'abk']);

        $payload = [
            'sync_timestamp' => now()->toIso8601String(),
            'transactions'   => [
                buildTransactionPayload(Str::uuid()->toString(), Str::uuid()->toString()),
            ],
        ];

        $response = $this->actingAs($abk)->postJson('/api/v1/sync/finances', $payload);

        $response->assertForbidden();
        $response->assertJsonPath('success', false);
    });

    it('returns 403 when user role is investor', function () {
        $investor = User::factory()->create(['role' => 'investor']);

        $payload = [
            'sync_timestamp' => now()->toIso8601String(),
            'transactions'   => [
                buildTransactionPayload(Str::uuid()->toString(), Str::uuid()->toString()),
            ],
        ];

        $response = $this->actingAs($investor)->postJson('/api/v1/sync/finances', $payload);

        $response->assertForbidden();
    });

    // ── Business Logic ───────────────────────────────────────

    it('returns FAILED status for items with type INCOME', function () {
        $pic      = User::factory()->create(['role' => 'pic']);
        $period   = createActivePeriod($pic->id);
        $category = TransactionCategory::factory()->create(['type' => 'INCOME']);

        CoopUserAssignment::factory()->create([
            'user_id' => $pic->id,
            'coop_id' => $period->floor->coop_id,
        ]);

        $payload = [
            'sync_timestamp' => now()->toIso8601String(),
            'transactions'   => [
                buildTransactionPayload($period->id, $category->id, ['type' => 'INCOME']),
            ],
        ];

        $response = $this->actingAs($pic)->postJson('/api/v1/sync/finances', $payload);

        $response->assertOk();
        $response->assertJsonPath('data.sync_results.0.status', 'FAILED');
        $this->assertDatabaseCount('transactions', 0);
    });

    it('returns FAILED status when period is not active (completed)', function () {
        $pic      = User::factory()->create(['role' => 'pic']);
        $coop     = Coop::factory()->create();
        $floor    = CoopFloor::factory()->create(['coop_id' => $coop->id]);
        $period   = ProductionPeriod::factory()->create([
            'floor_id' => $floor->id,
            'pic_id'   => $pic->id,
            'status'   => 'completed', // Period sudah ditutup
        ]);
        $category = TransactionCategory::factory()->create(['type' => 'EXPENSE']);

        $payload = [
            'sync_timestamp' => now()->toIso8601String(),
            'transactions'   => [
                buildTransactionPayload($period->id, $category->id),
            ],
        ];

        $response = $this->actingAs($pic)->postJson('/api/v1/sync/finances', $payload);

        $response->assertOk();
        $response->assertJsonPath('data.sync_results.0.status', 'FAILED');
        $this->assertDatabaseCount('transactions', 0);
    });

    it('returns FAILED when PIC has no coop assignment for the period', function () {
        $pic      = User::factory()->create(['role' => 'pic']);
        $period   = createActivePeriod($pic->id);
        $category = TransactionCategory::factory()->create(['type' => 'EXPENSE']);

        // TIDAK membuat CoopUserAssignment untuk pic ini

        $payload = [
            'sync_timestamp' => now()->toIso8601String(),
            'transactions'   => [
                buildTransactionPayload($period->id, $category->id),
            ],
        ];

        $response = $this->actingAs($pic)->postJson('/api/v1/sync/finances', $payload);

        $response->assertOk();
        $response->assertJsonPath('data.sync_results.0.status', 'FAILED');
        $this->assertDatabaseCount('transactions', 0);
    });

    // ── Idempotency ──────────────────────────────────────────

    it('only updates receipt_path_local when existing transaction is APPROVED', function () {
        $pic      = User::factory()->create(['role' => 'pic']);
        $period   = createActivePeriod($pic->id);
        $category = TransactionCategory::factory()->create(['type' => 'EXPENSE']);

        CoopUserAssignment::factory()->create([
            'user_id' => $pic->id,
            'coop_id' => $period->floor->coop_id,
        ]);

        // Buat transaksi yang sudah APPROVED di server dengan amount asli
        $originalAmount   = 200000.00;
        $transactionId    = Str::uuid()->toString();
        $existingTransaction = Transaction::create([
            'id'                 => $transactionId,
            'period_id'          => $period->id,
            'user_id'            => $pic->id,
            'category_id'        => $category->id,
            'date'               => now(),
            'amount'             => $originalAmount,
            'business_status'    => 'APPROVED', // Sudah disetujui
            'sync_status'        => 'SYNCED',
            'receipt_path_local' => '/old/path.jpg',
            'created_at_client'  => now(),
            'updated_at_client'  => now(),
        ]);

        // Mobile mengirim ulang dengan amount berbeda dan path baru
        $newReceiptPath = '/new/receipt/path.jpg';
        $payload = [
            'sync_timestamp' => now()->toIso8601String(),
            'transactions'   => [
                buildTransactionPayload($period->id, $category->id, [
                    'id'                       => $transactionId,
                    'amount'                   => 999999.00, // Amount berbeda, harus diabaikan
                    'receipt_image_path_local' => $newReceiptPath,
                ]),
            ],
        ];

        $response = $this->actingAs($pic)->postJson('/api/v1/sync/finances', $payload);

        $response->assertOk();
        $response->assertJsonPath('data.sync_results.0.status', 'SUCCESS');

        // Amount harus tetap TIDAK berubah
        $this->assertDatabaseHas('transactions', [
            'id'                 => $transactionId,
            'amount'             => $originalAmount,
            'receipt_path_local' => $newReceiptPath, // Path boleh berubah
        ]);
    });

    it('successfully upserts on re-sync for non-APPROVED transaction', function () {
        $pic      = User::factory()->create(['role' => 'pic']);
        $period   = createActivePeriod($pic->id);
        $category = TransactionCategory::factory()->create(['type' => 'EXPENSE']);

        CoopUserAssignment::factory()->create([
            'user_id' => $pic->id,
            'coop_id' => $period->floor->coop_id,
        ]);

        $transactionId = Str::uuid()->toString();

        // Sync pertama
        $this->actingAs($pic)->postJson('/api/v1/sync/finances', [
            'sync_timestamp' => now()->toIso8601String(),
            'transactions'   => [
                buildTransactionPayload($period->id, $category->id, [
                    'id'     => $transactionId,
                    'amount' => 50000.00,
                ]),
            ],
        ])->assertOk();

        $this->assertDatabaseHas('transactions', ['id' => $transactionId, 'amount' => 50000.00]);

        // Sync kedua — update amount (status masih DRAFT)
        $response = $this->actingAs($pic)->postJson('/api/v1/sync/finances', [
            'sync_timestamp' => now()->toIso8601String(),
            'transactions'   => [
                buildTransactionPayload($period->id, $category->id, [
                    'id'     => $transactionId,
                    'amount' => 75000.00, // Update amount
                ]),
            ],
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.sync_results.0.status', 'SUCCESS');

        $this->assertDatabaseHas('transactions', ['id' => $transactionId, 'amount' => 75000.00]);
        $this->assertDatabaseCount('transactions', 1); // Tidak duplikat
    });

    // ── Auth ─────────────────────────────────────────────────

    it('returns 401 when not authenticated', function () {
        $response = $this->postJson('/api/v1/sync/finances', [
            'sync_timestamp' => now()->toIso8601String(),
            'transactions'   => [],
        ]);

        $response->assertUnauthorized();
    });

    // ── Validation ───────────────────────────────────────────

    it('returns 422 when transactions array is missing', function () {
        $pic = User::factory()->create(['role' => 'pic']);

        $response = $this->actingAs($pic)->postJson('/api/v1/sync/finances', [
            'sync_timestamp' => now()->toIso8601String(),
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('transactions');
    });

    it('returns 422 when category_id does not exist in DB', function () {
        $pic    = User::factory()->create(['role' => 'pic']);
        $period = createActivePeriod($pic->id);

        $payload = [
            'sync_timestamp' => now()->toIso8601String(),
            'transactions'   => [
                buildTransactionPayload($period->id, Str::uuid()->toString()), // category tidak ada
            ],
        ];

        $response = $this->actingAs($pic)->postJson('/api/v1/sync/finances', $payload);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('transactions.0.category_id');
    });
});
