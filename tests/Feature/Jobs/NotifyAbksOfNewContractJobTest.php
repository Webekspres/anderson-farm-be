<?php

declare(strict_types=1);

use App\Jobs\NotifyAbksOfNewContractJob;
use App\Models\CoopUserAssignment;
use App\Models\Notification;
use App\Models\ProductionPeriod;
use App\Models\User;
use DevKandil\NotiFire\Enums\MessagePriority;
use DevKandil\NotiFire\Facades\Fcm;

describe('NotifyAbksOfNewContractJob', function (): void {

    afterEach(function (): void {
        Mockery::close();
    });

    it('creates in-app notification and sends FCM when ABK has token', function (): void {
        $period = ProductionPeriod::factory()->create();
        $period->loadMissing('floor');

        $abk = User::factory()->create([
            'role' => 'abk',
            'fcm_token' => 'valid-token-123',
        ]);

        CoopUserAssignment::factory()->create([
            'user_id' => $abk->id,
            'coop_id' => $period->floor->coop_id,
        ]);

        Fcm::shouldReceive('withTitle')
            ->once()
            ->with('Kontrak Baru Tersedia')
            ->andReturnSelf();
        Fcm::shouldReceive('withBody')
            ->once()
            ->with('Manager telah mengunggah SPK baru. Silakan buka aplikasi untuk menyetujui kontrak.')
            ->andReturnSelf();
        Fcm::shouldReceive('withSound')
            ->once()
            ->with('default')
            ->andReturnSelf();
        Fcm::shouldReceive('withPriority')
            ->once()
            ->with(MessagePriority::HIGH)
            ->andReturnSelf();
        Fcm::shouldReceive('sendNotification')
            ->once()
            ->with('valid-token-123')
            ->andReturn(true);

        (new NotifyAbksOfNewContractJob($period->id))->handle();

        $this->assertDatabaseHas('notifications', [
            'user_id' => $abk->id,
            'type' => 'INFO',
            'reference_id' => $period->id,
            'reference_type' => 'CONTRACT',
        ]);

        expect(Notification::query()->where('user_id', $abk->id)->count())->toBe(1);
    });

    it('skips FCM but still creates DB notification when ABK has no token', function (): void {
        $period = ProductionPeriod::factory()->create();
        $period->loadMissing('floor');

        $abk = User::factory()->create([
            'role' => 'abk',
            'fcm_token' => null,
        ]);

        CoopUserAssignment::factory()->create([
            'user_id' => $abk->id,
            'coop_id' => $period->floor->coop_id,
        ]);

        Fcm::shouldReceive('sendNotification')->never();
        Fcm::shouldReceive('withTitle')->never();

        (new NotifyAbksOfNewContractJob($period->id))->handle();

        $this->assertDatabaseHas('notifications', [
            'user_id' => $abk->id,
            'type' => 'INFO',
            'reference_id' => $period->id,
        ]);
    });

    it('still persists notification when FCM send throws', function (): void {
        $period = ProductionPeriod::factory()->create();
        $period->loadMissing('floor');

        $abk = User::factory()->create([
            'role' => 'abk',
            'fcm_token' => 'token-that-will-fail-push',
        ]);

        CoopUserAssignment::factory()->create([
            'user_id' => $abk->id,
            'coop_id' => $period->floor->coop_id,
        ]);

        Fcm::shouldReceive('withTitle')->once()->andReturnSelf();
        Fcm::shouldReceive('withBody')->once()->andReturnSelf();
        Fcm::shouldReceive('withSound')->once()->andReturnSelf();
        Fcm::shouldReceive('withPriority')->once()->andReturnSelf();
        Fcm::shouldReceive('sendNotification')
            ->once()
            ->andThrow(new Exception('Firebase Timeout'));

        expect(fn () => (new NotifyAbksOfNewContractJob($period->id))->handle())->not->toThrow(Exception::class);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $abk->id,
            'reference_id' => $period->id,
        ]);
    });

    it('creates nothing when coop has no assigned users', function (): void {
        $period = ProductionPeriod::factory()->create();

        (new NotifyAbksOfNewContractJob($period->id))->handle();

        expect(Notification::query()->count())->toBe(0);
    });
});
