<?php

namespace App\Models;

use App\Enums\PayoutStatusEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Payout extends Model
{
    /** @use HasFactory<\Database\Factories\PayoutFactory> */
    use HasFactory;

    protected $fillable = [
        'batch_id',
        'instructor_id',
        'amount',
        'status',
        'idempotency_key',
        'provider_reference',
    ];

    protected $casts = [
        'status' => PayoutStatusEnum::class,
    ];

    public function batch(): BelongsTo
    {
        return $this->belongsTo(PayoutBatch::class, 'batch_id');
    }

    public function instructor(): BelongsTo
    {
        return $this->belongsTo(Instructor::class);
    }

    public function ledgerEntries(): MorphMany
    {
        return $this->morphMany(LedgerEntry::class, 'reference');
    }
}
