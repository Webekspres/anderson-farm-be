<?php

use App\Models\PeriodDocument;
use App\Models\ProductionPeriod;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

describe('Period Document API', function () {

    beforeEach(function () {
        $this->user = User::factory()->create();
        $this->period = ProductionPeriod::factory()->create();
    });

    it('bisa mengambil daftar dokumen melalui GET', function () {
        Sanctum::actingAs($this->user);

        PeriodDocument::factory()->count(3)->create([
            'period_id' => $this->period->id,
            'uploaded_by' => $this->user->id,
        ]);

        $response = $this->getJson("/api/v1/periods/{$this->period->id}/documents");

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        expect($response->json('data'))->toHaveCount(3);
    });

    it('berhasil menyimpan dokumen baru (POST)', function () {
        Sanctum::actingAs($this->user);

        $payload = [
            'title' => 'Surat Jalan Vaksin Gumboro',
            'document_type' => 'OVK',
            'file_url' => 'https://example.com/vaksin.jpg',
        ];

        $response = $this->postJson("/api/v1/periods/{$this->period->id}/documents", $payload);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.title', 'Surat Jalan Vaksin Gumboro');

        $this->assertDatabaseHas('period_documents', [
            'period_id' => $this->period->id,
            'document_type' => 'OVK',
            'uploaded_by' => $this->user->id,
        ]);
    });

    it('gagal 422 jika tipe dokumen tidak valid', function () {
        Sanctum::actingAs($this->user);

        $payload = [
            'title' => 'Dokumen Asal',
            'document_type' => 'PDF', // Tipe salah, harusnya OVK/ARV/OTHER
            'file_url' => 'https://example.com/file.pdf',
        ];

        $response = $this->postJson("/api/v1/periods/{$this->period->id}/documents", $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['document_type']);
    });

    it('gagal 401 jika tanpa token otentikasi', function () {
        $payload = [
            'title' => 'Rahasia',
            'document_type' => 'OVK',
            'file_url' => 'http://test.com/img.jpg'
        ];

        $response = $this->postJson("/api/v1/periods/{$this->period->id}/documents", $payload);

        $response->assertStatus(401);
    });
});
