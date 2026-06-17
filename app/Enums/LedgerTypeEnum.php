<?php
namespace App\Enums;

enum LedgerTypeEnum: string {
    case EARNING    = 'earning';
    case REFUND     = 'refund';
    case PAYOUT     = 'payout';
    case ADJUSTMENT = 'adjustment';
}
