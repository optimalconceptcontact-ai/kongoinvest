<?php

namespace App\Services\Payments;

final class RefundResult
{
    public function __construct(
        public readonly bool $successful,
        public readonly ?string $providerRefundId = null,
        public readonly ?int $amountMinor = null,
        public readonly ?string $failureReason = null,
    ) {}
}
