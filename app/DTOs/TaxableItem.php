<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Enums\TaxCategory;

final readonly class TaxableItem
{
    public function __construct(
        public ?int $id,
        public string $totalPrice,
        public bool $isTaxExempt,
        public ?TaxCategory $taxCategory,
    ) {
    }
}
