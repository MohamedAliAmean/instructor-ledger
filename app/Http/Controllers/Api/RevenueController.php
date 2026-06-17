<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ProcessRevenueAccrualRequest;
use App\Services\RevenueAccrualService;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

class RevenueController extends Controller
{
    #[OA\Post(
        path: '/revenue/accrue',
        summary: 'Accrue due revenue schedules into instructor ledger',
        tags: ['Revenue'],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: 'as_of', type: 'string', format: 'date', example: '2026-01-31'),
                ],
            ),
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Accrual completed',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'processed_count', type: 'integer', example: 30),
                        new OA\Property(property: 'as_of', type: 'string', example: '2026-01-31'),
                    ],
                ),
            ),
        ],
    )]
    public function accrue(ProcessRevenueAccrualRequest $request, RevenueAccrualService $accrualService): JsonResponse
    {
        $asOf = $request->validated('as_of')
            ? \Carbon\Carbon::parse($request->validated('as_of'))
            : now();

        $count = $accrualService->processDueSchedules($asOf);

        return response()->json([
            'processed_count' => $count,
            'as_of' => $asOf->toDateString(),
        ]);
    }
}
