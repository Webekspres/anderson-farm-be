<?php

declare(strict_types=1);

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;

describe('POST /api/v1/sync/activity-logs', function (): void {
    beforeEach(function (): void {
        $this->user = User::factory()->create([
            'role' => 'abk',
            'username' => 'budi_abk',
        ]);
    });

    it('berhasil menyinkronkan bulk log aktivitas', function (): void {
        Sanctum::actingAs($this->user, ['*']);

        $logId = (string) Str::uuid();

        $payload = [
            'logs' => [
                [
                    'id' => $logId,
                    'action' => 'TAP_BUTTON',
                    'entity_type' => 'DailyActivityHeader',
                    'entity_id' => (string) Str::uuid(),
                    'device_id' => 'device-abc-123',
                    'status' => 'SUCCESS',
                    'metadata' => json_encode(['screen' => 'home']),
                    'created_at_client' => '2026-06-01T08:00:00Z',
                    'updated_at_client' => '2026-06-01T08:00:00Z',
                ],
            ],
        ];

        $response = $this->postJson('/api/v1/sync/activity-logs', $payload);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Sinkronisasi log aktivitas berhasil.',
                'synced_ids' => [$logId],
            ]);

        $this->assertDatabaseHas('activity_logs', [
            'id' => $logId,
            'user_id' => $this->user->id,
            'action' => 'TAP_BUTTON',
            'sync_status' => 'SYNCED',
        ]);

        $log = ActivityLog::query()->find($logId);
        expect($log->created_at_server)->not->toBeNull()
            ->and($log->updated_at_server)->not->toBeNull();
    });

    it('mengembalikan sukses dengan synced_ids kosong ketika logs array kosong', function (): void {
        Sanctum::actingAs($this->user, ['*']);

        $this->postJson('/api/v1/sync/activity-logs', ['logs' => []])
            ->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Sinkronisasi log aktivitas berhasil.',
                'synced_ids' => [],
            ]);

        expect(ActivityLog::query()->count())->toBe(0);
    });

    it('idempotent pada retry sync dengan UUID yang sama', function (): void {
        Sanctum::actingAs($this->user, ['*']);

        $logId = (string) Str::uuid();

        $basePayload = [
            'logs' => [
                [
                    'id' => $logId,
                    'action' => 'TAP_BUTTON',
                    'entity_type' => 'Screen',
                    'entity_id' => (string) Str::uuid(),
                    'device_id' => 'device-retry',
                    'status' => 'SUCCESS',
                    'metadata' => null,
                    'created_at_client' => '2026-06-01T08:00:00Z',
                    'updated_at_client' => '2026-06-01T08:00:00Z',
                ],
            ],
        ];

        $this->postJson('/api/v1/sync/activity-logs', $basePayload)->assertOk();

        $firstCreatedAtServer = ActivityLog::query()->find($logId)->created_at_server;

        $retryPayload = $basePayload;
        $retryPayload['logs'][0]['action'] = 'TAP_BUTTON_RETRY';
        $retryPayload['logs'][0]['status'] = 'FAILED';

        $this->postJson('/api/v1/sync/activity-logs', $retryPayload)
            ->assertOk()
            ->assertJsonPath('synced_ids', [$logId]);

        expect(ActivityLog::query()->where('id', $logId)->count())->toBe(1);

        $log = ActivityLog::query()->find($logId);
        expect($log->action)->toBe('TAP_BUTTON_RETRY')
            ->and($log->status)->toBe('FAILED')
            ->and($log->created_at_server->equalTo($firstCreatedAtServer))->toBeTrue();
    });

    it('menggunakan entri terakhir ketika UUID duplikat dalam satu payload', function (): void {
        Sanctum::actingAs($this->user, ['*']);

        $logId = (string) Str::uuid();
        $entityId = (string) Str::uuid();

        $payload = [
            'logs' => [
                [
                    'id' => $logId,
                    'action' => 'FIRST',
                    'entity_type' => 'Screen',
                    'entity_id' => $entityId,
                    'device_id' => null,
                    'status' => 'SUCCESS',
                    'metadata' => null,
                    'created_at_client' => '2026-06-01T08:00:00Z',
                    'updated_at_client' => '2026-06-01T08:00:00Z',
                ],
                [
                    'id' => $logId,
                    'action' => 'LAST_WINS',
                    'entity_type' => 'Screen',
                    'entity_id' => $entityId,
                    'device_id' => null,
                    'status' => 'FAILED',
                    'metadata' => null,
                    'created_at_client' => '2026-06-01T09:00:00Z',
                    'updated_at_client' => '2026-06-01T09:00:00Z',
                ],
            ],
        ];

        $this->postJson('/api/v1/sync/activity-logs', $payload)
            ->assertOk()
            ->assertJsonPath('synced_ids', [$logId]);

        expect(ActivityLog::query()->where('id', $logId)->count())->toBe(1)
            ->and(ActivityLog::query()->find($logId)->action)->toBe('LAST_WINS');
    });

    it('menyimpan user_id dari user yang terautentikasi bukan dari payload', function (): void {
        Sanctum::actingAs($this->user, ['*']);

        $otherUser = User::factory()->create();
        $logId = (string) Str::uuid();

        $this->postJson('/api/v1/sync/activity-logs', [
            'logs' => [
                [
                    'id' => $logId,
                    'action' => 'VIEW',
                    'entity_type' => 'Contract',
                    'entity_id' => (string) Str::uuid(),
                    'device_id' => null,
                    'status' => 'SUCCESS',
                    'metadata' => null,
                    'created_at_client' => '2026-06-01T08:00:00Z',
                    'updated_at_client' => '2026-06-01T08:00:00Z',
                ],
            ],
        ])->assertOk();

        $this->assertDatabaseHas('activity_logs', [
            'id' => $logId,
            'user_id' => $this->user->id,
        ]);

        $this->assertDatabaseMissing('activity_logs', [
            'id' => $logId,
            'user_id' => $otherUser->id,
        ]);
    });

    it('menolak request tanpa token', function (): void {
        $this->postJson('/api/v1/sync/activity-logs', [
            'logs' => [],
        ])->assertUnauthorized();
    });

    it('menolak log dengan id bukan uuid', function (): void {
        Sanctum::actingAs($this->user, ['*']);

        $this->postJson('/api/v1/sync/activity-logs', [
            'logs' => [
                [
                    'id' => 'bukan-uuid',
                    'action' => 'TAP',
                    'entity_type' => 'Screen',
                    'entity_id' => 'entity-1',
                    'status' => 'SUCCESS',
                    'created_at_client' => '2026-06-01T08:00:00Z',
                    'updated_at_client' => '2026-06-01T08:00:00Z',
                ],
            ],
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['logs.0.id']);
    });
});
