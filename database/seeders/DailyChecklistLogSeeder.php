<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\DailyChecklistLog;

class DailyChecklistLogSeeder extends Seeder
{
    public function run(): void
    {
        $headers = \App\Models\DailyActivityHeader::inRandomOrder()->take(5)->get();
        $tasks = \App\Models\ChecklistTask::inRandomOrder()->take(5)->get();

        if ($headers->isEmpty() || $tasks->isEmpty()) {
            return;
        }

        foreach ($headers as $index => $header) {
            $task = $tasks->get($index % $tasks->count());
            DailyChecklistLog::factory()->create([
                'header_id' => $header->id,
                'task_id' => $task->id,
            ]);
        }
    }
}
