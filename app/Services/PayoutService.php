<?php

namespace App\Services;

use App\Contracts\PaymentProviderInterface;
use App\Enums\PayoutBatchStatusEnum;
use App\Enums\PayoutStatusEnum;
use App\Enums\ProviderStatusEnum;
use App\Exceptions\PaymentTimeoutException;
use App\Jobs\ProcessPayoutJob;
use App\Models\Instructor;
use App\Models\Payout;
use App\Models\PayoutBatch;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PayoutService
{
    public function __construct(
        private readonly PaymentProviderInterface $paymentProvider,
        private readonly BalanceService $balanceService,
        private readonly LedgerService $ledgerService,
    ) {}

    public function createBatch(string $batchKey): PayoutBatch
    {
        return DB::transaction(function () use ($batchKey): PayoutBatch {
            $batch = PayoutBatch::query()->firstOrCreate(
                ['batch_key' => $batchKey],
                ['status' => PayoutBatchStatusEnum::Pending],
            );

            if ($batch->status === PayoutBatchStatusEnum::Completed) {
                return $batch;
            }

            $batch->update(['status' => PayoutBatchStatusEnum::Processing]);

            $this->eligibleInstructors()->each(function (Instructor $instructor) use ($batch): void {
                $outstanding = $this->balanceService->getOutstandingBalance($instructor);

                if ($outstanding <= 0) {
                    return;
                }

                $idempotencyKey = "{$batch->batch_key}:instructor:{$instructor->id}";

                Payout::query()->firstOrCreate(
                    ['idempotency_key' => $idempotencyKey],
                    [
                        'batch_id' => $batch->id,
                        'instructor_id' => $instructor->id,
                        'amount' => $outstanding,
                        'status' => PayoutStatusEnum::Pending,
                    ],
                );
            });

            return $batch->fresh(['payouts']);
        });
    }

    public function dispatchBatch(PayoutBatch $batch): void
    {
        $batch->payouts()
            ->where('status', PayoutStatusEnum::Pending)
            ->each(fn (Payout $payout) => ProcessPayoutJob::dispatch($payout));
    }

    public function processPayout(Payout $payout): void
    {
        DB::transaction(function () use ($payout): void {
            $payout = Payout::query()
                ->whereKey($payout->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($payout->status === PayoutStatusEnum::Paid) {
                return;
            }

            if ($payout->status === PayoutStatusEnum::PendingConfirmation) {
                $this->confirmPayout($payout);

                return;
            }

            if ($payout->status === PayoutStatusEnum::Failed) {
                return;
            }

            $payout->update(['status' => PayoutStatusEnum::Processing]);

            try {
                $result = $this->paymentProvider->transfer(
                    $payout->idempotency_key,
                    (int) $payout->amount,
                );
            } catch (PaymentTimeoutException $exception) {
                $payout->update([
                    'status' => PayoutStatusEnum::PendingConfirmation,
                    'provider_reference' => $exception->providerReference,
                ]);

                return;
            }

            if (! $result->succeeded) {
                $payout->update(['status' => PayoutStatusEnum::Failed]);

                return;
            }

            $this->markPaid($payout, $result->providerReference);
        });
    }

    public function confirmPayout(Payout $payout): void
    {
        if ($payout->status === PayoutStatusEnum::Paid || $payout->provider_reference === null) {
            return;
        }

        $status = $this->paymentProvider->checkStatus($payout->provider_reference);

        match ($status) {
            ProviderStatusEnum::Success => $this->markPaid($payout, $payout->provider_reference),
            ProviderStatusEnum::Failed => $payout->update(['status' => PayoutStatusEnum::Failed]),
            ProviderStatusEnum::Unknown => null,
        };
    }

    private function markPaid(Payout $payout, string $providerReference): void
    {
        if ($payout->status === PayoutStatusEnum::Paid) {
            return;
        }

        $payout->update([
            'status' => PayoutStatusEnum::Paid,
            'provider_reference' => $providerReference,
        ]);

        $this->ledgerService->recordPayout($payout);
    }

    private function eligibleInstructors(): Collection
    {
        return Instructor::query()
            ->whereHas('ledgerEntries')
            ->get()
            ->filter(fn (Instructor $instructor) => $this->balanceService->getOutstandingBalance($instructor) > 0);
    }
}
