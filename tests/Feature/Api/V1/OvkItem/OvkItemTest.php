<?php

use App\Models\User;
use App\Models\OvkItem;
use Laravel\Sanctum\Sanctum;
use Illuminate\Foundation\Testing\RefreshDatabase;

describe('OvkItem API', function () {
    beforeEach(function () {
        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user, ['*']);
    });

    it('can list ovk items with page pagination', function () {
        OvkItem::factory()->count(5)->create();
        $response = $this->getJson('/api/v1/ovk-items');
        $response->assertOk()->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'items' => [['id', 'name', 'type', 'unit', 'is_active']],
                'total',
                'per_page',
                'current_page',
                'last_page'
            ]
        ]);
    });

    it('can list ovk items with cursor pagination', function () {
        OvkItem::factory()->count(5)->create();
        $response = $this->getJson('/api/v1/ovk-items?cursor=true');
        $response->assertOk()->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'items',
                'next_cursor',
                'prev_cursor',
                'has_next',
                'has_prev'
            ]
        ]);
    });

    it('can create ovk item', function () {
        $data = OvkItem::factory()->make()->toArray();
        $response = $this->postJson('/api/v1/ovk-items', $data);
        $response->assertCreated()->assertJson(['success' => true]);
        $this->assertDatabaseHas('ovk_items', ['name' => $data['name']]);
    });

    it('returns 422 if name already exists', function () {
        $item = OvkItem::factory()->create();
        $data = OvkItem::factory()->make(['name' => $item->name])->toArray();
        $response = $this->postJson('/api/v1/ovk-items', $data);
        $response->assertStatus(422)->assertJsonValidationErrors(['name']);
    });

    it('returns 422 if type is invalid', function () {
        $data = OvkItem::factory()->make(['type' => 'INVALID'])->toArray();
        $response = $this->postJson('/api/v1/ovk-items', $data);
        $response->assertStatus(422)->assertJsonValidationErrors(['type']);
    });

    it('can update ovk item', function () {
        $item = OvkItem::factory()->create();
        $newName = 'Vitamin C';
        $response = $this->patchJson("/api/v1/ovk-items/{$item->id}", [
            'name' => $newName,
        ]);
        $response->assertOk()->assertJson(['data' => ['name' => $newName]]);
        $this->assertDatabaseHas('ovk_items', ['id' => $item->id, 'name' => $newName]);
    });

    it('returns 422 if updating name to another existing', function () {
        $item1 = OvkItem::factory()->create(['name' => 'A']);
        $item2 = OvkItem::factory()->create(['name' => 'B']);
        $response = $this->patchJson("/api/v1/ovk-items/{$item2->id}", [
            'name' => 'A',
        ]);
        $response->assertStatus(422)->assertJsonValidationErrors(['name']);
    });

    it('can update without changing name (ignore unique)', function () {
        $item = OvkItem::factory()->create(['name' => 'Unik']);
        $response = $this->patchJson("/api/v1/ovk-items/{$item->id}", [
            'name' => 'Unik',
        ]);
        $response->assertOk()->assertJson(['data' => ['name' => 'Unik']]);
    });

    it('returns 404 if updating non-existent id', function () {
        $response = $this->patchJson('/api/v1/ovk-items/invalid-uuid', [
            'name' => 'X'
        ]);
        $response->assertStatus(404);
    });

    it('can soft delete ovk item', function () {
        $item = OvkItem::factory()->create();
        $response = $this->deleteJson("/api/v1/ovk-items/{$item->id}");
        $response->assertOk()->assertJson(['success' => true]);
        $this->assertSoftDeleted('ovk_items', ['id' => $item->id]);
    });

    it('returns 404 if deleting already deleted', function () {
        $item = OvkItem::factory()->create();
        $item->delete();
        $response = $this->deleteJson("/api/v1/ovk-items/{$item->id}");
        $response->assertStatus(404);
    });

    it('returns 401 if not authenticated', function () {
        $this->refreshApplication(); // reset state, tidak ada user
        $response = $this->getJson('/api/v1/ovk-items');
        $response->assertStatus(401);
    });
});
