<?php
namespace App\Services;

use App\Enums\LedgerTypeEnum;
use App\Models\Instructor;

class BalanceService
{
    public function getOutstandingBalance(
        Instructor $instructor
    ): int {

        return (int) $instructor
            ->ledgerEntries()
            ->sum('amount');
    }

    public function getTotalEarned(
        Instructor $instructor
    ): int {

        return (int) $instructor
            ->ledgerEntries()
            ->where('type', LedgerTypeEnum::EARNING)
            ->sum('amount');
    }

    public function getTotalPaid(
        Instructor $instructor
    ): int {

        return abs(
            (int) $instructor
                ->ledgerEntries()
                ->where('type', LedgerTypeEnum::PAYOUT)
                ->sum('amount')
        );
    }
}
