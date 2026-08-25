<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Enums\TaxCategory;

final readonly class StoreTaxRateInput
{
    /**
     * @param  array<string, string>|string  $name
     */
    public function __construct(
        public array|string $name,
        public ?TaxCategory $taxCategory,
        public int $regionId,
        public string $rate,
        public ?int $priority,
        public ?string $minOrderValue,
        public ?string $maxOrderValue,
        public bool $isCompound,
        public bool $isActive,
    ) {
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $taxCategory = null;
        if (array_key_exists('tax_category', $data) && $data['tax_category'] !== null) {
            $taxCategory = $data['tax_category'] instanceof TaxCategory
                ? $data['tax_category']
                : TaxCategory::from((string) $data['tax_category']);
        }

        return new self(
            name: $data['name'],
            taxCategory: $taxCategory,
            regionId: (int) $data['region_id'],
            rate: (string) $data['rate'],
            priority: isset($data['priority']) ? (int) $data['priority'] : null,
            minOrderValue: isset($data['min_order_value']) ? (string) $data['min_order_value'] : null,
            maxOrderValue: isset($data['max_order_value']) ? (string) $data['max_order_value'] : null,
            isCompound: (bool) $data['is_compound'],
            isActive: (bool) $data['is_active'],
        );
    }
}
