<?php

namespace App\Enums;

enum ProviderTransferStatusEnum: string
{
    case Completed = 'completed';
    case Failed = 'failed';
}
