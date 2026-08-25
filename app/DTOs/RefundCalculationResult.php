<?php

declare(strict_types=1);

namespace App\DTOs;

final readonly class RefundCalculationResult
{
    public function __construct(
        public string $productTotal,
        public string $taxTotal,
        public string $discountTotal,
    ) {
    }
}
