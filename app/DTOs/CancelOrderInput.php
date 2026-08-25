<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Enums\CancellationReason;

final readonly class CancelOrderInput
{
    public function __construct(
        public CancellationReason $reason,
        public ?string $reasonNote,
        public bool $refund,
        public bool $restock,
        public bool $notifyCustomer,
    ) {
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            reason: CancellationReason::from((string) $data['reason']),
            reasonNote: isset($data['reason_note']) ? (string) $data['reason_note'] : null,
            refund: (bool) ($data['refund'] ?? false),
            restock: (bool) ($data['restock'] ?? false),
            notifyCustomer: (bool) ($data['notify_customer'] ?? true),
        );
    }
}
