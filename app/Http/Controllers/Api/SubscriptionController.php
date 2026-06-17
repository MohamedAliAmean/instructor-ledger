<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreSubscriptionRequest;
use App\Http\Requests\Api\RefundSubscriptionRequest;
use App\Http\Resources\SubscriptionResource;
use App\Models\Subscription;
use App\Services\RefundService;
use App\Services\SubscriptionService;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

class SubscriptionController extends Controller
{
    public function __construct(
        private readonly SubscriptionService $subscriptionService,
        private readonly RefundService $refundService,
    ) {}

    #[OA\Post(
        path: '/subscriptions',
        summary: 'Create subscription and allocate revenue schedules',
        tags: ['Subscriptions'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['student_name', 'plan_type', 'amount_paid', 'platform_fee_percentage', 'starts_at', 'instructors'],
                properties: [
                    new OA\Property(property: 'student_name', type: 'string', example: 'Sara Mohamed'),
                    new OA\Property(property: 'plan_type', type: 'string', enum: ['monthly', 'quarterly', 'yearly'], example: 'monthly'),
                    new OA\Property(property: 'amount_paid', type: 'integer', description: 'Amount in cents', example: 29900),
                    new OA\Property(property: 'platform_fee_percentage', type: 'integer', example: 20),
                    new OA\Property(property: 'starts_at', type: 'string', format: 'date', example: '2026-01-01'),
                    new OA\Property(
                        property: 'instructors',
                        type: 'array',
                        items: new OA\Items(
                            properties: [
                                new OA\Property(property: 'id', type: 'integer', example: 1),
                                new OA\Property(property: 'allocation_percentage', type: 'integer', example: 60),
                            ],
                            type: 'object',
                        ),
                    ),
                ],
            ),
        ),
        responses: [
            new OA\Response(response: 201, description: 'Subscription created', content: new OA\JsonContent(ref: '#/components/schemas/Subscription')),
            new OA\Response(response: 422, description: 'Validation error'),
        ],
    )]
    public function store(StoreSubscriptionRequest $request): JsonResponse
    {
        $subscription = $this->subscriptionService->create($request->validated());

        return (new SubscriptionResource($subscription))
            ->response()
            ->setStatusCode(201);
    }

    #[OA\Get(
        path: '/subscriptions/{id}',
        summary: 'Get subscription details',
        tags: ['Subscriptions'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Successful response', content: new OA\JsonContent(ref: '#/components/schemas/Subscription')),
            new OA\Response(response: 404, description: 'Subscription not found'),
        ],
    )]
    public function show(Subscription $subscription): SubscriptionResource
    {
        return new SubscriptionResource($subscription->load(['student', 'instructors']));
    }

    #[OA\Post(
        path: '/subscriptions/{id}/refund',
        summary: 'Process mid-term refund (cancels future unearned schedules)',
        tags: ['Subscriptions'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'refunded_at', type: 'string', format: 'date', example: '2026-01-15'),
                ],
            ),
        ),
        responses: [
            new OA\Response(response: 200, description: 'Refund processed', content: new OA\JsonContent(ref: '#/components/schemas/Subscription')),
            new OA\Response(response: 404, description: 'Subscription not found'),
        ],
    )]
    public function refund(RefundSubscriptionRequest $request, Subscription $subscription): SubscriptionResource
    {
        $refundedAt = $request->validated('refunded_at')
            ? \Carbon\Carbon::parse($request->validated('refunded_at'))
            : now();

        $this->refundService->processRefund($subscription, $refundedAt);

        return new SubscriptionResource($subscription->fresh(['student', 'instructors']));
    }
}

#[OA\Schema(
    schema: 'Subscription',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'student_id', type: 'integer', example: 1),
        new OA\Property(property: 'student_name', type: 'string', example: 'Sara Mohamed'),
        new OA\Property(property: 'plan_type', type: 'string', example: 'monthly'),
        new OA\Property(property: 'amount_paid_cents', type: 'integer', example: 29900),
        new OA\Property(property: 'status', type: 'string', example: 'active'),
    ],
)]
class SubscriptionSchema
{
}
