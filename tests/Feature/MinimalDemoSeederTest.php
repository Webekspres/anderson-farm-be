<?php

use App\Models\Area;
use App\Models\ContractAbk;
use App\Models\ContractAcceptance;
use App\Models\Coop;
use App\Models\CoopFloor;
use App\Models\CoopUserAssignment;
use App\Models\EquipmentType;
use App\Models\Farm;
use App\Models\OvkItem;
use App\Models\ProductionPeriod;
use App\Models\ReportTemplate;
use App\Models\TransactionCategory;
use App\Models\User;
use Database\Seeders\MinimalDemoSeeder;
use Illuminate\Support\Facades\Hash;

it('seeds a minimal deterministic demo dataset', function () {
    $this->seed(MinimalDemoSeeder::class);

    expect(User::query()->count())->toBe(6)
        ->and(Area::query()->count())->toBe(1)
        ->and(Farm::query()->count())->toBe(1)
        ->and(Coop::query()->count())->toBe(1)
        ->and(CoopFloor::query()->count())->toBe(2)
        ->and(ProductionPeriod::query()->count())->toBe(1)
        ->and(CoopUserAssignment::query()->count())->toBe(2)
        ->and(ContractAbk::query()->count())->toBe(1)
        ->and(ContractAcceptance::query()->count())->toBe(1)
        ->and(TransactionCategory::query()->count())->toBe(2)
        ->and(ReportTemplate::query()->count())->toBe(1)
        ->and(EquipmentType::query()->count())->toBe(2)
        ->and(OvkItem::query()->count())->toBe(2);

    foreach (['admin', 'manager', 'finance', 'pic', 'abk', 'investor'] as $username) {
        $user = User::query()->where('username', $username)->first();

        expect($user)->not->toBeNull()
            ->and(Hash::check('password123', $user->password_hash))->toBeTrue();
    }

    expect(ProductionPeriod::query()->first())
        ->period_code->toBe('DEMO-001')
        ->status->toBe('active');

    expect(ReportTemplate::query()->first())
        ->report_type->toBe('WA');
});
