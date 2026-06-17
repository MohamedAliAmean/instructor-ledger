<?php

namespace App\Enums;

enum PayoutStatusEnum: string
{
    case Pending = 'pending';
    case Processing = 'processing';
    case Paid = 'paid';
    case Failed = 'failed';
    case PendingConfirmation = 'pending_confirmation';
}
