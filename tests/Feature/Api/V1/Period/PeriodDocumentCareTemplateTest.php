<?php

use App\Models\ProductionPeriod;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

describe('Period Document CARE_TEMPLATE', function () {
    beforeEach(function () {
        $this->user = User::factory()->create(['role' => 'manager']);
        $this->period = ProductionPeriod::factory()->create();
    });

    it('accepts CARE_TEMPLATE document type on store', function () {
        Sanctum::actingAs($this->user);

        $payload = [
            'title' => 'SOP Perawatan Minggu 1',
            'document_type' => 'CARE_TEMPLATE',
            'file_url' => 'https://example.com/care-template.pdf',
        ];

        $response = $this->postJson("/api/v1/periods/{$this->period->id}/documents", $payload);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.document_type', 'CARE_TEMPLATE')
            ->assertJsonPath('data.title', 'SOP Perawatan Minggu 1');

        $this->assertDatabaseHas('period_documents', [
            'period_id' => $this->period->id,
            'document_type' => 'CARE_TEMPLATE',
            'uploaded_by' => $this->user->id,
        ]);
    });
});
