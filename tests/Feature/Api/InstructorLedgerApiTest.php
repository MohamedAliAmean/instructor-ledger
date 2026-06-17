<?php

use App\Models\Instructor;
use App\Services\Payment\MockPaymentProvider;

it('lists instructors via api', function () {
    Instructor::factory()->count(2)->create();

    $response = $this->getJson('/api/instructors');

    $response->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonStructure([
            'data' => [
                '*' => ['id', 'name', 'balance'],
            ],
        ]);
});

it('creates subscription via api and allocates revenue', function () {
    $instructor = Instructor::factory()->create();

    $response = $this->postJson('/api/subscriptions', [
        'student_name' => 'API Student',
        'plan_type' => 'monthly',
        'amount_paid' => 3000,
        'platform_fee_percentage' => 0,
        'starts_at' => '2026-01-01',
        'instructors' => [
            ['id' => $instructor->id, 'allocation_percentage' => 100],
        ],
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.student_name', 'API Student')
        ->assertJsonPath('data.plan_type', 'monthly');
});

it('accrues revenue via api', function () {
    $instructor = Instructor::factory()->create();

    $this->postJson('/api/subscriptions', [
        'student_name' => 'Accrual Student',
        'plan_type' => 'monthly',
        'amount_paid' => 3000,
        'platform_fee_percentage' => 0,
        'starts_at' => '2026-01-01',
        'instructors' => [
            ['id' => $instructor->id, 'allocation_percentage' => 100],
        ],
    ]);

    $response = $this->postJson('/api/revenue/accrue', [
        'as_of' => '2026-01-31',
    ]);

    $response->assertOk()
        ->assertJsonPath('processed_count', 30);
});

it('processes payouts synchronously via api', function () {
    $this->mockProvider()->forceOutcome(MockPaymentProvider::OUTCOME_SUCCESS);

    $instructor = Instructor::factory()->create();

    $this->postJson('/api/subscriptions', [
        'student_name' => 'Payout Student',
        'plan_type' => 'monthly',
        'amount_paid' => 3000,
        'platform_fee_percentage' => 0,
        'starts_at' => '2026-01-01',
        'instructors' => [
            ['id' => $instructor->id, 'allocation_percentage' => 100],
        ],
    ]);

    $this->postJson('/api/revenue/accrue', ['as_of' => '2026-01-31']);

    $response = $this->postJson('/api/payouts/process', [
        'batch_key' => 'api-test-batch',
        'sync' => true,
    ]);

    $response->assertOk()
        ->assertJsonPath('payouts_count', 1)
        ->assertJsonPath('payouts.0.status', 'paid');
});

it('returns swagger documentation ui', function () {
    $this->get('/api/documentation')->assertOk();
});
