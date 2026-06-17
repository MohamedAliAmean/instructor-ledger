<?php

namespace App\Models;

use App\Enums\PayoutBatchStatusEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PayoutBatch extends Model
{
    /** @use HasFactory<\Database\Factories\PayoutBatchFactory> */
    use HasFactory;

    protected $fillable = [
        'batch_key',
        'status',
    ];

    protected $casts = [
        'status' => PayoutBatchStatusEnum::class,
    ];

    public function payouts(): HasMany
    {
        return $this->hasMany(Payout::class, 'batch_id');
    }
}
