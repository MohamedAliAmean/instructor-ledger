<?php

namespace App\Services;

use App\Models\RevenueSchedule;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

class RevenueAccrualService
{
    public function __construct(
        private readonly LedgerService $ledgerService,
    ) {}

    public function processDueSchedules(?CarbonInterface $asOf = null): int
    {
        $asOf ??= now();
        $processedCount = 0;

        RevenueSchedule::query()
            ->where('processed', false)
            ->whereDate('earned_at', '<=', $asOf->toDateString())
            ->orderBy('id')
            ->chunkById(500, function ($schedules) use (&$processedCount): void {
                foreach ($schedules as $schedule) {
                    if ($this->processSchedule($schedule)) {
                        $processedCount++;
                    }
                }
            });

        return $processedCount;
    }

    public function processSchedule(RevenueSchedule $schedule): bool
    {
        return (bool) DB::transaction(function () use ($schedule): bool {
            $locked = RevenueSchedule::query()
                ->whereKey($schedule->id)
                ->lockForUpdate()
                ->first();

            if ($locked === null || $locked->processed) {
                return false;
            }

            $this->ledgerService->recordEarningFromSchedule($locked);

            $locked->update(['processed' => true]);

            return true;
        });
    }
}
