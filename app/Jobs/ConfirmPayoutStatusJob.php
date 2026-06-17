<?php

namespace App\Jobs;

use App\Enums\PayoutStatusEnum;
use App\Models\Payout;
use App\Services\PayoutService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ConfirmPayoutStatusJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 5;

    public function __construct(public Payout $payout) {}

    public function handle(PayoutService $payoutService): void
    {
        if ($this->payout->status !== PayoutStatusEnum::PendingConfirmation) {
            return;
        }

        $payoutService->confirmPayout($this->payout);
    }
}
