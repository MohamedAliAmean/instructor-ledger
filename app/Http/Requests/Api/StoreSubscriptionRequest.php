<?php

namespace App\Http\Requests\Api;

use App\Enums\SubscriptionPlanEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSubscriptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'student_name' => ['required', 'string', 'max:255'],
            'plan_type' => ['required', Rule::enum(SubscriptionPlanEnum::class)],
            'amount_paid' => ['required', 'integer', 'min:1'],
            'platform_fee_percentage' => ['required', 'integer', 'min:0', 'max:100'],
            'starts_at' => ['required', 'date'],
            'instructors' => ['required', 'array', 'min:1'],
            'instructors.*.id' => ['required', 'integer', 'exists:instructors,id'],
            'instructors.*.allocation_percentage' => ['required', 'integer', 'min:1', 'max:100'],
        ];
    }
}
