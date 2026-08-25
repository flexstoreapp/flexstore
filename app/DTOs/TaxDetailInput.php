<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Enums\OrderTaxDetailItemType;

final readonly class TaxDetailInput
{
    /**
     * @param  array<string, string>  $taxName
     */
    public function __construct(
        public ?int $orderItemId,
        public ?int $taxRateId,
        public OrderTaxDetailItemType $itemType,
        public array $taxName,
        public string $taxRate,
        public string $taxableAmount,
        public string $taxAmount,
        public bool $isCompound,
        public ?string $proportion,
    ) {
    }

    public function withScaledAmounts(string $taxableAmount, string $taxAmount): self
    {
        return new self(
            orderItemId: $this->orderItemId,
            taxRateId: $this->taxRateId,
            itemType: $this->itemType,
            taxName: $this->taxName,
            taxRate: $this->taxRate,
            taxableAmount: $taxableAmount,
            taxAmount: $taxAmount,
            isCompound: $this->isCompound,
            proportion: $this->proportion,
        );
    }
}
