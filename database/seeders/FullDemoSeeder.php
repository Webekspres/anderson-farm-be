<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class FullDemoSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Opt-in only: large / random factory dataset for load-style demos.
     *
     * NOT the default for Spesifikasi Flow smoke or UAT.
     * Prefer: php artisan db:seed  (MinimalDemoSeeder) — see README.md in this folder.
     *
     * Run explicitly: php artisan db:seed --class=FullDemoSeeder
     */
    public function run(): void
    {
        $this->call([
            AreaSeeder::class,
            FarmSeeder::class,
            CoopSeeder::class,
            CoopFloorSeeder::class,
            UserSeeder::class,
            ProductionPeriodSeeder::class,
            CoopUserAssignmentSeeder::class,
            EquipmentTypeSeeder::class,
            TransactionCategorySeeder::class,
            ReportTemplateSeeder::class,
            CoopDocumentSeeder::class,
            MaintenanceLogSeeder::class,
            EducationArticleSeeder::class,
            OvkItemSeeder::class,
            PriceReferenceSeeder::class,
            FormConfigSeeder::class,
            CoopEquipmentSeeder::class,
            EquipmentTypeFormConfigSeeder::class,
            PeriodFormAssignmentSeeder::class,
            ChecklistTaskSeeder::class,
            ContractAbkSeeder::class,
            DailyActivityHeaderSeeder::class,
            DailyChecklistLogSeeder::class,
            DailyDynamicLogSeeder::class,
            OvkUsageSeeder::class,
            HarvestEntrySeeder::class,
            PhotoEvidenceSeeder::class,
            SyncTrackerSeeder::class,
            NotificationSeeder::class,
            ActivityLogSeeder::class,
            TransactionSeeder::class,
            RhppSeeder::class,
            RhppDocumentSeeder::class,
        ]);
    }
}
