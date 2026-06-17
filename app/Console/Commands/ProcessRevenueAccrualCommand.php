<?php

namespace App\Console\Commands;

use App\Services\RevenueAccrualService;
use Illuminate\Console\Command;

class ProcessRevenueAccrualCommand extends Command
{
    protected $signature = 'revenue:accrue {--date= : Accrue revenue up to this date (Y-m-d)}';

    protected $description = 'Move due revenue schedules into instructor ledger entries';

    public function handle(RevenueAccrualService $accrualService): int
    {
        $asOf = $this->option('date') ? now()->parse($this->option('date')) : now();

        $count = $accrualService->processDueSchedules($asOf);

        $this->info("Processed {$count} revenue schedule(s).");

        return self::SUCCESS;
    }
}
