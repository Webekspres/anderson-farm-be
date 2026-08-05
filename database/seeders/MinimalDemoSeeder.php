<?php

namespace Database\Seeders;

use App\Models\Area;
use App\Models\ContractAbk;
use App\Models\ContractAcceptance;
use App\Models\Coop;
use App\Models\CoopFloor;
use App\Models\CoopUserAssignment;
use App\Models\EquipmentType;
use App\Models\Farm;
use App\Models\OvkItem;
use App\Models\PeriodDocument;
use App\Models\PeriodInvestor;
use App\Models\ProductionPeriod;
use App\Models\ReportTemplate;
use App\Models\TransactionCategory;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class MinimalDemoSeeder extends Seeder
{
    /**
     * Deterministic dataset for Spesifikasi Flow smoke / UAT (not random factories).
     *
     * Default via DatabaseSeeder. See database/seeders/README.md for login matrix.
     */
    public function run(): void
    {
        $now = now();
        $synced = [
            'sync_status' => 'SYNCED',
            'created_at_client' => $now,
            'created_at_server' => $now,
            'updated_at_client' => $now,
            'updated_at_server' => $now,
            'deleted_at' => null,
        ];

        $users = $this->seedUsers($synced);
        $hierarchy = $this->seedHierarchy($users['manager'], $synced);
        $period = $this->seedPeriod($hierarchy['floor1'], $users['pic'], $synced);

        $this->seedAssignments($hierarchy['coop'], $users['pic'], $users['abk'], $synced);
        $this->seedContract($period, $users['admin'], $users['pic'], $users['abk'], $synced);
        $this->seedPeriodInvestor($period, $users['investor'], $synced);
        $this->seedPeriodDocuments($period, $users['manager'], $synced);
        $this->seedCatalog($synced);
    }

    /**
     * @param  array<string, mixed>  $synced
     * @return array<string, User>
     */
    private function seedUsers(array $synced): array
    {
        $passwordHash = Hash::make('password123');

        $definitions = [
            'admin' => ['name' => 'Administrator', 'email' => 'admin@example.com', 'role' => 'admin', 'server_id' => 1],
            'manager' => ['name' => 'Manager Demo', 'email' => 'manager@example.com', 'role' => 'manager', 'server_id' => 2],
            'finance' => ['name' => 'Finance Demo', 'email' => 'finance@example.com', 'role' => 'finance', 'server_id' => 3],
            'pic' => ['name' => 'PIC Demo', 'email' => 'pic@example.com', 'role' => 'pic', 'server_id' => 4],
            'abk' => ['name' => 'ABK Demo', 'email' => 'abk@example.com', 'role' => 'abk', 'server_id' => 5],
            'investor' => ['name' => 'Investor Demo', 'email' => 'investor@example.com', 'role' => 'investor', 'server_id' => 6],
        ];

        $users = [];

        foreach ($definitions as $username => $definition) {
            $users[$username] = User::query()->updateOrCreate(
                ['username' => $username],
                [
                    'password_hash' => $passwordHash,
                    'name' => $definition['name'],
                    'email' => $definition['email'],
                    'phone_number' => '0812345678'.$definition['server_id'],
                    'role' => $definition['role'],
                    'server_id' => $definition['server_id'],
                    'is_active' => true,
                    'version' => 1,
                    ...$synced,
                ],
            );
        }

        return $users;
    }

    /**
     * @param  array<string, mixed>  $synced
     * @return array{area: Area, farm: Farm, coop: Coop, floor1: CoopFloor, floor2: CoopFloor}
     */
    private function seedHierarchy(User $manager, array $synced): array
    {
        $area = Area::query()->updateOrCreate(
            ['name' => 'Area Demo'],
            [
                'server_id' => 1001,
                'version' => 1,
                'manager_id' => $manager->id,
                ...$synced,
            ],
        );

        $farm = Farm::query()->updateOrCreate(
            ['name' => 'Farm Demo', 'area_id' => $area->id],
            [
                'server_id' => 1002,
                'version' => 1,
                'address' => 'Jl. Demo Farm No. 1',
                'type' => 'broiler',
                'is_active' => true,
                ...$synced,
            ],
        );

        $coop = Coop::query()->updateOrCreate(
            ['name' => 'Kandang A', 'farm_id' => $farm->id],
            [
                'server_id' => 1003,
                'version' => 1,
                'coop_type' => 'CH_POSTAL',
                'note' => 'Minimal demo coop',
                'is_active' => true,
                ...$synced,
            ],
        );

        $floor1 = CoopFloor::query()->updateOrCreate(
            ['name' => 'Lantai 1', 'coop_id' => $coop->id],
            [
                'server_id' => 1004,
                'capacity' => 10000,
                ...$synced,
            ],
        );

        $floor2 = CoopFloor::query()->updateOrCreate(
            ['name' => 'Lantai 2', 'coop_id' => $coop->id],
            [
                'server_id' => 1005,
                'capacity' => 10000,
                ...$synced,
            ],
        );

        return [
            'area' => $area,
            'farm' => $farm,
            'coop' => $coop,
            'floor1' => $floor1,
            'floor2' => $floor2,
        ];
    }

    /**
     * @param  array<string, mixed>  $synced
     */
    private function seedPeriod(CoopFloor $floor, User $pic, array $synced): ProductionPeriod
    {
        return ProductionPeriod::query()->updateOrCreate(
            ['period_code' => 'DEMO-001'],
            [
                'server_id' => 2001,
                'version' => 1,
                'floor_id' => $floor->id,
                'pic_id' => $pic->id,
                'start_date' => now()->subWeeks(2)->toDateString(),
                'end_date' => null,
                'initial_stock' => 5000,
                'closing_reason' => null,
                'status' => 'active',
                'closed_at' => null,
                ...$synced,
            ],
        );
    }

    /**
     * @param  array<string, mixed>  $synced
     */
    private function seedAssignments(Coop $coop, User $pic, User $abk, array $synced): void
    {
        CoopUserAssignment::query()->updateOrCreate(
            ['user_id' => $pic->id, 'coop_id' => $coop->id],
            [
                'server_id' => 3001,
                'version' => 1,
                'assigned_at' => now(),
                'role_in_coop' => 'kepala_kandang',
                ...$synced,
            ],
        );

        CoopUserAssignment::query()->updateOrCreate(
            ['user_id' => $abk->id, 'coop_id' => $coop->id],
            [
                'server_id' => 3002,
                'version' => 1,
                'assigned_at' => now(),
                'role_in_coop' => 'abk',
                ...$synced,
            ],
        );
    }

    /**
     * @param  array<string, mixed>  $synced
     */
    private function seedContract(
        ProductionPeriod $period,
        User $uploader,
        User $pic,
        User $abk,
        array $synced,
    ): void {
        $contract = ContractAbk::query()->updateOrCreate(
            [
                'period_id' => $period->id,
                'title' => 'Kontrak Kemitraan ABK — DEMO-001',
            ],
            [
                'server_id' => 4001,
                'version' => 1,
                'file_path_local' => null,
                'file_url' => 'https://example.com/contracts/demo-001.pdf',
                'uploaded_by' => $uploader->id,
                ...$synced,
            ],
        );

        $acceptances = [
            [$pic, 4002, 'demo-device-pic'],
            [$abk, 4003, 'demo-device-abk'],
        ];

        foreach ($acceptances as [$user, $serverId, $deviceId]) {
            ContractAcceptance::query()->updateOrCreate(
                [
                    'contract_id' => $contract->id,
                    'user_id' => $user->id,
                ],
                [
                    'server_id' => $serverId,
                    'accepted_at' => now(),
                    'device_id' => $deviceId,
                    'current_loan_accumulated' => 0,
                    'remaining_loan_limit' => 3000000,
                    ...$synced,
                ],
            );
        }
    }

    /**
     * @param  array<string, mixed>  $synced
     */
    private function seedPeriodInvestor(ProductionPeriod $period, User $investor, array $synced): void
    {
        PeriodInvestor::query()->updateOrCreate(
            [
                'period_id' => $period->id,
                'user_id' => $investor->id,
            ],
            [
                'server_id' => 6001,
                'version' => 1,
                'profit_share_percentage' => 100,
                'initial_investment' => 50_000_000,
                'final_dividend_amount' => null,
                'is_paid' => false,
                ...$synced,
            ],
        );
    }

    /**
     * @param  array<string, mixed>  $synced
     */
    private function seedPeriodDocuments(ProductionPeriod $period, User $uploader, array $synced): void
    {
        $documents = [
            ['CARE_TEMPLATE', 'SOP Perawatan — DEMO-001', 7001, 'https://example.com/docs/care-template-demo.pdf'],
            ['OVK', 'Program OVK — DEMO-001', 7002, 'https://example.com/docs/ovk-demo.pdf'],
            ['ARV', 'Standar ARV — DEMO-001', 7003, 'https://example.com/docs/arv-demo.pdf'],
        ];

        foreach ($documents as [$documentType, $title, $serverId, $fileUrl]) {
            PeriodDocument::query()->updateOrCreate(
                [
                    'period_id' => $period->id,
                    'document_type' => $documentType,
                ],
                [
                    'server_id' => $serverId,
                    'version' => 1,
                    'title' => $title,
                    'file_path_local' => null,
                    'file_url' => $fileUrl,
                    'uploaded_by' => $uploader->id,
                    ...$synced,
                ],
            );
        }
    }

    /**
     * @param  array<string, mixed>  $synced
     */
    private function seedCatalog(array $synced): void
    {
        $categories = [
            ['Gaji Pegawai', 'EXPENSE', 5001],
            ['Pakan', 'EXPENSE', 5006],
            ['OVK / Obat', 'EXPENSE', 5007],
            ['Listrik & Utilitas', 'EXPENSE', 5008],
            ['Operasional Kandang', 'EXPENSE', 5009],
            ['Transport', 'EXPENSE', 5010],
            ['Penjualan Ayam', 'INCOME', 5002],
            ['Bonus / Insentif', 'INCOME', 5011],
            ['Pemasukan Lain', 'INCOME', 5012],
        ];

        foreach ($categories as [$name, $type, $serverId]) {
            TransactionCategory::query()->updateOrCreate(
                ['name' => $name],
                [
                    'server_id' => $serverId,
                    'version' => 1,
                    'type' => $type,
                    'is_active' => true,
                    ...$synced,
                ],
            );
        }

        ReportTemplate::query()->updateOrCreate(
            ['name' => 'WhatsApp Generator'],
            [
                'server_id' => 5003,
                'version' => 1,
                'report_type' => 'WA',
                'content_placeholder' => "Laporan harian {{period_code}}\nPopulasi: {{population}}\nMortalitas: {{mortality}}",
                ...$synced,
            ],
        );

        foreach ([['Feeders', 5004], ['Drinkers', 5005]] as [$name, $serverId]) {
            EquipmentType::query()->updateOrCreate(
                ['name' => $name],
                [
                    'server_id' => $serverId,
                    'version' => 1,
                    'description' => "Demo equipment: {$name}",
                    ...$synced,
                ],
            );
        }

        OvkItem::query()->updateOrCreate(
            ['name' => 'Vaksin ND'],
            [
                'server_id' => 5006,
                'version' => 1,
                'type' => 'VAKSIN',
                'unit' => 'ml',
                'description' => 'Demo vaksin ND',
                'is_active' => true,
                ...$synced,
            ],
        );

        OvkItem::query()->updateOrCreate(
            ['name' => 'Vitamin C'],
            [
                'server_id' => 5007,
                'version' => 1,
                'type' => 'OBAT',
                'unit' => 'gram',
                'description' => 'Demo vitamin C',
                'is_active' => true,
                ...$synced,
            ],
        );
    }
}
