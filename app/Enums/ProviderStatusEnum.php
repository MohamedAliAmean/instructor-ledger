<?php

namespace App\Enums;

enum ProviderStatusEnum: string
{
    case Success = 'success';
    case Failed = 'failed';
    case Unknown = 'unknown';
}
