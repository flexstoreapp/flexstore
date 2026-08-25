<?php

declare(strict_types=1);

namespace App\Payment\DTOs;

final readonly class RedirectUrls
{
    public function __construct(
        public string $returnUrl,
        public ?string $cancelUrl = null,
        public ?string $failureUrl = null,
    ) {
    }
}
