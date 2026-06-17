<?php

namespace Database\Factories;

use App\Enums\PayoutStatusEnum;
use App\Models\Instructor;
use App\Models\Payout;
use App\Models\PayoutBatch;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payout>
 */
class PayoutFactory extends Factory
{
    protected $model = Payout::class;

    public function definition(): array
    {
        $batch = PayoutBatch::factory()->create();
        $instructor = Instructor::factory()->create();

        return [
            'batch_id' => $batch->id,
            'instructor_id' => $instructor->id,
            'amount' => fake()->numberBetween(1000, 50000),
            'status' => PayoutStatusEnum::Pending,
            'idempotency_key' => "{$batch->batch_key}:instructor:{$instructor->id}",
            'provider_reference' => null,
        ];
    }
}
