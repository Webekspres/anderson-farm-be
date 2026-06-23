<?php

use App\Models\ChecklistTask;
use App\Models\CoopUserAssignment;
use App\Models\DailyActivityHeader;
use App\Models\FormConfig;
use App\Models\OvkItem;
use App\Models\ProductionPeriod;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

function postAuthUser(): User
{
    return User::factory()->create();
}

function syncActivityDay(int $daysAgo = 2): string
{
    return now()->subDays($daysAgo)->format('Y-m-d');
}

function syncClientTimestamp(string $activityDay): string
{
    return $activityDay.'T06:00:00Z';
}

function createSyncPeriod(array $overrides = []): ProductionPeriod
{
    return ProductionPeriod::factory()->create(array_merge([
        'start_date' => now()->subMonths(6)->format('Y-m-d'),
    ], $overrides));
}

function buildHeaderPayload(
    string $periodId,
    string $userId,
    ?array $overrides = [],
    ?array $children = [],
): array {
    $activityDay = $overrides['date'] ?? syncActivityDay();
    $clientTimestamp = $overrides['created_at_client'] ?? syncClientTimestamp($activityDay);

    return array_merge([
        'id' => Str::uuid()->toString(),
        'period_id' => $periodId,
        'user_id' => $userId,
        'date' => $activityDay,
        'age_days' => 15,
        'mortality_count' => 5,
        'cull_count' => 1,
        'feed_consumption_kg' => 125.5,
        'average_weight' => 1.2,
        'business_status' => 'DRAFT',
        'created_at_client' => $clientTimestamp,
        'updated_at_client' => $overrides['updated_at_client'] ?? $clientTimestamp,
        'dynamic_logs' => [],
        'harvests' => [],
        'ovk_usages' => [],
        'photos' => [],
        'checklist_logs' => [],
    ], $overrides, $children);
}

function assignUserToPeriodCoop(User $user, ProductionPeriod $period): void
{
    $period->loadMissing('floor');
    CoopUserAssignment::factory()->create([
        'user_id' => $user->id,
        'coop_id' => $period->floor->coop_id,
    ]);
}

describe('POST /api/v1/sync/daily-activities', function () {

    it('successfully upserts a header with all child relations (Happy Path)', function () {
        $user = postAuthUser();
        $period = createSyncPeriod(['status' => 'active']);
        assignUserToPeriodCoop($user, $period);
        $formConfig = FormConfig::factory()->create();
        $ovkItem = OvkItem::factory()->create();
        $task = ChecklistTask::factory()->create();

        $headerId = Str::uuid()->toString();
        $clientTimestamp = syncClientTimestamp(syncActivityDay());

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
                            'created_at_client' => $clientTimestamp,
                            'updated_at_client' => $clientTimestamp,
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
                            'created_at_client' => $clientTimestamp,
                            'updated_at_client' => $clientTimestamp,
                        ],
                    ],
                    'ovk_usages' => [
                        [
                            'id' => Str::uuid()->toString(),
                            'ovk_item_id' => $ovkItem->id,
                            'quantity' => 2.5,
                            'notes' => 'Vaksin ND',
                            'created_at_client' => $clientTimestamp,
                            'updated_at_client' => $clientTimestamp,
                        ],
                    ],
                    'photos' => [
                        [
                            'id' => Str::uuid()->toString(),
                            'photo_path_local' => '/local/photo.jpg',
                            'photo_url' => 'https://cdn.example.com/photo.jpg',
                            'photo_type' => 'mortality',
                            'description' => 'Ayam mati pagi',
                            'created_at_client' => $clientTimestamp,
                            'updated_at_client' => $clientTimestamp,
                        ],
                    ],
                    'checklist_logs' => [
                        [
                            'id' => Str::uuid()->toString(),
                            'task_id' => $task->id,
                            'boolean_value' => true,
                            'text_value' => null,
                            'notes' => 'Sudah dicek',
                            'created_at_client' => $clientTimestamp,
                            'updated_at_client' => $clientTimestamp,
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
            'feed_consumption_kg' => 125.5,
        ]);
        $this->assertDatabaseCount('daily_dynamic_logs', 1);
        $this->assertDatabaseCount('harvest_entries', 1);
        $this->assertDatabaseCount('ovk_usages', 1);
        $this->assertDatabaseCount('photo_evidences', 1);
        $this->assertDatabaseCount('daily_checklist_logs', 1);
    });

    it('detects conflict when server data is newer than client data', function () {
        $user = postAuthUser();
        $period = createSyncPeriod(['status' => 'active']);
        assignUserToPeriodCoop($user, $period);

        // Buat header di database dengan updated_at_server yang LEBIH BARU dari updated_at_client payload
        $activityDay = syncActivityDay(10);
        $existingHeader = DailyActivityHeader::factory()->create([
            'period_id' => $period->id,
            'user_id' => $user->id,
            'date' => $activityDay,
            'updated_at_server' => now()->addDay()->toIso8601String(),
            'sync_status' => 'SYNCED',
        ]);

        $payload = [
            'headers' => [
                buildHeaderPayload($period->id, $user->id, [
                    'id' => $existingHeader->id,
                    'date' => $activityDay,
                    'updated_at_client' => syncClientTimestamp(syncActivityDay(15)),
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
        $period = createSyncPeriod(['status' => 'closed']);
        assignUserToPeriodCoop($user, $period);

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
        $period = createSyncPeriod(['status' => 'active']);
        assignUserToPeriodCoop($user, $period);

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
        $period = createSyncPeriod(['status' => 'active']);
        assignUserToPeriodCoop($user, $period);
        $formConfig = FormConfig::factory()->create();

        $headerId = Str::uuid()->toString();
        $activityDay = syncActivityDay();
        $clientTimestamp = syncClientTimestamp($activityDay);

        // Sync pertama — 2 dynamic logs
        $firstPayload = [
            'headers' => [
                buildHeaderPayload($period->id, $user->id, [
                    'id' => $headerId,
                    'date' => $activityDay,
                ], [
                    'dynamic_logs' => [
                        [
                            'id' => Str::uuid()->toString(),
                            'form_config_id' => $formConfig->id,
                            'value' => 'old_value_1',
                            'created_at_client' => $clientTimestamp,
                            'updated_at_client' => $clientTimestamp,
                        ],
                    ],
                ]),
            ],
        ];

        $this->actingAs($user)->postJson('/api/v1/sync/daily-activities', $firstPayload)->assertOk();
        $this->assertDatabaseCount('daily_dynamic_logs', 1);

        // Sync kedua — ganti dengan 1 dynamic log baru (value berbeda)
        $newFormConfig = FormConfig::factory()->create();
        $newClientTime = now()->addMinute()->toIso8601String();
        $secondPayload = [
            'headers' => [
                buildHeaderPayload($period->id, $user->id, [
                    'id' => $headerId,
                    'updated_at_client' => $newClientTime,
                    'date' => $activityDay,
                ], [
                    'dynamic_logs' => [
                        [
                            'id' => Str::uuid()->toString(),
                            'form_config_id' => $newFormConfig->id,
                            'value' => 'new_value',
                            'created_at_client' => $newClientTime,
                            'updated_at_client' => $newClientTime,
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

    it('rejects header if another header already exists for the same date and period but different UUID', function () {
        $user = postAuthUser();
        $period = createSyncPeriod(['status' => 'active']);
        assignUserToPeriodCoop($user, $period);

        $uuidA = Str::uuid()->toString();
        $activityDay = syncActivityDay();
        DailyActivityHeader::factory()->create([
            'id' => $uuidA,
            'period_id' => $period->id,
            'user_id' => $user->id,
            'date' => $activityDay,
            'updated_at_server' => now(),
            'sync_status' => 'SYNCED',
        ]);

        $uuidB = Str::uuid()->toString();
        $payload = [
            'headers' => [
                buildHeaderPayload($period->id, $user->id, [
                    'id' => $uuidB,
                    'date' => $activityDay,
                ]),
            ],
        ];

        $response = $this->actingAs($user)->postJson('/api/v1/sync/daily-activities', $payload);

        $response->assertOk()
            ->assertJsonPath('data.sync_results.0.status', 'DUPLICATE_DATE');

        $this->assertDatabaseMissing('daily_activity_headers', [
            'id' => $uuidB,
        ]);
        $this->assertDatabaseHas('daily_activity_headers', [
            'id' => $uuidA,
        ]);
    });

    it('returns 422 if the daily activity date is in the future', function () {
        $user = postAuthUser();
        $period = createSyncPeriod(['status' => 'active']);
        assignUserToPeriodCoop($user, $period);

        $future = now()->addDays(2)->format('Y-m-d');
        $payload = [
            'headers' => [
                buildHeaderPayload($period->id, $user->id, [
                    'date' => $future,
                    'created_at_client' => $future.'T06:00:00Z',
                    'updated_at_client' => $future.'T06:00:00Z',
                ]),
            ],
        ];

        $response = $this->actingAs($user)->postJson('/api/v1/sync/daily-activities', $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['headers.0.date']);
    });

    it('returns FORBIDDEN or 403 if the user is not assigned to the period\'s coop', function () {
        $user = postAuthUser();
        $period = createSyncPeriod(['status' => 'active']);

        $payload = [
            'headers' => [
                buildHeaderPayload($period->id, $user->id),
            ],
        ];

        $response = $this->actingAs($user)->postJson('/api/v1/sync/daily-activities', $payload);

        if ($response->status() === 403) {
            $response->assertForbidden();
        } else {
            $response->assertOk()
                ->assertJsonPath('data.sync_results.0.status', 'FORBIDDEN');
        }

        $headerIdFromPayload = $payload['headers'][0]['id'];
        $this->assertDatabaseMissing('daily_activity_headers', [
            'id' => $headerIdFromPayload,
        ]);
    });

    it('rolls back the entire header transaction if a child payload has an invalid foreign key', function () {
        $user = postAuthUser();
        $period = createSyncPeriod(['status' => 'active']);
        assignUserToPeriodCoop($user, $period);

        $headerId = Str::uuid()->toString();
        $clientTimestamp = syncClientTimestamp(syncActivityDay());
        $payload = [
            'headers' => [
                buildHeaderPayload($period->id, $user->id, [
                    'id' => $headerId,
                ], [
                    'ovk_usages' => [
                        [
                            'id' => Str::uuid()->toString(),
                            'ovk_item_id' => Str::uuid()->toString(),
                            'quantity' => 1,
                            'notes' => null,
                            'created_at_client' => $clientTimestamp,
                            'updated_at_client' => $clientTimestamp,
                        ],
                    ],
                ]),
            ],
        ];

        $response = $this->actingAs($user)->postJson('/api/v1/sync/daily-activities', $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['headers.0.ovk_usages.0.ovk_item_id']);

        $this->assertDatabaseMissing('daily_activity_headers', [
            'id' => $headerId,
        ]);
    });

    it('applies partial success on bulk POST without rolling back successful headers when one conflicts', function () {
        $user = postAuthUser();
        $period = createSyncPeriod([
            'status' => 'active',
            'start_date' => now()->subMonths(3)->format('Y-m-d'),
        ]);
        assignUserToPeriodCoop($user, $period);

        $dayOlder = now()->subDays(22)->format('Y-m-d');
        $dayNewA = now()->subDays(9)->format('Y-m-d');
        $dayNewB = now()->subDays(8)->format('Y-m-d');

        $existingHeader = DailyActivityHeader::factory()->create([
            'period_id' => $period->id,
            'user_id' => $user->id,
            'date' => $dayOlder,
            'updated_at_server' => now()->addDay()->toIso8601String(),
            'sync_status' => 'SYNCED',
        ]);

        $uuidNew1 = Str::uuid()->toString();
        $uuidNew3 = Str::uuid()->toString();

        $payload = [
            'headers' => [
                buildHeaderPayload($period->id, $user->id, [
                    'id' => $uuidNew1,
                    'date' => $dayNewA,
                    'created_at_client' => $dayNewA.'T06:00:00Z',
                    'updated_at_client' => $dayNewA.'T06:00:00Z',
                ]),
                buildHeaderPayload($period->id, $user->id, [
                    'id' => $existingHeader->id,
                    'date' => $dayOlder,
                    'updated_at_client' => now()->subDays(2)->toIso8601String(),
                ]),
                buildHeaderPayload($period->id, $user->id, [
                    'id' => $uuidNew3,
                    'date' => $dayNewB,
                    'created_at_client' => $dayNewB.'T06:00:00Z',
                    'updated_at_client' => $dayNewB.'T06:00:00Z',
                ]),
            ],
        ];

        $existingHeader->refresh();
        $untouchedServerTs = $existingHeader->updated_at_server->clone();
        $untouchedDate = $existingHeader->date->format('Y-m-d');

        $response = $this->actingAs($user)->postJson('/api/v1/sync/daily-activities', $payload);

        $response->assertOk()
            ->assertJsonCount(3, 'data.sync_results')
            ->assertJsonPath('data.sync_results.0.status', 'SYNCED')
            ->assertJsonPath('data.sync_results.1.status', 'CONFLICT')
            ->assertJsonPath('data.sync_results.2.status', 'SYNCED');

        $this->assertDatabaseHas('daily_activity_headers', [
            'id' => $uuidNew1,
            'sync_status' => 'SYNCED',
        ]);
        $this->assertDatabaseHas('daily_activity_headers', [
            'id' => $uuidNew3,
            'sync_status' => 'SYNCED',
        ]);

        $existingHeader->refresh();
        expect($existingHeader->sync_status)->toBe('SYNCED')
            ->and($existingHeader->updated_at_server->equalTo($untouchedServerTs))->toBeTrue()
            ->and($existingHeader->date->format('Y-m-d'))->toBe($untouchedDate);
    });

    it('rejects modification when business_status on server is APPROVED', function () {
        $user = postAuthUser();
        $period = createSyncPeriod([
            'status' => 'active',
            'start_date' => now()->subMonths(2)->format('Y-m-d'),
        ]);
        assignUserToPeriodCoop($user, $period);

        $activityDay = now()->subDays(4)->format('Y-m-d');

        $existingHeader = DailyActivityHeader::factory()->create([
            'period_id' => $period->id,
            'user_id' => $user->id,
            'date' => $activityDay,
            'business_status' => 'APPROVED',
            'updated_at_server' => now()->subDays(4)->toIso8601String(),
            'sync_status' => 'SYNCED',
        ]);

        $payload = [
            'headers' => [
                buildHeaderPayload($period->id, $user->id, [
                    'id' => $existingHeader->id,
                    'date' => $activityDay,
                    'business_status' => 'DRAFT',
                    'updated_at_client' => now()->toIso8601String(),
                ]),
            ],
        ];

        $response = $this->actingAs($user)->postJson('/api/v1/sync/daily-activities', $payload);

        $response->assertOk()
            ->assertJsonPath('data.sync_results.0.status', 'LOCKED');

        $this->assertDatabaseHas('daily_activity_headers', [
            'id' => $existingHeader->id,
            'business_status' => 'APPROVED',
        ]);
    });

    it('returns 422 when mortality, culls, feed consumption, or OVK quantity are negative', function () {
        $user = postAuthUser();
        $period = createSyncPeriod([
            'status' => 'active',
            'start_date' => now()->subMonths(1)->format('Y-m-d'),
        ]);
        assignUserToPeriodCoop($user, $period);
        $ovkItem = OvkItem::factory()->create();

        $day = now()->subDays(2)->format('Y-m-d');

        $payload = [
            'headers' => [
                buildHeaderPayload($period->id, $user->id, [
                    'date' => $day,
                    'created_at_client' => $day.'T06:00:00Z',
                    'updated_at_client' => $day.'T06:00:00Z',
                    'mortality_count' => -5,
                    'cull_count' => -2,
                    'feed_consumption_kg' => -1,
                ], [
                    'ovk_usages' => [
                        [
                            'id' => Str::uuid()->toString(),
                            'ovk_item_id' => $ovkItem->id,
                            'quantity' => -10,
                            'notes' => null,
                            'created_at_client' => syncClientTimestamp($day),
                            'updated_at_client' => syncClientTimestamp($day),
                        ],
                    ],
                ]),
            ],
        ];

        $response = $this->actingAs($user)->postJson('/api/v1/sync/daily-activities', $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'headers.0.mortality_count',
                'headers.0.cull_count',
                'headers.0.feed_consumption_kg',
                'headers.0.ovk_usages.0.quantity',
            ]);
    });

    it('returns 422 when daily activity date is before the period start_date', function () {
        $user = postAuthUser();
        $periodStart = now()->subDays(10)->format('Y-m-d');
        $period = createSyncPeriod([
            'status' => 'active',
            'start_date' => $periodStart,
        ]);
        assignUserToPeriodCoop($user, $period);

        $tooEarly = now()->subDays(25)->format('Y-m-d');

        $payload = [
            'headers' => [
                buildHeaderPayload($period->id, $user->id, [
                    'date' => $tooEarly,
                    'created_at_client' => $tooEarly.'T06:00:00Z',
                    'updated_at_client' => $tooEarly.'T06:00:00Z',
                ]),
            ],
        ];

        $response = $this->actingAs($user)->postJson('/api/v1/sync/daily-activities', $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['headers.0.date']);
    });

    it('returns 422 when business_status is APPROVED or REJECTED', function () {
        $user = postAuthUser();
        $period = createSyncPeriod(['status' => 'active']);
        assignUserToPeriodCoop($user, $period);

        foreach (['APPROVED', 'REJECTED'] as $status) {
            $payload = [
                'headers' => [
                    buildHeaderPayload($period->id, $user->id, [
                        'business_status' => $status,
                    ]),
                ],
            ];

            $response = $this->actingAs($user)->postJson('/api/v1/sync/daily-activities', $payload);

            $response->assertStatus(422)
                ->assertJsonValidationErrors(['headers.0.business_status']);
        }
    });

    it('allows resubmit SUBMITTED after server REJECTED and clears rejection fields', function () {
        $user = postAuthUser();
        $period = createSyncPeriod([
            'status' => 'active',
            'start_date' => now()->subMonths(1)->format('Y-m-d'),
        ]);
        assignUserToPeriodCoop($user, $period);
        $manager = User::factory()->create(['role' => 'manager']);

        $activityDay = now()->subDays(3)->format('Y-m-d');

        $existingHeader = DailyActivityHeader::factory()->rejected()->create([
            'period_id' => $period->id,
            'user_id' => $user->id,
            'date' => $activityDay,
            'approved_by' => $manager->id,
        ]);

        $payload = [
            'headers' => [
                buildHeaderPayload($period->id, $user->id, [
                    'id' => $existingHeader->id,
                    'date' => $activityDay,
                    'business_status' => 'SUBMITTED',
                    'mortality_count' => 3,
                    'updated_at_client' => now()->toIso8601String(),
                ]),
            ],
        ];

        $response = $this->actingAs($user)->postJson('/api/v1/sync/daily-activities', $payload);

        $response->assertOk()
            ->assertJsonPath('data.sync_results.0.status', 'SYNCED');

        $this->assertDatabaseHas('daily_activity_headers', [
            'id' => $existingHeader->id,
            'business_status' => 'SUBMITTED',
            'rejection_reason' => null,
            'approved_by' => null,
            'mortality_count' => 3,
        ]);
    });

    it('returns INVALID_STATUS when pushing DRAFT while server is SUBMITTED', function () {
        $user = postAuthUser();
        $period = createSyncPeriod([
            'status' => 'active',
            'start_date' => now()->subMonths(1)->format('Y-m-d'),
        ]);
        assignUserToPeriodCoop($user, $period);

        $activityDay = now()->subDays(2)->format('Y-m-d');

        $existingHeader = DailyActivityHeader::factory()->submitted()->create([
            'period_id' => $period->id,
            'user_id' => $user->id,
            'date' => $activityDay,
        ]);

        $payload = [
            'headers' => [
                buildHeaderPayload($period->id, $user->id, [
                    'id' => $existingHeader->id,
                    'date' => $activityDay,
                    'business_status' => 'DRAFT',
                    'updated_at_client' => now()->toIso8601String(),
                ]),
            ],
        ];

        $response = $this->actingAs($user)->postJson('/api/v1/sync/daily-activities', $payload);

        $response->assertOk()
            ->assertJsonPath('data.sync_results.0.status', 'INVALID_STATUS');

        $this->assertDatabaseHas('daily_activity_headers', [
            'id' => $existingHeader->id,
            'business_status' => 'SUBMITTED',
        ]);
    });
});
