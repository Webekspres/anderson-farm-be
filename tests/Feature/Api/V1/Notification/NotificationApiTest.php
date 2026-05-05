<?php

declare(strict_types=1);

use App\Models\Notification;
use App\Models\User;
use Carbon\Carbon;

describe('Notification API', function (): void {

    it('returns paginated notifications with correct unread_count meta', function (): void {
        $user = User::factory()->create();

        Notification::factory()->count(3)->create([
            'user_id' => $user->id,
            'read_at' => null,
            'sync_status' => 'SYNCED',
        ]);

        Notification::factory()->count(2)->create([
            'user_id' => $user->id,
            'read_at' => now()->subHour(),
            'sync_status' => 'SYNCED',
        ]);

        $response = $this->actingAs($user)->getJson('/api/v1/notifications');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(5, 'data')
            ->assertJsonPath('meta.unread_count', 3);
    });

    it('marks one notification read and is idempotent on second PATCH', function (): void {
        Carbon::setTestNow(Carbon::parse('2026-05-05 14:30:00'));

        $user = User::factory()->create();

        $notification = Notification::factory()->create([
            'user_id' => $user->id,
            'read_at' => null,
            'sync_status' => 'SYNCED',
        ]);

        $this->actingAs($user)
            ->patchJson("/api/v1/notifications/{$notification->id}/read")
            ->assertOk()
            ->assertJsonPath('success', true);

        $notification->refresh();
        expect($notification->read_at)->not->toBeNull();

        $frozenReadAt = $notification->read_at->clone();

        Carbon::setTestNow(Carbon::parse('2026-05-06 09:00:00'));

        $this->actingAs($user)
            ->patchJson("/api/v1/notifications/{$notification->id}/read")
            ->assertOk();

        $notification->refresh();
        expect($notification->read_at->equalTo($frozenReadAt))->toBeTrue();

        Carbon::setTestNow();
    });

    it('returns 404 when marking another user notification as read', function (): void {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $notificationB = Notification::factory()->create([
            'user_id' => $userB->id,
            'read_at' => null,
            'sync_status' => 'SYNCED',
        ]);

        $this->actingAs($userA)
            ->patchJson("/api/v1/notifications/{$notificationB->id}/read")
            ->assertNotFound();

        $notificationB->refresh();
        expect($notificationB->read_at)->toBeNull();
    });

    it('marks all notifications read for the authenticated user', function (): void {
        $user = User::factory()->create();

        Notification::factory()->count(5)->create([
            'user_id' => $user->id,
            'read_at' => null,
            'sync_status' => 'SYNCED',
        ]);

        $this->actingAs($user)
            ->patchJson('/api/v1/notifications/read-all')
            ->assertOk()
            ->assertJsonPath('success', true);

        expect(
            Notification::query()
                ->where('user_id', $user->id)
                ->whereNull('read_at')
                ->count()
        )->toBe(0);
    });
});
