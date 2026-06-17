<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class ProcessPayoutsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'batch_key' => ['nullable', 'string', 'max:255'],
            'sync' => ['nullable', 'boolean'],
        ];
    }
}
