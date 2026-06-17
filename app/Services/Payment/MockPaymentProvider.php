<?php

namespace App\Services\Payment;

use App\Contracts\PaymentProviderInterface;
use App\DTOs\TransferResult;
use App\Enums\ProviderStatusEnum;
use App\Enums\ProviderTransferStatusEnum;
use App\Exceptions\PaymentTimeoutException;
use App\Models\ProviderTransfer;
use Illuminate\Support\Str;

class MockPaymentProvider implements PaymentProviderInterface
{
    public const OUTCOME_SUCCESS = 'success';

    public const OUTCOME_FAILED = 'failed';

    public const OUTCOME_TIMEOUT_SUCCESS = 'timeout_success';

    private ?string $forcedOutcome = null;

    public function forceOutcome(?string $outcome): self
    {
        $this->forcedOutcome = $outcome;

        return $this;
    }

    public function transfer(string $idempotencyKey, int $amountCents): TransferResult
    {
        $existing = ProviderTransfer::query()
            ->where('idempotency_key', $idempotencyKey)
            ->first();

        if ($existing !== null) {
            return $this->resultFromTransfer($existing);
        }

        $outcome = $this->forcedOutcome ?? $this->randomOutcome();
        $reference = 'mock_'.Str::uuid()->toString();

        return match ($outcome) {
            self::OUTCOME_SUCCESS => $this->completeTransfer($idempotencyKey, $amountCents, $reference),
            self::OUTCOME_FAILED => TransferResult::failed(),
            self::OUTCOME_TIMEOUT_SUCCESS => $this->timeoutAfterSuccess($idempotencyKey, $amountCents, $reference),
            default => TransferResult::failed(),
        };
    }

    public function checkStatus(string $providerReference): ProviderStatusEnum
    {
        $transfer = ProviderTransfer::query()
            ->where('provider_reference', $providerReference)
            ->first();

        if ($transfer === null) {
            return ProviderStatusEnum::Unknown;
        }

        return match ($transfer->status) {
            ProviderTransferStatusEnum::Completed => ProviderStatusEnum::Success,
            ProviderTransferStatusEnum::Failed => ProviderStatusEnum::Failed,
        };
    }

    private function completeTransfer(string $idempotencyKey, int $amountCents, string $reference): TransferResult
    {
        $transfer = ProviderTransfer::query()->create([
            'provider_reference' => $reference,
            'idempotency_key' => $idempotencyKey,
            'amount' => $amountCents,
            'status' => ProviderTransferStatusEnum::Completed,
        ]);

        return $this->resultFromTransfer($transfer);
    }

    private function timeoutAfterSuccess(string $idempotencyKey, int $amountCents, string $reference): TransferResult
    {
        ProviderTransfer::query()->create([
            'provider_reference' => $reference,
            'idempotency_key' => $idempotencyKey,
            'amount' => $amountCents,
            'status' => ProviderTransferStatusEnum::Completed,
        ]);

        throw new PaymentTimeoutException($reference);
    }

    private function resultFromTransfer(ProviderTransfer $transfer): TransferResult
    {
        return match ($transfer->status) {
            ProviderTransferStatusEnum::Completed => TransferResult::success($transfer->provider_reference),
            ProviderTransferStatusEnum::Failed => TransferResult::failed(),
        };
    }

    private function randomOutcome(): string
    {
        $roll = random_int(1, 100);

        if ($roll <= 60) {
            return self::OUTCOME_SUCCESS;
        }

        if ($roll <= 85) {
            return self::OUTCOME_FAILED;
        }

        return self::OUTCOME_TIMEOUT_SUCCESS;
    }
}
