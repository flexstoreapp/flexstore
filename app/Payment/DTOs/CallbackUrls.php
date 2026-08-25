<?php

declare(strict_types=1);

namespace App\Payment\DTOs;

final readonly class CallbackUrls
{
    public function __construct(
        public ?string $webhookUrl = null,
    ) {
    }
}
