<?php

use App\Models\ChecklistTask;
use App\Models\DailyActivityHeader;
use App\Models\DailyChecklistLog;
use App\Models\DailyDynamicLog;
use App\Models\FormConfig;
use App\Models\HarvestEntry;
use App\Models\OvkItem;
use App\Models\OvkUsage;
use App\Models\PhotoEvidence;
use App\Models\ProductionPeriod;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

function postAuthUser(): User
{
    return User::factory()->create();
}

function buildHeaderPayload(
    string $periodId,
    string $userId,
    ?array $overrides = [],
    ?array $children = [],
): array {
    return array_merge([
        'id' => Str::uuid()->toString(),
        'period_id' => $periodId,
        'user_id' => $userId,
        'date' => '2026-04-24',
        'age_days' => 15,
        'mortality_count' => 5,
        'cull_count' => 1,
        'average_weight' => 1.2,
        'business_status' => 'DRAFT',
        'created_at_client' => '2026-04-24T06:00:00Z',
        'updated_at_client' => '2026-04-24T06:00:00Z',
        'dynamic_logs' => [],
        'harvests' => [],
        'ovk_usages' => [],
        'photos' => [],
        'checklist_logs' => [],
    ], $overrides, $children);
}

describe('POST /api/v1/sync/daily-activities', function () {

    it('successfully upserts a header with all child relations (Happy Path)', function () {
        $user = postAuthUser();
        $period = ProductionPeriod::factory()->create(['status' => 'active']);
        $formConfig = FormConfig::factory()->create();
        $ovkItem = OvkItem::factory()->create();
        $task = ChecklistTask::factory()->create();

        $headerId = Str::uuid()->toString();

        $payload = [
            'headers' => [
                buildHeaderPayload($period->id, $user->id, [
                    'id' => $headerId,
                ], [
                    'dynamic_logs' => [
                        [
                            'id' => Str::uuid()->toString(),
                            'form_config_id' => $formConfig->id,
                            'value' => '4',
                            'created_at_client' => '2026-04-24T06:00:00Z',
                            'updated_at_client' => '2026-04-24T06:00:00Z',
                        ],
                    ],
                    'harvests' => [
                        [
                            'id' => Str::uuid()->toString(),
                            'rit_number' => 1,
                            'buyer_name' => 'Pak Budi',
                            'total_birds' => 200,
                            'total_weight' => 400.5,
                            'price_per_kg' => 20000,
                            'total_revenue' => 8010000,
                            'delivery_order_no' => 'DO-001',
                            'created_at_client' => '2026-04-24T06:00:00Z',
                            'updated_at_client' => '2026-04-24T06:00:00Z',
                        ],
                    ],
                    'ovk_usages' => [
                        [
                            'id' => Str::uuid()->toString(),
                            'ovk_item_id' => $ovkItem->id,
                            'quantity' => 2.5,
                            'notes' => 'Vaksin ND',
                            'created_at_client' => '2026-04-24T06:00:00Z',
                            'updated_at_client' => '2026-04-24T06:00:00Z',
                        ],
                    ],
                    'photos' => [
                        [
                            'id' => Str::uuid()->toString(),
                            'photo_path_local' => '/local/photo.jpg',
                            'photo_url' => 'https://cdn.example.com/photo.jpg',
                            'photo_type' => 'mortality',
                            'description' => 'Ayam mati pagi',
                            'created_at_client' => '2026-04-24T06:00:00Z',
                            'updated_at_client' => '2026-04-24T06:00:00Z',
                        ],
                    ],
                    'checklist_logs' => [
                        [
                            'id' => Str::uuid()->toString(),
                            'task_id' => $task->id,
                            'boolean_value' => true,
                            'text_value' => null,
                            'notes' => 'Sudah dicek',
                            'created_at_client' => '2026-04-24T06:00:00Z',
                            'updated_at_client' => '2026-04-24T06:00:00Z',
                        ],
                    ],
                ]),
            ],
        ];

        $response = $this->actingAs($user)->postJson('/api/v1/sync/daily-activities', $payload);

        $response->assertOk();
        $response->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'server_timestamp',
                'sync_results' => [
                    '*' => ['id', 'status', 'server_id'],
                ],
            ],
        ]);
        $response->assertJsonPath('data.sync_results.0.id', $headerId);
        $response->assertJsonPath('data.sync_results.0.status', 'SYNCED');

        // Verifikasi data tersimpan di database
        $this->assertDatabaseHas('daily_activity_headers', [
            'id' => $headerId,
            'sync_status' => 'SYNCED',
        ]);
        $this->assertDatabaseCount('daily_dynamic_logs', 1);
        $this->assertDatabaseCount('harvest_entries', 1);
        $this->assertDatabaseCount('ovk_usages', 1);
        $this->assertDatabaseCount('photo_evidences', 1);
        $this->assertDatabaseCount('daily_checklist_logs', 1);
    });

    it('detects conflict when server data is newer than client data', function () {
        $user = postAuthUser();
        $period = ProductionPeriod::factory()->create(['status' => 'active']);

        // Buat header di database dengan updated_at_server yang LEBIH BARU dari updated_at_client payload
        $existingHeader = DailyActivityHeader::factory()->create([
            'period_id' => $period->id,
            'user_id' => $user->id,
            'updated_at_server' => '2026-04-25T12:00:00Z', // Server punya data besok
            'sync_status' => 'SYNCED',
        ]);

        $payload = [
            'headers' => [
                buildHeaderPayload($period->id, $user->id, [
                    'id' => $existingHeader->id,
                    'updated_at_client' => '2026-04-24T06:00:00Z', // Klien punya data kemarin
                ]),
            ],
        ];

        $response = $this->actingAs($user)->postJson('/api/v1/sync/daily-activities', $payload);

        $response->assertOk();
        $response->assertJsonPath('data.sync_results.0.id', $existingHeader->id);
        $response->assertJsonPath('data.sync_results.0.status', 'CONFLICT');

        // Pastikan data asli di DB TIDAK berubah
        $this->assertDatabaseHas('daily_activity_headers', [
            'id' => $existingHeader->id,
            'sync_status' => 'SYNCED', // Tetap SYNCED, bukan ditimpa
        ]);
    });

    it('flags PERIOD_CLOSED when period is not active', function () {
        $user = postAuthUser();
        $period = ProductionPeriod::factory()->create(['status' => 'closed']);

        $payload = [
            'headers' => [
                buildHeaderPayload($period->id, $user->id),
            ],
        ];

        $response = $this->actingAs($user)->postJson('/api/v1/sync/daily-activities', $payload);

        $response->assertOk();
        $response->assertJsonPath('data.sync_results.0.status', 'PERIOD_CLOSED');

        // Pastikan header TIDAK tersimpan
        $this->assertDatabaseCount('daily_activity_headers', 0);
    });

    it('returns 422 when headers array is missing', function () {
        $user = postAuthUser();

        $response = $this->actingAs($user)->postJson('/api/v1/sync/daily-activities', []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('headers');
    });

    it('returns 422 when required child fields are missing', function () {
        $user = postAuthUser();
        $period = ProductionPeriod::factory()->create(['status' => 'active']);

        $payload = [
            'headers' => [
                buildHeaderPayload($period->id, $user->id, [], [
                    'dynamic_logs' => [
                        ['id' => Str::uuid()->toString()], // Missing form_config_id, value, dll
                    ],
                ]),
            ],
        ];

        $response = $this->actingAs($user)->postJson('/api/v1/sync/daily-activities', $payload);

        $response->assertStatus(422);
    });

    it('performs wipe and replace on re-sync (existing children are replaced)', function () {
        $user = postAuthUser();
        $period = ProductionPeriod::factory()->create(['status' => 'active']);
        $formConfig = FormConfig::factory()->create();

        $headerId = Str::uuid()->toString();

        // Sync pertama — 2 dynamic logs
        $firstPayload = [
            'headers' => [
                buildHeaderPayload($period->id, $user->id, [
                    'id' => $headerId,
                ], [
                    'dynamic_logs' => [
                        [
                            'id' => Str::uuid()->toString(),
                            'form_config_id' => $formConfig->id,
                            'value' => 'old_value_1',
                            'created_at_client' => '2026-04-24T06:00:00Z',
                            'updated_at_client' => '2026-04-24T06:00:00Z',
                        ],
                    ],
                ]),
            ],
        ];

        $this->actingAs($user)->postJson('/api/v1/sync/daily-activities', $firstPayload)->assertOk();
        $this->assertDatabaseCount('daily_dynamic_logs', 1);

        // Sync kedua — ganti dengan 1 dynamic log baru (value berbeda)
        $newFormConfig = FormConfig::factory()->create();
        $secondPayload = [
            'headers' => [
                buildHeaderPayload($period->id, $user->id, [
                    'id' => $headerId,
                    'updated_at_client' => '2026-04-24T12:00:00Z',
                    'date' => '2026-04-24',
                ], [
                    'dynamic_logs' => [
                        [
                            'id' => Str::uuid()->toString(),
                            'form_config_id' => $newFormConfig->id,
                            'value' => 'new_value',
                            'created_at_client' => '2026-04-24T12:00:00Z',
                            'updated_at_client' => '2026-04-24T12:00:00Z',
                        ],
                    ],
                ]),
            ],
        ];

        $response = $this->actingAs($user)->postJson('/api/v1/sync/daily-activities', $secondPayload);

        $response->assertOk();
        $response->assertJsonPath('data.sync_results.0.status', 'SYNCED');

        // HARUS hanya 1 log baru, yang lama sudah di-wipe
        $this->assertDatabaseCount('daily_dynamic_logs', 1);
        $this->assertDatabaseHas('daily_dynamic_logs', ['value' => 'new_value']);
        $this->assertDatabaseMissing('daily_dynamic_logs', ['value' => 'old_value_1']);
    });

    it('returns 401 when not authenticated', function () {
        $response = $this->postJson('/api/v1/sync/daily-activities', [
            'headers' => [],
        ]);

        $response->assertUnauthorized();
    });
});
