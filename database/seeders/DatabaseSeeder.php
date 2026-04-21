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
            UserSeeder::class,
            AreaSeeder::class,
            FarmSeeder::class,
            CoopSeeder::class,
            EquipmentTypeSeeder::class,
            TransactionCategorySeeder::class,
            ReportTemplateSeeder::class,
            CoopDocumentSeeder::class,
            EducationArticleSeeder::class,
            OvkItemSeeder::class,
            PriceReferenceSeeder::class,
            FormConfigSeeder::class,
            ProductionPeriodSeeder::class,
            CoopEquipmentSeeder::class,
            CoopUserAssignmentSeeder::class,
            EquipmentTypeFormConfigSeeder::class,
            PeriodFormAssignmentSeeder::class,
            ChecklistTaskSeeder::class,
            ContractAbkSeeder::class,
        ]);
    }
}
