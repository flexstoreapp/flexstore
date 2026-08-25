<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Enums\ShippingRateType;
use App\Enums\WeightUnit;

final readonly class UpdateShippingRateInput
{
    /**
     * @param  array<string, string>|string|null  $name
     * @param  array<string, string>|string|null  $deliveryTime
     * @param  list<int>|null  $excludedProducts
     * @param  list<int>|null  $excludedCategories
     * @param  list<int>|null  $excludedBrands
     * @param  array<string, true>  $provided
     */
    public function __construct(
        public ?int $regionId,
        public ?int $shippingCarrierId,
        public array|string|null $name,
        public ?ShippingRateType $type,
        public ?string $rate,
        public array|string|null $deliveryTime,
        public ?string $minOrderValue,
        public ?string $maxOrderValue,
        public ?string $minWeight,
        public ?WeightUnit $minWeightUnit,
        public ?string $maxWeight,
        public ?WeightUnit $maxWeightUnit,
        public ?array $excludedProducts,
        public ?array $excludedCategories,
        public ?array $excludedBrands,
        public ?bool $isActive,
        public array $provided,
    ) {
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $keys = ['region_id', 'shipping_carrier_id', 'name', 'type', 'rate', 'delivery_time', 'min_order_value', 'max_order_value', 'min_weight', 'min_weight_unit', 'max_weight', 'max_weight_unit', 'excluded_products', 'excluded_categories', 'excluded_brands', 'is_active'];
        $provided = [];
        foreach ($keys as $key) {
            if (array_key_exists($key, $data)) {
                $provided[$key] = true;
            }
        }

        $type = null;
        if (array_key_exists('type', $data) && $data['type'] !== null) {
            $type = $data['type'] instanceof ShippingRateType
                ? $data['type']
                : ShippingRateType::from((string) $data['type']);
        }

        return new self(
            regionId: isset($data['region_id']) ? (int) $data['region_id'] : null,
            shippingCarrierId: isset($data['shipping_carrier_id']) ? (int) $data['shipping_carrier_id'] : null,
            name: $data['name'] ?? null,
            type: $type,
            rate: isset($data['rate']) ? (string) $data['rate'] : null,
            deliveryTime: $data['delivery_time'] ?? null,
            minOrderValue: isset($data['min_order_value']) ? (string) $data['min_order_value'] : null,
            maxOrderValue: isset($data['max_order_value']) ? (string) $data['max_order_value'] : null,
            minWeight: isset($data['min_weight']) ? (string) $data['min_weight'] : null,
            minWeightUnit: self::resolveWeightUnit($data['min_weight_unit'] ?? null),
            maxWeight: isset($data['max_weight']) ? (string) $data['max_weight'] : null,
            maxWeightUnit: self::resolveWeightUnit($data['max_weight_unit'] ?? null),
            excludedProducts: isset($data['excluded_products']) && is_array($data['excluded_products']) ? array_values(array_map(intval(...), $data['excluded_products'])) : null,
            excludedCategories: isset($data['excluded_categories']) && is_array($data['excluded_categories']) ? array_values(array_map(intval(...), $data['excluded_categories'])) : null,
            excludedBrands: isset($data['excluded_brands']) && is_array($data['excluded_brands']) ? array_values(array_map(intval(...), $data['excluded_brands'])) : null,
            isActive: isset($data['is_active']) ? (bool) $data['is_active'] : null,
            provided: $provided,
        );
    }

    public function has(string $field): bool
    {
        return isset($this->provided[$field]);
    }

    private static function resolveWeightUnit(mixed $value): ?WeightUnit
    {
        if ($value === null) {
            return null;
        }

        return $value instanceof WeightUnit ? $value : WeightUnit::from((string) $value);
    }
}
