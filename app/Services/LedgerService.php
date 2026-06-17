<?php

namespace App\Services;

use App\Enums\LedgerTypeEnum;
use App\Models\Instructor;
use App\Models\LedgerEntry;
use App\Models\Payout;
use App\Models\RevenueSchedule;
use App\Models\Subscription;
use Illuminate\Database\Eloquent\Model;

class LedgerService
{
    public function recordEarningFromSchedule(RevenueSchedule $schedule): LedgerEntry
    {
        return LedgerEntry::query()->firstOrCreate(
            [
                'reference_type' => RevenueSchedule::class,
                'reference_id' => $schedule->id,
            ],
            [
                'instructor_id' => $schedule->instructor_id,
                'subscription_id' => $schedule->subscription_id,
                'type' => LedgerTypeEnum::EARNING,
                'amount' => $schedule->amount,
            ],
        );
    }

    public function recordRefund(
        Instructor $instructor,
        Subscription $subscription,
        int $amountCents,
        ?Model $reference = null,
    ): LedgerEntry {
        return LedgerEntry::query()->create([
            'instructor_id' => $instructor->id,
            'subscription_id' => $subscription->id,
            'type' => LedgerTypeEnum::REFUND,
            'amount' => -abs($amountCents),
            'reference_type' => $reference?->getMorphClass(),
            'reference_id' => $reference?->getKey(),
        ]);
    }

    public function recordPayout(Payout $payout): LedgerEntry
    {
        return LedgerEntry::query()->firstOrCreate(
            [
                'reference_type' => Payout::class,
                'reference_id' => $payout->id,
            ],
            [
                'instructor_id' => $payout->instructor_id,
                'type' => LedgerTypeEnum::PAYOUT,
                'amount' => -abs($payout->amount),
            ],
        );
    }
}
