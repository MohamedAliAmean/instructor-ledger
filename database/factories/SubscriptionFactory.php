<?php

namespace Database\Factories;

use App\Enums\SubscriptionPlanEnum;
use App\Enums\SubscriptionStatusEnum;
use App\Models\Student;
use App\Models\Subscription;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Subscription>
 */
class SubscriptionFactory extends Factory
{
    protected $model = Subscription::class;

    public function definition(): array
    {
        $plan = fake()->randomElement(SubscriptionPlanEnum::cases());
        $startsAt = now()->startOfDay();

        return [
            'student_id' => Student::factory(),
            'plan_type' => $plan,
            'amount_paid' => match ($plan) {
                SubscriptionPlanEnum::Monthly => 29900,
                SubscriptionPlanEnum::Quarterly => 79900,
                SubscriptionPlanEnum::Yearly => 249900,
            },
            'platform_fee_percentage' => 20,
            'starts_at' => $startsAt,
            'ends_at' => $startsAt->copy()->addDays($plan->days()),
            'status' => SubscriptionStatusEnum::Active,
        ];
    }
}
