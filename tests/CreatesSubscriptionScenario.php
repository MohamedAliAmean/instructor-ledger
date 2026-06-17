<?php

namespace Tests;

use App\Contracts\PaymentProviderInterface;
use App\Enums\SubscriptionPlanEnum;
use App\Enums\SubscriptionStatusEnum;
use App\Models\Instructor;
use App\Models\Student;
use App\Models\Subscription;
use App\Services\Payment\MockPaymentProvider;
use App\Services\RevenueAccrualService;
use App\Services\RevenueAllocationService;
use Carbon\Carbon;

trait CreatesSubscriptionScenario
{
    protected function createSubscriptionWithInstructors(
        int $amountPaid = 10000,
        int $platformFeePercentage = 20,
        array $allocations = [],
        SubscriptionPlanEnum $plan = SubscriptionPlanEnum::Monthly,
    ): Subscription {
        $student = Student::factory()->create();
        $startsAt = Carbon::parse('2026-01-01');

        $subscription = Subscription::factory()->create([
            'student_id' => $student->id,
            'plan_type' => $plan,
            'amount_paid' => $amountPaid,
            'platform_fee_percentage' => $platformFeePercentage,
            'starts_at' => $startsAt,
            'ends_at' => $startsAt->copy()->addDays($plan->days()),
            'status' => SubscriptionStatusEnum::Active,
        ]);

        if ($allocations === []) {
            $instructor = Instructor::factory()->create();
            $allocations = [$instructor->id => 100];
        }

        $pivotData = collect($allocations)
            ->mapWithKeys(fn (int $percentage, int $instructorId) => [
                $instructorId => ['allocation_percentage' => $percentage],
            ])
            ->all();

        $subscription->instructors()->attach($pivotData);

        app(RevenueAllocationService::class)->allocateForSubscription($subscription->fresh('instructors'));

        return $subscription->fresh('instructors');
    }

    protected function accrueAll(Subscription $subscription): void
    {
        app(RevenueAccrualService::class)->processDueSchedules($subscription->ends_at);
    }

    protected function mockProvider(): MockPaymentProvider
    {
        $provider = new MockPaymentProvider;
        $this->app->instance(PaymentProviderInterface::class, $provider);

        return $provider;
    }
}
