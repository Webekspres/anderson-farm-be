<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();
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
