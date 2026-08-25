<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Enums\TaxCategory;

final readonly class UpdateTaxRateInput
{
    /**
     * @param  array<string, string>|string|null  $name
     * @param  array<string, true>  $provided
     */
    public function __construct(
        public array|string|null $name,
        public ?TaxCategory $taxCategory,
        public ?int $regionId,
        public ?string $rate,
        public ?int $priority,
        public ?string $minOrderValue,
        public ?string $maxOrderValue,
        public ?bool $isCompound,
        public ?bool $isActive,
        public array $provided,
    ) {
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $provided = [];
        foreach (['name', 'tax_category', 'region_id', 'rate', 'priority', 'min_order_value', 'max_order_value', 'is_compound', 'is_active'] as $key) {
            if (array_key_exists($key, $data)) {
                $provided[$key] = true;
            }
        }

        $taxCategory = null;
        if (array_key_exists('tax_category', $data) && $data['tax_category'] !== null) {
            $taxCategory = $data['tax_category'] instanceof TaxCategory
                ? $data['tax_category']
                : TaxCategory::from((string) $data['tax_category']);
        }

        return new self(
            name: $data['name'] ?? null,
            taxCategory: $taxCategory,
            regionId: isset($data['region_id']) ? (int) $data['region_id'] : null,
            rate: isset($data['rate']) ? (string) $data['rate'] : null,
            priority: isset($data['priority']) ? (int) $data['priority'] : null,
            minOrderValue: isset($data['min_order_value']) ? (string) $data['min_order_value'] : null,
            maxOrderValue: isset($data['max_order_value']) ? (string) $data['max_order_value'] : null,
            isCompound: isset($data['is_compound']) ? (bool) $data['is_compound'] : null,
            isActive: isset($data['is_active']) ? (bool) $data['is_active'] : null,
            provided: $provided,
        );
    }

    public function has(string $field): bool
    {
        return isset($this->provided[$field]);
    }
}
