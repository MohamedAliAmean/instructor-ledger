<?php

namespace App\Enums;

enum PayoutBatchStatusEnum: string
{
    case Pending = 'pending';
    case Processing = 'processing';
    case Completed = 'completed';
}
