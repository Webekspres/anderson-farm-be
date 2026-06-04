<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\DailyActivityHeader;
use App\Models\Notification;
use DevKandil\NotiFire\Enums\MessagePriority;
use DevKandil\NotiFire\Facades\Fcm;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class NotifyAbkOfDailyActivityReviewJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly string $dailyActivityHeaderId,
        public readonly string $action,
        public readonly ?string $rejectionReason = null,
    ) {}

    public function handle(): void
    {
        try {
            $header = DailyActivityHeader::query()
                ->with('user')
                ->find($this->dailyActivityHeaderId);

            $abkUser = $header?->user;

            if (! $abkUser || $abkUser->role !== 'abk') {
                return;
            }

            $isApproved = $this->action === 'approve';
            $title = $isApproved ? 'Laporan Harian Disetujui' : 'Laporan Harian Ditolak';
            $message = $isApproved
                ? 'Manager telah menyetujui laporan harian Anda.'
                : 'Manager menolak laporan harian Anda. Alasan: '.$this->rejectionReason;
            $fcmBody = $isApproved
                ? 'Laporan harian Anda telah disetujui.'
                : 'Laporan ditolak: '.$this->rejectionReason;

            $now = now();

            Notification::create([
                'id' => Str::uuid()->toString(),
                'user_id' => $abkUser->id,
                'title' => $title,
                'message' => $message,
                'type' => $isApproved ? 'INFO' : 'WARNING',
                'reference_id' => $this->dailyActivityHeaderId,
                'reference_type' => 'DAILY_ACTIVITY',
                'sync_status' => 'SYNCED',
                'created_at_client' => $now,
                'created_at_server' => $now,
                'updated_at_client' => $now,
                'updated_at_server' => $now,
            ]);

            if (! empty($abkUser->fcm_token)) {
                Fcm::withTitle($title)
                    ->withBody($fcmBody)
                    ->withSound('default')
                    ->withPriority(MessagePriority::HIGH)
                    ->sendNotification($abkUser->fcm_token);
            }
        } catch (\Throwable $e) {
            Log::warning('NotifyAbkOfDailyActivityReviewJob failed.', [
                'header_id' => $this->dailyActivityHeaderId,
                'action' => $this->action,
                'exception' => $e->getMessage(),
            ]);
        }
    }
}
