<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\CoopUserAssignment;
use App\Models\Notification;
use App\Models\ProductionPeriod;
use App\Models\User;
use DevKandil\NotiFire\Enums\MessagePriority;
use DevKandil\NotiFire\Facades\Fcm;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class NotifyAbksOfNewContractJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly string $productionPeriodId,
    ) {}

    public function handle(): void
    {
        try {
            $period = ProductionPeriod::query()
                ->with(['floor:id,coop_id'])
                ->find($this->productionPeriodId);

            $coopId = $period?->floor?->coop_id;

            if (! $coopId) {
                return;
            }

            $assignedUserIds = CoopUserAssignment::query()
                ->where('coop_id', '=', $coopId)
                ->whereNull('deleted_at', 'and', false)
                ->pluck('user_id');

            if ($assignedUserIds->isEmpty()) {
                return;
            }

            $now = now();

            User::query()
                ->whereIn('id', $assignedUserIds->all(), 'and', false)
                ->where('role', '=', 'abk')
                ->each(function (User $abkUser) use ($now): void {
                    try {
                        // 1. Simpan In-App Notification ke Database (Untuk fitur Lonceng)
                        Notification::create([
                            'id' => Str::uuid()->toString(),
                            'user_id' => $abkUser->id,
                            'title' => 'Kontrak Baru Tersedia',
                            'message' => 'Manager telah mengunggah SPK baru. Silakan buka aplikasi untuk menyetujui kontrak.',
                            'type' => 'INFO',
                            'reference_id' => $this->productionPeriodId,
                            'reference_type' => 'CONTRACT',
                            'sync_status' => 'SYNCED', // Karena dibuat langsung di sisi server
                            'created_at_client' => $now,
                            'created_at_server' => $now,
                            'updated_at_client' => $now,
                            'updated_at_server' => $now,
                        ]);

                        // 2. Kirim Push Notification via FCM (Jika user memiliki token)
                        if (! empty($abkUser->fcm_token)) {
                            Fcm::withTitle('Kontrak Baru Tersedia')
                                ->withBody('Manager telah mengunggah SPK baru. Silakan buka aplikasi untuk menyetujui kontrak.')
                                ->withSound('default')
                                ->withPriority(MessagePriority::HIGH)
                                ->sendNotification($abkUser->fcm_token);
                        }
                    } catch (\Throwable $e) {
                        Log::warning('Notification DB creation or FCM failed for ABK after new contract upload.', [
                            'user_id' => $abkUser->id,
                            'exception' => $e->getMessage(),
                        ]);
                    }
                });
        } catch (\Throwable $e) {
            Log::warning('NotifyAbksOfNewContractJob failed.', [
                'period_id' => $this->productionPeriodId,
                'exception' => $e->getMessage(),
            ]);
        }
    }
}
