<?php

namespace App\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: '1.0.0',
    title: 'Instructor Revenue Ledger API',
    description: 'REST API for subscription revenue allocation, instructor balances, and payouts.',
)]
#[OA\Server(
    url: '/api',
    description: 'Local API server',
)]
#[OA\Tag(name: 'Instructors', description: 'Instructor balances and payout history')]
#[OA\Tag(name: 'Subscriptions', description: 'Subscription lifecycle')]
#[OA\Tag(name: 'Revenue', description: 'Revenue accrual operations')]
#[OA\Tag(name: 'Payouts', description: 'Instructor payout operations')]
class ApiDocumentation
{
}
