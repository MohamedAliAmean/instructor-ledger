<?php

namespace App\DTOs;

use App\Enums\ProviderStatusEnum;

readonly class TransferResult
{
    public function __construct(
        public bool $succeeded,
        public ?string $providerReference = null,
        public ProviderStatusEnum $status = ProviderStatusEnum::Unknown,
    ) {}

    public static function success(string $providerReference): self
    {
        return new self(
            succeeded: true,
            providerReference: $providerReference,
            status: ProviderStatusEnum::Success,
        );
    }

    public static function failed(): self
    {
        return new self(
            succeeded: false,
            status: ProviderStatusEnum::Failed,
        );
    }
}
