<?php

use App\Jobs\NotifyAbksOfNewContractJob;
use App\Models\ContractAbk;
use App\Models\ContractAcceptance;
use App\Models\ProductionPeriod;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;

uses(RefreshDatabase::class);

describe('ContractAbk API', function () {

    beforeEach(function () {
        $this->user = User::factory()->create(['role' => 'manager']);
        $this->period = ProductionPeriod::factory()->create();
    });

    it('bisa mengambil daftar kontrak melalui GET', function () {
        Sanctum::actingAs($this->user);

        ContractAbk::factory()->count(2)->create([
            'period_id' => $this->period->id,
            'uploaded_by' => $this->user->id,
        ]);

        $response = getJson("/api/v1/periods/{$this->period->id}/contracts");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => ['id', 'title', 'file_url', 'uploaded_by', 'uploader_name'],
                ],
            ]);

        expect($response->json('data'))->toHaveCount(2);
    });

    it('berhasil menyimpan kontrak baru melalui POST', function () {
        Queue::fake();

        Sanctum::actingAs($this->user);

        $payload = [
            'title' => 'Kontrak FCR Kemitraan Final',
            'file_url' => 'https://andersonfarm.com/files/contract.pdf',
        ];

        $response = postJson("/api/v1/periods/{$this->period->id}/contracts", $payload);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.title', 'Kontrak FCR Kemitraan Final');

        $this->assertDatabaseHas('contract_abks', [
            'period_id' => $this->period->id,
            'title' => 'Kontrak FCR Kemitraan Final',
            'uploaded_by' => $this->user->id,
        ]);

        $this->app->terminate();

        Queue::assertPushed(NotifyAbksOfNewContractJob::class, function (NotifyAbksOfNewContractJob $job): bool {
            return $job->productionPeriodId === $this->period->id;
        });
    });

    it('gagal 422 jika judul kosong saat POST', function () {
        Sanctum::actingAs($this->user);

        $payload = [
            'file_url' => 'https://andersonfarm.com/files/contract.pdf',
        ];

        $response = postJson("/api/v1/periods/{$this->period->id}/contracts", $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['title']);
    });

    it('gagal 422 jika tidak menyertakan file_url atau file_path_local', function () {
        Sanctum::actingAs($this->user);

        $payload = [
            'title' => 'Kontrak Tanpa File',
        ];

        $response = postJson("/api/v1/periods/{$this->period->id}/contracts", $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['file_url', 'file_path_local']);
    });

    it('gagal 401 jika tanpa token', function () {
        // Tanpa Sanctum::actingAs()
        $payload = [
            'title' => 'Kontrak Rahasia',
            'file_url' => 'http://test.com/x.pdf',
        ];

        $response = postJson("/api/v1/periods/{$this->period->id}/contracts", $payload);

        $response->assertStatus(401);
    });

    it('bisa melihat detail kontrak (GET)', function () {
        Sanctum::actingAs($this->user);
        $contract = ContractAbk::factory()->create(['period_id' => $this->period->id]);

        $response = $this->getJson("/api/v1/contracts/{$contract->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $contract->id);
    });

    it('berhasil menyetujui kontrak (POST Accept)', function () {
        Sanctum::actingAs($this->user);
        $contract = ContractAbk::factory()->create(['period_id' => $this->period->id]);

        $payload = ['device_id' => 'SAMSUNG-S21-XYZ'];

        $response = $this->postJson("/api/v1/contracts/{$contract->id}", $payload);

        $response->assertStatus(201);
        $this->assertDatabaseHas('contract_acceptances', [
            'contract_id' => $contract->id,
            'user_id' => $this->user->id,
            'device_id' => 'SAMSUNG-S21-XYZ',
        ]);
    });

    it('gagal menghapus kontrak yang sudah disetujui (DELETE)', function () {
        Sanctum::actingAs($this->user);
        $contract = ContractAbk::factory()->create(['period_id' => $this->period->id]);

        // Buat tanda tangan palsu
        ContractAcceptance::factory()->create(['contract_id' => $contract->id]);

        $response = $this->deleteJson("/api/v1/contracts/{$contract->id}");

        $response->assertStatus(403) // Forbidden
            ->assertJsonPath('success', false);

        $this->assertDatabaseHas('contract_abks', ['id' => $contract->id]);
    });
});
