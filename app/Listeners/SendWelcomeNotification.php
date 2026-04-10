<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendWelcomeNotification implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(UserCreated $event): void
    {
        $user = $event->user;

        // Logika kirim email atau WhatsApp ke $user ditaruh di sini.
        // Karena kita pakai 'ShouldQueue', ini akan dikerjakan di latar belakang!
        
        Log::info("Mengirim email sambutan ke: " . $user->full_name);
    }
}
