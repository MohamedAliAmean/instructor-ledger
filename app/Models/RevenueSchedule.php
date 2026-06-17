<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RevenueSchedule extends Model
{
    /** @use HasFactory<\Database\Factories\RevenueScheduleFactory> */
    use HasFactory;

    protected $fillable = [
        'subscription_id',
        'instructor_id',
        'amount',
        'earned_at',
        'processed',
    ];

    protected $casts = [
        'earned_at' => 'date',
        'processed' => 'boolean',
    ];

    public function subscription(): BelongsTo
    {
        return $this->belongsTo(Subscription::class);
    }

    public function instructor(): BelongsTo
    {
        return $this->belongsTo(Instructor::class);
    }
}
