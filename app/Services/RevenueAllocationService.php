<?php

namespace App\Services;

use App\Models\RevenueSchedule;
use App\Models\Subscription;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

class RevenueAllocationService
{
    public function __construct(
        private readonly RevenueCalculatorService $calculator,
    ) {}

    public function allocateForSubscription(Subscription $subscription): void
    {
        $subscription->loadMissing('instructors');

        if ($subscription->revenueSchedules()->exists()) {
            return;
        }

        $allocations = $subscription->instructors
            ->mapWithKeys(fn ($instructor) => [
                $instructor->id => (int) $instructor->pivot->allocation_percentage,
            ])
            ->all();

        $netRevenue = $this->calculator->calculateNetRevenue(
            (int) $subscription->amount_paid,
            (int) $subscription->platform_fee_percentage,
        );

        $instructorShares = $this->calculator->allocateRevenue($netRevenue, $allocations);
        $days = $subscription->plan_type->days();

        DB::transaction(function () use ($subscription, $instructorShares, $days): void {
            foreach ($instructorShares as $instructorId => $totalShare) {
                $this->createDailySchedules(
                    $subscription,
                    (int) $instructorId,
                    $totalShare,
                    $days,
                );
            }
        });
    }

    private function createDailySchedules(
        Subscription $subscription,
        int $instructorId,
        int $totalShare,
        int $days,
    ): void {
        $dailyAmount = intdiv($totalShare, $days);
        $remainder = $totalShare - ($dailyAmount * $days);

        for ($day = 0; $day < $days; $day++) {
            $amount = $dailyAmount + ($day === $days - 1 ? $remainder : 0);

            RevenueSchedule::query()->create([
                'subscription_id' => $subscription->id,
                'instructor_id' => $instructorId,
                'amount' => $amount,
                'earned_at' => $subscription->starts_at->copy()->addDays($day),
                'processed' => false,
            ]);
        }
    }
}
