<?php

namespace Database\Seeders;

use App\Models\ChecklistTask;
use App\Models\DailyActivityHeader;
use App\Models\DailyChecklistLog;
use App\Models\ProductionPeriod;
use Illuminate\Database\Seeder;

class DailyChecklistLogSeeder extends Seeder
{
    public function run(): void
    {
        $headers = DailyActivityHeader::inRandomOrder()->take(5)->get();
        $tasks = ChecklistTask::inRandomOrder()->take(5)->get();

        if ($tasks->isEmpty()) {
            return;
        }

        if (! $headers->isEmpty()) {
            foreach ($headers as $index => $header) {
                $task = $tasks->get($index % $tasks->count());
                DailyChecklistLog::factory()->create([
                    'header_id' => $header->id,
                    'period_id' => null,
                    'task_id' => $task->id,
                ]);
            }
        }

        // Seed Pre-Chick-In Checklist Logs (without daily header, associated directly with period)
        $periods = ProductionPeriod::inRandomOrder()->take(5)->get();
        if (! $periods->isEmpty()) {
            foreach ($periods as $index => $period) {
                $task = $tasks->get(($index + 1) % $tasks->count());
                DailyChecklistLog::factory()->create([
                    'header_id' => null,
                    'period_id' => $period->id,
                    'task_id' => $task->id,
                ]);
            }
        }
    }
}
