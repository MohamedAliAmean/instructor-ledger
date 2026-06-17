<?php

namespace Database\Factories;

use App\Enums\PayoutBatchStatusEnum;
use App\Models\PayoutBatch;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PayoutBatch>
 */
class PayoutBatchFactory extends Factory
{
    protected $model = PayoutBatch::class;

    public function definition(): array
    {
        return [
            'batch_key' => 'payout-'.fake()->unique()->uuid(),
            'status' => PayoutBatchStatusEnum::Pending,
        ];
    }
}
