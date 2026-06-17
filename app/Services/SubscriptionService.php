<?php

namespace App\Services;

use App\Enums\SubscriptionPlanEnum;
use App\Enums\SubscriptionStatusEnum;
use App\Models\Student;
use App\Models\Subscription;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class SubscriptionService
{
    public function __construct(
        private readonly RevenueAllocationService $allocationService,
    ) {}

    public function create(array $data): Subscription
    {
        return DB::transaction(function () use ($data): Subscription {
            $student = Student::query()->create([
                'name' => $data['student_name'],
            ]);

            $plan = $data['plan_type'] instanceof SubscriptionPlanEnum
                ? $data['plan_type']
                : SubscriptionPlanEnum::from($data['plan_type']);
            $startsAt = Carbon::parse($data['starts_at'])->startOfDay();

            $subscription = Subscription::query()->create([
                'student_id' => $student->id,
                'plan_type' => $plan,
                'amount_paid' => $data['amount_paid'],
                'platform_fee_percentage' => $data['platform_fee_percentage'],
                'starts_at' => $startsAt,
                'ends_at' => $startsAt->copy()->addDays($plan->days()),
                'status' => SubscriptionStatusEnum::Active,
            ]);

            $pivotData = collect($data['instructors'])
                ->mapWithKeys(fn (array $row) => [
                    $row['id'] => ['allocation_percentage' => $row['allocation_percentage']],
                ])
                ->all();

            $subscription->instructors()->attach($pivotData);

            $this->allocationService->allocateForSubscription($subscription->fresh('instructors'));

            return $subscription->fresh(['student', 'instructors']);
        });
    }
}
