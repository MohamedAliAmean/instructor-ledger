<?php

namespace App\Models;

use App\Enums\SubscriptionPlanEnum;
use App\Enums\SubscriptionStatusEnum;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subscription extends Model
{
    /** @use HasFactory<\Database\Factories\SubscriptionFactory> */
    use HasFactory;

    protected $fillable = [
        'student_id',
        'plan_type',
        'amount_paid',
        'platform_fee_percentage',
        'starts_at',
        'ends_at',
        'status',
    ];

    protected $casts = [
        'plan_type' => SubscriptionPlanEnum::class,
        'status' => SubscriptionStatusEnum::class,
        'starts_at' => 'date',
        'ends_at' => 'date',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function instructors(): BelongsToMany
    {
        return $this->belongsToMany(
            Instructor::class,
            'subscription_instructors'
        )->withPivot('allocation_percentage');
    }

    public function ledgerEntries(): HasMany
    {
        return $this->hasMany(LedgerEntry::class);
    }

    public function revenueSchedules(): HasMany
    {
        return $this->hasMany(RevenueSchedule::class);
    }
}
