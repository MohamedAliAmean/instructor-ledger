<?php

namespace App\Enums;

enum SubscriptionPlanEnum: string
{
    case Monthly = 'monthly';
    case Quarterly = 'quarterly';
    case Yearly = 'yearly';

    public function days(): int
    {
        return match ($this) {
            self::Monthly => 30,
            self::Quarterly => 90,
            self::Yearly => 365,
        };
    }
}
