<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Enums\ShippingRateType;
use App\Enums\WeightUnit;

final readonly class StoreShippingRateInput
{
    /**
     * @param  array<string, string>|string  $name
     * @param  array<string, string>|string|null  $deliveryTime
     * @param  list<int>  $excludedProducts
     * @param  list<int>  $excludedCategories
     * @param  list<int>  $excludedBrands
     */
    public function __construct(
        public int $regionId,
        public int $shippingCarrierId,
        public array|string $name,
        public ShippingRateType $type,
        public ?string $rate,
        public array|string|null $deliveryTime,
        public ?string $minOrderValue,
        public ?string $maxOrderValue,
        public ?string $minWeight,
        public ?WeightUnit $minWeightUnit,
        public ?string $maxWeight,
        public ?WeightUnit $maxWeightUnit,
        public array $excludedProducts,
        public array $excludedCategories,
        public array $excludedBrands,
        public bool $isActive,
    ) {
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $type = $data['type'] instanceof ShippingRateType
            ? $data['type']
            : ShippingRateType::from((string) $data['type']);

        return new self(
            regionId: (int) $data['region_id'],
            shippingCarrierId: (int) $data['shipping_carrier_id'],
            name: $data['name'],
            type: $type,
            rate: isset($data['rate']) ? (string) $data['rate'] : null,
            deliveryTime: $data['delivery_time'] ?? null,
            minOrderValue: isset($data['min_order_value']) ? (string) $data['min_order_value'] : null,
            maxOrderValue: isset($data['max_order_value']) ? (string) $data['max_order_value'] : null,
            minWeight: isset($data['min_weight']) ? (string) $data['min_weight'] : null,
            minWeightUnit: self::resolveWeightUnit($data['min_weight_unit'] ?? null),
            maxWeight: isset($data['max_weight']) ? (string) $data['max_weight'] : null,
            maxWeightUnit: self::resolveWeightUnit($data['max_weight_unit'] ?? null),
            excludedProducts: isset($data['excluded_products']) && is_array($data['excluded_products']) ? array_values(array_map(intval(...), $data['excluded_products'])) : [],
            excludedCategories: isset($data['excluded_categories']) && is_array($data['excluded_categories']) ? array_values(array_map(intval(...), $data['excluded_categories'])) : [],
            excludedBrands: isset($data['excluded_brands']) && is_array($data['excluded_brands']) ? array_values(array_map(intval(...), $data['excluded_brands'])) : [],
            isActive: (bool) $data['is_active'],
        );
    }

    private static function resolveWeightUnit(mixed $value): ?WeightUnit
    {
        if ($value === null) {
            return null;
        }

        return $value instanceof WeightUnit ? $value : WeightUnit::from((string) $value);
    }
}
