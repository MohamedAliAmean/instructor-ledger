<?php

namespace App\Http\Resources;

use App\Support\Money;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Payout */
class PayoutResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'batch_id' => $this->batch_id,
            'batch_key' => $this->whenLoaded('batch', fn () => $this->batch->batch_key),
            'instructor_id' => $this->instructor_id,
            'amount_cents' => $this->amount,
            'amount' => Money::format((int) $this->amount),
            'status' => $this->status->value,
            'idempotency_key' => $this->idempotency_key,
            'provider_reference' => $this->provider_reference,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
