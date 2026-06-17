<?php

namespace App\Services;

use App\Enums\SubscriptionStatusEnum;
use App\Models\Subscription;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

class RefundService
{
    public function __construct(
        private readonly LedgerService $ledgerService,
    ) {}

    /**
     * Instructors keep amounts already accrued to the ledger.
     * Future unearned schedules are cancelled.
     */
    public function processRefund(Subscription $subscription, ?CarbonInterface $refundedAt = null): void
    {
        $refundedAt ??= now();

        DB::transaction(function () use ($subscription, $refundedAt): void {
            $subscription->refresh();

            if ($subscription->status === SubscriptionStatusEnum::Refunded) {
                return;
            }

            $subscription->update([
                'status' => SubscriptionStatusEnum::Refunded,
                'ends_at' => $refundedAt->toDateString(),
            ]);

            $subscription->revenueSchedules()
                ->where('processed', false)
                ->whereDate('earned_at', '>', $refundedAt->toDateString())
                ->delete();
        });
    }
}
