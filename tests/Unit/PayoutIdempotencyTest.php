<?php

use App\Enums\LedgerTypeEnum;
use App\Enums\PayoutStatusEnum;
use App\Jobs\ProcessPayoutJob;
use App\Models\LedgerEntry;
use App\Models\Payout;
use App\Services\BalanceService;
use App\Services\Payment\MockPaymentProvider;
use App\Services\PayoutService;

it('never double-pays when payout process runs twice', function () {
    $provider = $this->mockProvider()->forceOutcome(MockPaymentProvider::OUTCOME_SUCCESS);

    $subscription = $this->createSubscriptionWithInstructors(amountPaid: 5000, platformFeePercentage: 0);
    $this->accrueAll($subscription);

    $instructor = $subscription->instructors->first();
    $batchKey = 'test-batch-1';

    $payoutService = app(PayoutService::class);

    $batchOne = $payoutService->createBatch($batchKey);
    $batchOne->payouts->each(fn (Payout $payout) => $payoutService->processPayout($payout));

    $batchTwo = $payoutService->createBatch($batchKey);
    $batchTwo->payouts->each(fn (Payout $payout) => $payoutService->processPayout($payout));

    expect(Payout::query()->where('instructor_id', $instructor->id)->where('status', PayoutStatusEnum::Paid)->count())->toBe(1);
    expect(app(BalanceService::class)->getOutstandingBalance($instructor))->toBe(0);
    expect(LedgerEntry::query()->where('type', LedgerTypeEnum::PAYOUT)->count())->toBe(1);
});

it('never double-pays when the payout job is retried', function () {
    $this->mockProvider()->forceOutcome(MockPaymentProvider::OUTCOME_SUCCESS);

    $subscription = $this->createSubscriptionWithInstructors(amountPaid: 4000, platformFeePercentage: 0);
    $this->accrueAll($subscription);

    $batch = app(PayoutService::class)->createBatch('retry-batch');
    $payout = $batch->payouts->first();

    $job = new ProcessPayoutJob($payout);
    $job->handle(app(PayoutService::class));
    $job->handle(app(PayoutService::class));

    expect(Payout::query()->find($payout->id)->status)->toBe(PayoutStatusEnum::Paid);
    expect(LedgerEntry::query()->where('type', LedgerTypeEnum::PAYOUT)->count())->toBe(1);
});

it('handles provider timeout without duplicate payment on retry', function () {
    $provider = $this->mockProvider()->forceOutcome(MockPaymentProvider::OUTCOME_TIMEOUT_SUCCESS);

    $subscription = $this->createSubscriptionWithInstructors(amountPaid: 6000, platformFeePercentage: 0);
    $this->accrueAll($subscription);

    $payoutService = app(PayoutService::class);
    $payout = $payoutService->createBatch('timeout-batch')->payouts->first();

    $payoutService->processPayout($payout);
    $payout->refresh();

    expect($payout->status)->toBe(PayoutStatusEnum::PendingConfirmation);
    expect($payout->provider_reference)->not->toBeNull();

    $payoutService->processPayout($payout);
    $payout->refresh();

    expect($payout->status)->toBe(PayoutStatusEnum::Paid);
    expect(LedgerEntry::query()->where('type', LedgerTypeEnum::PAYOUT)->count())->toBe(1);
});

it('reuses provider transfer for the same idempotency key', function () {
    $provider = $this->mockProvider()->forceOutcome(MockPaymentProvider::OUTCOME_SUCCESS);

    $first = $provider->transfer('same-key', 1500);
    $second = $provider->transfer('same-key', 1500);

    expect($first->providerReference)->toBe($second->providerReference);
});
