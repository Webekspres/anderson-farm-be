<?php

use App\Models\TransactionCategory;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

describe('TransactionCategory API', function () {
    beforeEach(function () {
        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user, ['*']);
    });

    it('can list transaction categories', function () {
        TransactionCategory::factory()->count(3)->create();
        $response = $this->getJson('/api/v1/transaction-categories');
        $response->assertOk()->assertJsonStructure([
            'data' => [
                '*' => ['id', 'server_id', 'version', 'name', 'type', 'is_active', 'sync_status', 'created_at_client', 'created_at_server', 'updated_at_client', 'updated_at_server', 'deleted_at'],
            ],
            'meta' => ['current_page', 'per_page', 'total', 'last_page'],
        ]);
    });

    it('can create a transaction category', function () {
        $data = TransactionCategory::factory()->make()->toArray();
        $response = $this->postJson('/api/v1/transaction-categories', $data);
        $response->assertCreated();
        $this->assertDatabaseHas('transaction_categories', ['name' => $data['name']]);
    });

    it('validates required fields on create', function () {
        $response = $this->postJson('/api/v1/transaction-categories', []);
        $response->assertStatus(422)->assertJsonValidationErrors(['name', 'type', 'created_at_client', 'updated_at_client']);
    });

    it('can show a transaction category', function () {
        $category = TransactionCategory::factory()->create();
        $response = $this->getJson("/api/v1/transaction-categories/{$category->id}");
        $response->assertOk()->assertJson(['data' => ['id' => $category->id]]);
    });

    it('can update a transaction category', function () {
        $category = TransactionCategory::factory()->create();
        $newName = 'Updated Name';
        $response = $this->patchJson("/api/v1/transaction-categories/{$category->id}", [
            'name' => $newName,
            'type' => $category->type,
            'is_active' => $category->is_active,
            'sync_status' => $category->sync_status,
            'created_at_client' => $category->created_at_client->toDateTimeString(),
            'created_at_server' => $category->created_at_server?->toDateTimeString(),
            'updated_at_client' => now()->toDateTimeString(),
            'updated_at_server' => $category->updated_at_server?->toDateTimeString(),
        ]);
        $response->assertOk()->assertJson(['data' => ['name' => $newName]]);
        $this->assertDatabaseHas('transaction_categories', ['id' => $category->id, 'name' => $newName]);
    });

    it('validates unique name on update', function () {
        $cat1 = TransactionCategory::factory()->create(['name' => 'A']);
        $cat2 = TransactionCategory::factory()->create(['name' => 'B']);
        $response = $this->patchJson("/api/v1/transaction-categories/{$cat2->id}", [
            'name' => 'A',
            'type' => $cat2->type,
            'is_active' => $cat2->is_active,
            'sync_status' => $cat2->sync_status,
            'created_at_client' => $cat2->created_at_client->toDateTimeString(),
            'created_at_server' => $cat2->created_at_server?->toDateTimeString(),
            'updated_at_client' => now()->toDateTimeString(),
            'updated_at_server' => $cat2->updated_at_server?->toDateTimeString(),
        ]);
        $response->assertStatus(422)->assertJsonValidationErrors(['name']);
    });

    it('can soft delete a transaction category', function () {
        $category = TransactionCategory::factory()->create();
        $response = $this->deleteJson("/api/v1/transaction-categories/{$category->id}");
        $response->assertNoContent();
        $this->assertSoftDeleted('transaction_categories', ['id' => $category->id]);
    });
});
