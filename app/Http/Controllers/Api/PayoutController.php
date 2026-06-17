<?php

namespace App\Http\Controllers\Api;

use App\Enums\PayoutStatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ProcessPayoutsRequest;
use App\Http\Resources\PayoutResource;
use App\Jobs\ConfirmPayoutStatusJob;
use App\Models\Payout;
use App\Services\PayoutService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use OpenApi\Attributes as OA;

class PayoutController extends Controller
{
    public function __construct(
        private readonly PayoutService $payoutService,
    ) {}

    #[OA\Get(
        path: '/payouts',
        summary: 'List all payouts',
        tags: ['Payouts'],
        responses: [
            new OA\Response(response: 200, description: 'Successful response'),
        ],
    )]
    public function index(): AnonymousResourceCollection
    {
        return PayoutResource::collection(
            Payout::query()->with(['batch', 'instructor'])->latest()->get(),
        );
    }

    #[OA\Get(
        path: '/payouts/{id}',
        summary: 'Get payout details',
        tags: ['Payouts'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Successful response'),
            new OA\Response(response: 404, description: 'Payout not found'),
        ],
    )]
    public function show(Payout $payout): PayoutResource
    {
        return new PayoutResource($payout->load('batch'));
    }

    #[OA\Post(
        path: '/payouts/process',
        summary: 'Create payout batch and process instructor payments',
        tags: ['Payouts'],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'batch_key', type: 'string', example: 'payout-2026-06-15'),
                    new OA\Property(property: 'sync', type: 'boolean', description: 'Process payouts synchronously (useful for testing)', example: true),
                ],
            ),
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Payout batch created',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'batch_key', type: 'string'),
                        new OA\Property(property: 'payouts_count', type: 'integer'),
                        new OA\Property(property: 'payouts', type: 'array', items: new OA\Items(ref: '#/components/schemas/Payout')),
                    ],
                ),
            ),
        ],
    )]
    public function process(ProcessPayoutsRequest $request): JsonResponse
    {
        $batchKey = $request->validated('batch_key') ?? 'payout-'.now()->toDateString();
        $sync = (bool) $request->validated('sync', false);

        $batch = $this->payoutService->createBatch($batchKey);

        if ($sync) {
            $batch->payouts->each(fn (Payout $payout) => $this->payoutService->processPayout($payout));
            $batch->payouts
                ->filter(fn (Payout $p) => $p->fresh()->status === PayoutStatusEnum::PendingConfirmation)
                ->each(fn (Payout $payout) => $this->payoutService->confirmPayout($payout->fresh()));
        } else {
            $this->payoutService->dispatchBatch($batch);
            $batch->payouts
                ->filter(fn (Payout $p) => $p->status === PayoutStatusEnum::PendingConfirmation)
                ->each(fn (Payout $payout) => ConfirmPayoutStatusJob::dispatch($payout));
        }

        $batch->load('payouts');

        return response()->json([
            'batch_key' => $batch->batch_key,
            'payouts_count' => $batch->payouts->count(),
            'payouts' => PayoutResource::collection($batch->payouts),
        ]);
    }
}

#[OA\Schema(
    schema: 'Payout',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'instructor_id', type: 'integer', example: 1),
        new OA\Property(property: 'amount_cents', type: 'integer', example: 5000),
        new OA\Property(property: 'status', type: 'string', example: 'paid'),
        new OA\Property(property: 'idempotency_key', type: 'string'),
    ],
)]
class PayoutSchema
{
}
