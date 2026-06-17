<?php
namespace App\Models;

use App\Enums\LedgerTypeEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class LedgerEntry extends Model
{
    protected $fillable = [
        'instructor_id',
        'subscription_id',
        'type',
        'amount',
        'reference_type',
        'reference_id',
    ];

    protected $casts = [
        'type' => LedgerTypeEnum::class,
    ];

    public function instructor(): BelongsTo
    {
        return $this->belongsTo(Instructor::class);
    }

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }
}
