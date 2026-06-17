<?php

namespace App\Jobs;

use App\Models\Payout;
use App\Services\PayoutService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProcessPayoutJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(public Payout $payout) {}

    public function handle(PayoutService $payoutService): void
    {
        $payoutService->processPayout($this->payout);
    }

    public function uniqueId(): string
    {
        return (string) $this->payout->id;
    }
}
