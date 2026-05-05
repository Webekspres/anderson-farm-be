<?php

use App\Models\EducationArticle;
use App\Models\PriceReference;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
});

it('returns all education and price data (full sync)', function () {
    $articles = EducationArticle::factory()->count(2)->create();
    $prices = PriceReference::factory()->count(2)->create();

    $articlesById = $articles->sortBy('id')->values();
    $pricesById = $prices->sortBy('id')->values();

    actingAs($this->user, 'sanctum')
        ->getJson('/api/v1/sync/education')
        ->assertOk()
        ->assertJson([
            'success' => true,
            'data' => [
                'education_articles' => [
                    ['id' => $articlesById[0]->id],
                    ['id' => $articlesById[1]->id],
                ],
                'price_references' => [
                    ['id' => $pricesById[0]->id],
                    ['id' => $pricesById[1]->id],
                ],
            ],
        ])
        ->assertJsonStructure([
            'success',
            'current_server_timestamp',
            'data' => [
                'education_articles' => [
                    '*' => [
                        'id',
                        'server_id',
                        'title',
                        'excerpt',
                        'link_url',
                        'image_url',
                        'image_path_local',
                        'created_at_server',
                        'updated_at_server',
                        'deleted_at',
                    ],
                ],
                'price_references' => [
                    '*' => [
                        'id',
                        'server_id',
                        'name',
                        'highlight_price',
                        'link_url',
                        'image_url',
                        'image_path_local',
                        'created_at_server',
                        'updated_at_server',
                        'deleted_at',
                    ],
                ],
            ],
        ]);
});

it('returns only updated records for delta sync', function () {
    $old = EducationArticle::factory()->create([
        'updated_at_server' => now()->subDays(2),
    ]);
    $new = EducationArticle::factory()->create([
        'updated_at_server' => now(),
    ]);
    $oldPrice = PriceReference::factory()->create([
        'updated_at_server' => now()->subDays(2),
    ]);
    $newPrice = PriceReference::factory()->create([
        'updated_at_server' => now(),
    ]);

    $timestamp = now()->subHour()->toIso8601String();

    actingAs($this->user, 'sanctum')
        ->getJson('/api/v1/sync/education?last_sync_timestamp='.urlencode($timestamp))
        ->assertOk()
        ->assertJson([
            'success' => true,
            'data' => [
                'education_articles' => [
                    ['id' => $new->id],
                ],
                'price_references' => [
                    ['id' => $newPrice->id],
                ],
            ],
        ])
        ->assertJsonMissing([
            'id' => $old->id,
        ])
        ->assertJsonMissing([
            'id' => $oldPrice->id,
        ]);
});

it('returns soft deleted records with deleted_at set', function () {
    $article = EducationArticle::factory()->create();
    $price = PriceReference::factory()->create();

    $article->delete();
    $price->delete();

    actingAs($this->user, 'sanctum')
        ->getJson('/api/v1/sync/education')
        ->assertOk()
        ->assertJsonPath('data.education_articles.0.deleted_at', $article->deleted_at->toIso8601String())
        ->assertJsonPath('data.price_references.0.deleted_at', $price->deleted_at->toIso8601String());
});
