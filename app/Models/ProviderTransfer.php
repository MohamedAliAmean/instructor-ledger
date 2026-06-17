<?php

namespace App\Models;

use App\Enums\ProviderTransferStatusEnum;
use Illuminate\Database\Eloquent\Model;

class ProviderTransfer extends Model
{
    protected $fillable = [
        'provider_reference',
        'idempotency_key',
        'amount',
        'status',
    ];

    protected $casts = [
        'status' => ProviderTransferStatusEnum::class,
    ];
}
