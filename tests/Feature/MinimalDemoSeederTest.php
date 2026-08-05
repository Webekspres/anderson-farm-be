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
use App\Models\PeriodDocument;
use App\Models\PeriodInvestor;
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
        ->and(ContractAcceptance::query()->count())->toBe(2)
        ->and(PeriodInvestor::query()->count())->toBe(1)
        ->and(PeriodDocument::query()->count())->toBe(3)
        ->and(TransactionCategory::query()->count())->toBe(9)
        ->and(ReportTemplate::query()->count())->toBe(1)
        ->and(EquipmentType::query()->count())->toBe(2)
        ->and(OvkItem::query()->count())->toBe(2);

    foreach (['admin', 'manager', 'finance', 'pic', 'abk', 'investor'] as $username) {
        $user = User::query()->where('username', $username)->first();

        expect($user)->not->toBeNull()
            ->and(Hash::check('password123', $user->password_hash))->toBeTrue();
    }

    expect(TransactionCategory::query()->where('type', 'EXPENSE')->count())->toBe(6)
        ->and(TransactionCategory::query()->where('type', 'INCOME')->count())->toBe(3);

    $period = ProductionPeriod::query()->first();

    expect($period)
        ->period_code->toBe('DEMO-001')
        ->status->toBe('active');

    $pic = User::query()->where('username', 'pic')->first();
    $abk = User::query()->where('username', 'abk')->first();
    $investor = User::query()->where('username', 'investor')->first();
    $contract = ContractAbk::query()->first();

    expect(ContractAcceptance::query()->where('contract_id', $contract->id)->where('user_id', $pic->id)->exists())->toBeTrue()
        ->and(ContractAcceptance::query()->where('contract_id', $contract->id)->where('user_id', $abk->id)->exists())->toBeTrue();

    $periodInvestor = PeriodInvestor::query()->first();

    expect($periodInvestor)
        ->period_id->toBe($period->id)
        ->user_id->toBe($investor->id)
        ->profit_share_percentage->toBe(100.0)
        ->initial_investment->toBe(50_000_000.0);

    expect(PeriodDocument::query()->where('period_id', $period->id)->pluck('document_type')->sort()->values()->all())
        ->toBe(['ARV', 'CARE_TEMPLATE', 'OVK']);

    expect(ReportTemplate::query()->first())
        ->report_type->toBe('WA');
});
