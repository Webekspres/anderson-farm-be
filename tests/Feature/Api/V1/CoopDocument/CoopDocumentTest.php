<?php

use App\Models\User;
use App\Models\Coop;
use App\Models\CoopFloor;
use App\Models\CoopDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('CoopDocument API', function () {
    $authUser = fn() => User::factory()->create();

    it('GET: hanya menampilkan dokumen milik kandang tertentu', function () use ($authUser) {
        $user = $authUser();
        $coopA = Coop::factory()->create();
        $coopB = Coop::factory()->create();
        $floorA = CoopFloor::factory()->create(['coop_id' => $coopA->id]);
        $floorB = CoopFloor::factory()->create(['coop_id' => $coopB->id]);
        $docA1 = CoopDocument::factory()->create(['floor_id' => $floorA->id]);
        $docA2 = CoopDocument::factory()->create(['floor_id' => $floorA->id]);
        $docB1 = CoopDocument::factory()->create(['floor_id' => $floorB->id]);

        $response = $this->actingAs($user)->getJson("/api/v1/coops/{$coopA->id}/documents");
        $response->assertOk();
        $ids = collect($response->json('data'))->pluck('id');
        expect($ids)->toContain($docA1->id)
            ->toContain($docA2->id)
            ->not->toContain($docB1->id);
    });

    it('POST: sukses menambah dokumen ke kandang', function () use ($authUser) {
        $user = $authUser();
        $coop = Coop::factory()->create();
        $floor = CoopFloor::factory()->create(['coop_id' => $coop->id]);
        $payload = [
            'document_name' => 'SOP Kandang',
            'document_type' => 'pdf',
            'file_url' => 'https://example.com/sop.pdf',
        ];
        $response = $this->actingAs($user)->postJson("/api/v1/coops/{$coop->id}/documents", $payload);
        $response->assertCreated();
        $response->assertJsonPath('data.name', 'SOP Kandang');
        $this->assertDatabaseHas('coop_documents', [
            'floor_id' => $floor->id,
            'name' => 'SOP Kandang',
            'file_type' => 'pdf',
            'file_url' => 'https://example.com/sop.pdf',
            'deleted_at' => null,
        ]);
    });

    it('POST: 404 jika coop_id tidak ditemukan', function () use ($authUser) {
        $user = $authUser();
        $payload = [
            'document_name' => 'SOP Kandang',
            'document_type' => 'pdf',
            'file_url' => 'https://example.com/sop.pdf',
        ];
        $response = $this->actingAs($user)->postJson("/api/v1/coops/invalid-coop-id/documents", $payload);
        $response->assertStatus(404);
    });

    it('DELETE: berhasil soft delete dokumen', function () use ($authUser) {
        $user = $authUser();
        $coop = Coop::factory()->create();
        $floor = CoopFloor::factory()->create(['coop_id' => $coop->id]);
        $doc = CoopDocument::factory()->create(['floor_id' => $floor->id]);
        $response = $this->actingAs($user)->deleteJson("/api/v1/coops/{$coop->id}/documents/{$doc->id}");
        $response->assertOk();
        $this->assertSoftDeleted('coop_documents', ['id' => $doc->id]);
    });

    it('DELETE: 404 jika dokumen tidak milik kandang', function () use ($authUser) {
        $user = $authUser();
        $coopA = Coop::factory()->create();
        $coopB = Coop::factory()->create();
        $floorB = CoopFloor::factory()->create(['coop_id' => $coopB->id]);
        $docB = CoopDocument::factory()->create(['floor_id' => $floorB->id]);
        $response = $this->actingAs($user)->deleteJson("/api/v1/coops/{$coopA->id}/documents/{$docB->id}");
        $response->assertStatus(404);
    });

    it('GET: 404 jika coop_id tidak ditemukan', function () use ($authUser) {
        $user = $authUser();
        $response = $this->actingAs($user)->getJson("/api/v1/coops/invalid-coop-id/documents");
        $response->assertStatus(404);
    });

    it('AUTH: 401 jika tanpa token', function () {
        $coop = Coop::factory()->create();
        $floor = CoopFloor::factory()->create(['coop_id' => $coop->id]);
        $doc = CoopDocument::factory()->create(['floor_id' => $floor->id]);
        $this->getJson("/api/v1/coops/{$coop->id}/documents")->assertUnauthorized();
        $this->postJson("/api/v1/coops/{$coop->id}/documents", [
            'document_name' => 'SOP',
            'document_type' => 'pdf',
            'file_url' => 'https://example.com/sop.pdf',
        ])->assertUnauthorized();
        $this->deleteJson("/api/v1/coops/{$coop->id}/documents/{$doc->id}")->assertUnauthorized();
    });
});
