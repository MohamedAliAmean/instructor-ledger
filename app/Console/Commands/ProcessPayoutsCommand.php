<?php

namespace App\Console\Commands;

use App\Enums\PayoutBatchStatusEnum;
use App\Enums\PayoutStatusEnum;
use App\Jobs\ConfirmPayoutStatusJob;
use App\Models\Payout;
use App\Models\PayoutBatch;
use App\Services\PayoutService;
use Illuminate\Console\Command;

class ProcessPayoutsCommand extends Command
{
    protected $signature = 'payouts:process {--batch-key= : Optional stable batch key for idempotent runs}';

    protected $description = 'Create payout batch and dispatch jobs to pay instructors their outstanding balances';

    public function handle(PayoutService $payoutService): int
    {
        $batchKey = $this->option('batch-key') ?? 'payout-'.now()->toDateString();

        $this->info("Processing payouts for batch [{$batchKey}]");

        $batch = $payoutService->createBatch($batchKey);

        $payoutService->dispatchBatch($batch);

        Payout::query()
            ->where('batch_id', $batch->id)
            ->where('status', PayoutStatusEnum::PendingConfirmation)
            ->each(fn (Payout $payout) => ConfirmPayoutStatusJob::dispatch($payout));

        $this->finalizeBatch($batch);

        $this->info("Dispatched {$batch->payouts()->count()} payout job(s).");

        return self::SUCCESS;
    }

    private function finalizeBatch(PayoutBatch $batch): void
    {
        $pending = $batch->payouts()
            ->whereIn('status', [
                PayoutStatusEnum::Pending,
                PayoutStatusEnum::Processing,
                PayoutStatusEnum::PendingConfirmation,
            ])
            ->exists();

        if (! $pending) {
            $batch->update(['status' => PayoutBatchStatusEnum::Completed]);
        }
    }
}
