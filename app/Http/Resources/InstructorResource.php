<?php

namespace App\Http\Resources;

use App\Services\BalanceService;
use App\Support\Money;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Instructor */
class InstructorResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $balanceService = app(BalanceService::class);

        return [
            'id' => $this->id,
            'name' => $this->name,
            'balance' => [
                'outstanding_cents' => $balanceService->getOutstandingBalance($this->resource),
                'outstanding' => Money::format($balanceService->getOutstandingBalance($this->resource)),
                'total_earned_cents' => $balanceService->getTotalEarned($this->resource),
                'total_earned' => Money::format($balanceService->getTotalEarned($this->resource)),
                'total_paid_cents' => $balanceService->getTotalPaid($this->resource),
                'total_paid' => Money::format($balanceService->getTotalPaid($this->resource)),
            ],
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
