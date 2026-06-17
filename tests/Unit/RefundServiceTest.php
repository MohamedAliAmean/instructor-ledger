<?php

use App\Models\RevenueSchedule;
use App\Services\BalanceService;
use App\Services\RefundService;
use Carbon\Carbon;

it('cancels unearned future schedules on refund', function () {
    $subscription = $this->createSubscriptionWithInstructors(
        amountPaid: 3000,
        platformFeePercentage: 0,
    );

    $refundDate = Carbon::parse('2026-01-10');

    app(RefundService::class)->processRefund($subscription, $refundDate);

    $remainingSchedules = RevenueSchedule::query()
        ->where('subscription_id', $subscription->id)
        ->count();

    expect($remainingSchedules)->toBe(10);

    app(\App\Services\RevenueAccrualService::class)->processDueSchedules($refundDate);

    $instructor = $subscription->instructors->first();
    $earned = app(BalanceService::class)->getTotalEarned($instructor);

    expect($earned)->toBe(1000);
});
