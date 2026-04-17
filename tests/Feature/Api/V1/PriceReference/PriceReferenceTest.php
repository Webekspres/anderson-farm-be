<?php

use App\Models\User;
use App\Models\PriceReference;
use Laravel\Sanctum\Sanctum;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

describe('PriceReference API', function () {
    beforeEach(function () {
        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user, ['*']);
    });

    it('successfully creates data with image_url and image_path_local', function () {
        $data = PriceReference::factory()->make([
            'image_url' => 'https://example.com/image.jpg',
            'image_path_local' => '/images/local.jpg',
        ])->toArray();
        $response = $this->postJson('/api/v1/price-references', $data);
        $response->assertCreated()->assertJson([
            'success' => true,
            'data' => [
                'image_url' => 'https://example.com/image.jpg',
                'image_path_local' => '/images/local.jpg',
            ],
        ]);
        $this->assertDatabaseHas('price_references', [
            'name' => $data['name'],
            'image_url' => 'https://example.com/image.jpg',
            'image_path_local' => '/images/local.jpg',
        ]);
    });

    it('returns 422 if name is empty', function () {
        $data = PriceReference::factory()->make(['name' => ''])->toArray();
        $response = $this->postJson('/api/v1/price-references', $data);
        $response->assertStatus(422)->assertJsonValidationErrors(['name']);
    });

    it('returns 422 if link_url is not a valid url', function () {
        $data = PriceReference::factory()->make(['link_url' => 'not-a-url'])->toArray();
        $response = $this->postJson('/api/v1/price-references', $data);
        $response->assertStatus(422)->assertJsonValidationErrors(['link_url']);
    });

    it('successfully updates only the name', function () {
        $ref = PriceReference::factory()->create();
        $response = $this->patchJson("/api/v1/price-references/{$ref->id}", [
            'name' => 'Updated Name',
        ]);
        $response->assertOk()->assertJson([
            'data' => ['name' => 'Updated Name'],
        ]);
        $this->assertDatabaseHas('price_references', [
            'id' => $ref->id,
            'name' => 'Updated Name',
        ]);
    });

    it('returns 404 if updating with invalid uuid', function () {
        $response = $this->patchJson('/api/v1/price-references/invalid-uuid', [
            'name' => 'X',
        ]);
        $response->assertStatus(404);
    });

    it('successfully soft deletes the price reference', function () {
        $ref = PriceReference::factory()->create();
        $response = $this->deleteJson("/api/v1/price-references/{$ref->id}");
        $response->assertOk()->assertJson(['success' => true]);
        $this->assertSoftDeleted('price_references', ['id' => $ref->id]);
    });

    it('returns 401 if not authenticated', function () {
        $this->refreshApplication();
        $response = $this->postJson('/api/v1/price-references', []);
        $response->assertStatus(401);
    });
});
