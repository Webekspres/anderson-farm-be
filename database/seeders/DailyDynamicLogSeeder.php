<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\DailyDynamicLog;

class DailyDynamicLogSeeder extends Seeder
{
    public function run(): void
    {
        $headers = \App\Models\DailyActivityHeader::inRandomOrder()->take(5)->get();
        $formConfigs = \App\Models\FormConfig::inRandomOrder()->take(5)->get();

        if ($headers->isEmpty() || $formConfigs->isEmpty()) {
            return;
        }

        foreach ($headers as $index => $header) {
            $config = $formConfigs->get($index % $formConfigs->count());
            DailyDynamicLog::factory()->create([
                'header_id' => $header->id,
                'form_config_id' => $config->id,
            ]);
        }
    }
}
