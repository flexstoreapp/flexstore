<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Enums\StockMovementReason;

final readonly class StockAdjustmentInput
{
    public function __construct(
        public int $quantity,
        public StockMovementReason $reason,
        public ?string $notes,
        public ?string $referenceType,
        public string|int|null $referenceId,
    ) {
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $reason = $data['reason'] instanceof StockMovementReason
            ? $data['reason']
            : StockMovementReason::from((string) $data['reason']);

        return new self(
            quantity: (int) $data['quantity'],
            reason: $reason,
            notes: isset($data['notes']) ? (string) $data['notes'] : null,
            referenceType: isset($data['reference_type']) ? (string) $data['reference_type'] : null,
            referenceId: $data['reference_id'] ?? null,
        );
    }
}
