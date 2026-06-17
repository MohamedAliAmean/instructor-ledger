<?php

namespace App\Contracts;

use App\DTOs\TransferResult;
use App\Enums\ProviderStatusEnum;
use App\Exceptions\PaymentTimeoutException;

interface PaymentProviderInterface
{
    /**
     * @throws PaymentTimeoutException When money may have moved but response was lost.
     */
    public function transfer(string $idempotencyKey, int $amountCents): TransferResult;

    public function checkStatus(string $providerReference): ProviderStatusEnum;
}
