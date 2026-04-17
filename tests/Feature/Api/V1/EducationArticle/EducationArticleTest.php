<?php

use App\Models\User;
use App\Models\EducationArticle;
use Laravel\Sanctum\Sanctum;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

describe('EducationArticle API', function () {
    beforeEach(function () {
        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user, ['*']);
    });

    it('successfully creates data with image_url and image_path_local', function () {
        $data = EducationArticle::factory()->make([
            'image_url' => 'https://example.com/image.jpg',
            'image_path_local' => '/images/local.jpg',
        ])->toArray();
        $response = $this->postJson('/api/v1/education-articles', $data);
        $response->assertCreated()->assertJson([
            'success' => true,
            'data' => [
                'image_url' => 'https://example.com/image.jpg',
                'image_path_local' => '/images/local.jpg',
            ],
        ]);
        $this->assertDatabaseHas('education_articles', [
            'name' => $data['name'],
            'image_url' => 'https://example.com/image.jpg',
            'image_path_local' => '/images/local.jpg',
        ]);
    });

    it('returns 422 if name is empty', function () {
        $data = EducationArticle::factory()->make(['name' => ''])->toArray();
        $response = $this->postJson('/api/v1/education-articles', $data);
        $response->assertStatus(422)->assertJsonValidationErrors(['name']);
    });

    it('returns 422 if link_url is not a valid url', function () {
        $data = EducationArticle::factory()->make(['link_url' => 'not-a-url'])->toArray();
        $response = $this->postJson('/api/v1/education-articles', $data);
        $response->assertStatus(422)->assertJsonValidationErrors(['link_url']);
    });

    it('successfully updates only the name', function () {
        $article = EducationArticle::factory()->create();
        $response = $this->patchJson("/api/v1/education-articles/{$article->id}", [
            'name' => 'Updated Name',
        ]);
        $response->assertOk()->assertJson([
            'data' => ['name' => 'Updated Name'],
        ]);
        $this->assertDatabaseHas('education_articles', [
            'id' => $article->id,
            'name' => 'Updated Name',
        ]);
    });

    it('returns 404 if updating with invalid uuid', function () {
        $response = $this->patchJson('/api/v1/education-articles/invalid-uuid', [
            'name' => 'X',
        ]);
        $response->assertStatus(404);
    });

    it('successfully soft deletes the article', function () {
        $article = EducationArticle::factory()->create();
        $response = $this->deleteJson("/api/v1/education-articles/{$article->id}");
        $response->assertOk()->assertJson(['success' => true]);
        $this->assertSoftDeleted('education_articles', ['id' => $article->id]);
    });

    it('returns 401 if not authenticated', function () {
        $this->refreshApplication();
        $response = $this->postJson('/api/v1/education-articles', []);
        $response->assertStatus(401);
    });
});
