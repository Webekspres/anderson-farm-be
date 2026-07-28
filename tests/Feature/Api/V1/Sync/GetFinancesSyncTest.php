<?php

use App\Models\Coop;
use App\Models\CoopFloor;
use App\Models\CoopUserAssignment;
use App\Models\EmployeeSalary;
use App\Models\ProductionPeriod;
use App\Models\Transaction;
use App\Models\TransactionCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// ──────────────────────────────────────────────────────────────
// Helpers
// ──────────────────────────────────────────────────────────────

/**
 * Buat state lengkap: Coop → Floor → Period + assignment user.
 * Mengembalikan period yang sudah terbentuk.
 */
function createFinancePeriodForUser(User $user): ProductionPeriod
{
    $coop = Coop::factory()->create();
    $floor = CoopFloor::factory()->create(['coop_id' => $coop->id]);

    CoopUserAssignment::factory()->create([
        'user_id' => $user->id,
        'coop_id' => $coop->id,
    ]);

    return ProductionPeriod::factory()->create([
        'floor_id' => $floor->id,
        'pic_id' => $user->id,
        'status' => 'active',
    ]);
}

describe('GET /api/v1/sync/finances', function () {

    // ── Test 1: ABK Privacy — transactions empty, hanya gaji sendiri ──

    it('Test 1 — ABK hanya mendapat gaji sendiri dan transactions array kosong', function () {
        $abk = User::factory()->create(['role' => 'abk']);
        $otherAbk = User::factory()->create(['role' => 'abk']);
        $period = createFinancePeriodForUser($abk);
        $category = TransactionCategory::factory()->create();

        // Buat transaksi di periode (ABK seharusnya TIDAK melihatnya)
        Transaction::factory()->create([
            'period_id' => $period->id,
            'user_id' => $abk->id,
            'category_id' => $category->id,
        ]);

        // Gaji milik $abk sendiri
        $ownSalary = EmployeeSalary::factory()->create([
            'period_id' => $period->id,
            'employee_id' => $abk->id,
        ]);

        // Gaji milik ABK lain (tidak boleh muncul)
        EmployeeSalary::factory()->create([
            'period_id' => $period->id,
            'employee_id' => $otherAbk->id,
        ]);

        $response = $this->actingAs($abk)->getJson('/api/v1/sync/finances');

        $response->assertOk();
        $response->assertJsonStructure([
            'success',
            'current_server_timestamp',
            'data' => ['transactions', 'employee_salaries'],
        ]);

        // Transactions harus KOSONG
        $response->assertJsonCount(0, 'data.transactions');

        // Employee salaries hanya berisi milik ABK sendiri
        $response->assertJsonCount(1, 'data.employee_salaries');
        $response->assertJsonPath('data.employee_salaries.0.id', $ownSalary->id);
    });

    // ── Test 2: PIC Access — mendapat transaksi periode miliknya ──

    it('Test 2 — PIC mendapat transactions dari periode di coop yang diassign', function () {
        $pic = User::factory()->create(['role' => 'pic']);
        $period = createFinancePeriodForUser($pic);
        $category = TransactionCategory::factory()->create();

        // Buat transaksi di period PIC
        $tx = Transaction::factory()->create([
            'period_id' => $period->id,
            'user_id' => $pic->id,
            'category_id' => $category->id,
        ]);

        // Buat transaksi di period LAIN (tidak diassign ke PIC ini)
        $otherCoop = Coop::factory()->create();
        $otherFloor = CoopFloor::factory()->create(['coop_id' => $otherCoop->id]);
        $otherPeriod = ProductionPeriod::factory()->create(['floor_id' => $otherFloor->id]);
        Transaction::factory()->create([
            'period_id' => $otherPeriod->id,
            'category_id' => $category->id,
        ]);

        $response = $this->actingAs($pic)->getJson('/api/v1/sync/finances');

        $response->assertOk();

        // Hanya 1 transaksi (milik period PIC, bukan period orang lain)
        $response->assertJsonCount(1, 'data.transactions');
        $response->assertJsonPath('data.transactions.0.id', $tx->id);
    });

    it('exposes type and category_name from category on pulled transactions', function () {
        $admin = User::factory()->create(['role' => 'admin']);
        $period = createFinancePeriodForUser($admin);

        $incomeCategory = TransactionCategory::factory()->create([
            'name' => 'Penjualan Ayam',
            'type' => 'INCOME',
        ]);
        $expenseCategory = TransactionCategory::factory()->create([
            'name' => 'Pakan',
            'type' => 'EXPENSE',
        ]);

        $incomeTx = Transaction::factory()->create([
            'period_id' => $period->id,
            'user_id' => $admin->id,
            'category_id' => $incomeCategory->id,
        ]);
        $expenseTx = Transaction::factory()->create([
            'period_id' => $period->id,
            'user_id' => $admin->id,
            'category_id' => $expenseCategory->id,
        ]);

        $response = $this->actingAs($admin)->getJson('/api/v1/sync/finances');

        $response->assertOk();
        $response->assertJsonCount(2, 'data.transactions');

        $items = collect($response->json('data.transactions'));
        $incomePayload = $items->firstWhere('id', $incomeTx->id);
        $expensePayload = $items->firstWhere('id', $expenseTx->id);

        expect($incomePayload)
            ->type->toBe('INCOME')
            ->category_name->toBe('Penjualan Ayam')
            ->and($incomePayload['transaction_date'])->not->toBeNull()
            ->and($expensePayload)
            ->type->toBe('EXPENSE')
            ->category_name->toBe('Pakan');
    });

    // ── Test 3: Delta Sync — hanya data lebih baru dari last_sync_timestamp ──

    it('Test 3 — Delta sync memfilter transaksi berdasarkan last_sync_timestamp', function () {
        $pic = User::factory()->create(['role' => 'pic']);
        $period = createFinancePeriodForUser($pic);
        $category = TransactionCategory::factory()->create();

        // Tentukan cutoff yang jelas
        $cutoff = now()->subHour();

        // Transaksi lama — updated_at_server sebelum cutoff
        Transaction::factory()->create([
            'period_id' => $period->id,
            'category_id' => $category->id,
            'updated_at_server' => $cutoff->copy()->subMinutes(30)->toDateTimeString(),
        ]);

        // Transaksi baru — updated_at_server setelah cutoff
        $newTx = Transaction::factory()->create([
            'period_id' => $period->id,
            'category_id' => $category->id,
            'updated_at_server' => now()->toDateTimeString(),
        ]);

        $response = $this->actingAs($pic)->getJson(
            '/api/v1/sync/finances?last_sync_timestamp='.urlencode($cutoff->toDateTimeString())
        );

        $response->assertOk();

        // Hanya transaksi baru yang lolos filter
        $response->assertJsonCount(1, 'data.transactions');
        $response->assertJsonPath('data.transactions.0.id', $newTx->id);
    });

    it('returns 401 when not authenticated', function () {
        $this->getJson('/api/v1/sync/finances')->assertUnauthorized();
    });
});
