<?php

namespace App\Http\Resources;

use App\Support\Money;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Subscription */
class SubscriptionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'student_id' => $this->student_id,
            'student_name' => $this->whenLoaded('student', fn () => $this->student->name),
            'plan_type' => $this->plan_type->value,
            'amount_paid_cents' => $this->amount_paid,
            'amount_paid' => Money::format((int) $this->amount_paid),
            'platform_fee_percentage' => $this->platform_fee_percentage,
            'starts_at' => $this->starts_at?->toDateString(),
            'ends_at' => $this->ends_at?->toDateString(),
            'status' => $this->status->value,
            'instructors' => $this->whenLoaded('instructors', fn () => $this->instructors->map(fn ($instructor) => [
                'id' => $instructor->id,
                'name' => $instructor->name,
                'allocation_percentage' => (int) $instructor->pivot->allocation_percentage,
            ])),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
