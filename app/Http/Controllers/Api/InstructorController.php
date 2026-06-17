<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\InstructorResource;
use App\Http\Resources\PayoutResource;
use App\Models\Instructor;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use OpenApi\Attributes as OA;

class InstructorController extends Controller
{
    #[OA\Get(
        path: '/instructors',
        summary: 'List instructors with balances',
        tags: ['Instructors'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Successful response',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/Instructor')),
                    ],
                ),
            ),
        ],
    )]
    public function index(): AnonymousResourceCollection
    {
        return InstructorResource::collection(
            Instructor::query()->orderBy('name')->get(),
        );
    }

    #[OA\Get(
        path: '/instructors/{id}',
        summary: 'Get instructor balance details',
        tags: ['Instructors'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Successful response', content: new OA\JsonContent(ref: '#/components/schemas/Instructor')),
            new OA\Response(response: 404, description: 'Instructor not found'),
        ],
    )]
    public function show(Instructor $instructor): InstructorResource
    {
        return new InstructorResource($instructor);
    }

    #[OA\Get(
        path: '/instructors/{id}/payouts',
        summary: 'List payout history for an instructor',
        tags: ['Instructors'],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Successful response'),
            new OA\Response(response: 404, description: 'Instructor not found'),
        ],
    )]
    public function payouts(Instructor $instructor): AnonymousResourceCollection
    {
        return PayoutResource::collection(
            $instructor->payouts()->with('batch')->latest()->get(),
        );
    }
}

#[OA\Schema(
    schema: 'Instructor',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'name', type: 'string', example: 'Ahmed Hassan'),
        new OA\Property(
            property: 'balance',
            properties: [
                new OA\Property(property: 'outstanding_cents', type: 'integer', example: 5000),
                new OA\Property(property: 'outstanding', type: 'string', example: '50.00 EGP'),
                new OA\Property(property: 'total_earned_cents', type: 'integer', example: 8000),
                new OA\Property(property: 'total_earned', type: 'string', example: '80.00 EGP'),
                new OA\Property(property: 'total_paid_cents', type: 'integer', example: 3000),
                new OA\Property(property: 'total_paid', type: 'string', example: '30.00 EGP'),
            ],
            type: 'object',
        ),
    ],
)]
class InstructorSchema
{
}
