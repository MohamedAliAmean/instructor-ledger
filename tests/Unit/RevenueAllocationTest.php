<?php

use App\Enums\LedgerTypeEnum;
use App\Enums\SubscriptionPlanEnum;
use App\Models\Instructor;
use App\Models\LedgerEntry;
use App\Models\RevenueSchedule;
use App\Services\BalanceService;
use App\Services\RevenueAllocationService;
use App\Services\RevenueCalculatorService;

it('creates daily revenue schedules that sum to instructor share', function () {
    $instructorA = Instructor::factory()->create();
    $instructorB = Instructor::factory()->create();

    $subscription = $this->createSubscriptionWithInstructors(
        amountPaid: 30000,
        platformFeePercentage: 10,
        allocations: [
            $instructorA->id => 60,
            $instructorB->id => 40,
        ],
        plan: SubscriptionPlanEnum::Monthly,
    );

    $calculator = new RevenueCalculatorService;
    $net = $calculator->calculateNetRevenue(30000, 10);

    $schedules = RevenueSchedule::query()->get();
    expect($schedules)->toHaveCount(60);

    $sumA = RevenueSchedule::query()->where('instructor_id', $instructorA->id)->sum('amount');
    $sumB = RevenueSchedule::query()->where('instructor_id', $instructorB->id)->sum('amount');

    expect($sumA + $sumB)->toBe($net);
});

it('is idempotent when allocating the same subscription twice', function () {
    $subscription = $this->createSubscriptionWithInstructors();

    $service = app(RevenueAllocationService::class);
    $service->allocateForSubscription($subscription);

    expect(RevenueSchedule::query()->count())->toBe(30);
});

it('accrues due schedules into ledger entries once', function () {
    $subscription = $this->createSubscriptionWithInstructors(amountPaid: 3000, platformFeePercentage: 0);

    $this->accrueAll($subscription);

    expect(LedgerEntry::query()->where('type', LedgerTypeEnum::EARNING)->count())->toBe(30);
    expect(RevenueSchedule::query()->where('processed', false)->count())->toBe(0);

    $instructor = $subscription->instructors->first();
    $balance = app(BalanceService::class)->getOutstandingBalance($instructor);

    expect($balance)->toBe(3000);
});
