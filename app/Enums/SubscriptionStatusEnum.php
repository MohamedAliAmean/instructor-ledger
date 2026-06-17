<?php

namespace App\Enums;

enum SubscriptionStatusEnum: string
{
    case Active = 'active';
    case Refunded = 'refunded';
    case Cancelled = 'cancelled';
}
