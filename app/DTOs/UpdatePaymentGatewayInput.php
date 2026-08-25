<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Enums\PaymentGatewayDriver;
use App\Enums\WeightUnit;

final readonly class UpdatePaymentGatewayInput
{
    /**
     * @param  array<string, string>|string|null  $name
     * @param  array<string, mixed>|null  $credentials
     * @param  list<int>|null  $excludedProducts
     * @param  list<int>|null  $excludedCategories
     * @param  list<int>|null  $excludedBrands
     * @param  list<int>|null  $allowedRegions
     * @param  list<string>|null  $supportedCurrencies
     * @param  array<string, true>  $provided
     */
    public function __construct(
        public array|string|null $name,
        public ?PaymentGatewayDriver $driver,
        public ?array $credentials,
        public ?string $minOrderValue,
        public ?string $maxOrderValue,
        public ?string $minWeight,
        public ?WeightUnit $minWeightUnit,
        public ?string $maxWeight,
        public ?WeightUnit $maxWeightUnit,
        public ?array $excludedProducts,
        public ?array $excludedCategories,
        public ?array $excludedBrands,
        public ?array $allowedRegions,
        public ?array $supportedCurrencies,
        public ?bool $syncExternalRefunds,
        public ?bool $isActive,
        public array $provided,
    ) {
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $keys = ['name', 'driver', 'credentials', 'min_order_value', 'max_order_value', 'min_weight', 'min_weight_unit', 'max_weight', 'max_weight_unit', 'excluded_products', 'excluded_categories', 'excluded_brands', 'allowed_regions', 'supported_currencies', 'sync_external_refunds', 'is_active'];
        $provided = [];
        foreach ($keys as $key) {
            if (array_key_exists($key, $data)) {
                $provided[$key] = true;
            }
        }

        $driver = null;
        if (array_key_exists('driver', $data) && $data['driver'] !== null) {
            $driver = $data['driver'] instanceof PaymentGatewayDriver
                ? $data['driver']
                : PaymentGatewayDriver::from((string) $data['driver']);
        }

        $minWeightUnit = null;
        if (array_key_exists('min_weight_unit', $data) && $data['min_weight_unit'] !== null) {
            $minWeightUnit = $data['min_weight_unit'] instanceof WeightUnit
                ? $data['min_weight_unit']
                : WeightUnit::from((string) $data['min_weight_unit']);
        }

        $maxWeightUnit = null;
        if (array_key_exists('max_weight_unit', $data) && $data['max_weight_unit'] !== null) {
            $maxWeightUnit = $data['max_weight_unit'] instanceof WeightUnit
                ? $data['max_weight_unit']
                : WeightUnit::from((string) $data['max_weight_unit']);
        }

        return new self(
            name: $data['name'] ?? null,
            driver: $driver,
            credentials: isset($data['credentials']) && is_array($data['credentials']) ? $data['credentials'] : null,
            minOrderValue: isset($data['min_order_value']) ? (string) $data['min_order_value'] : null,
            maxOrderValue: isset($data['max_order_value']) ? (string) $data['max_order_value'] : null,
            minWeight: isset($data['min_weight']) ? (string) $data['min_weight'] : null,
            minWeightUnit: $minWeightUnit,
            maxWeight: isset($data['max_weight']) ? (string) $data['max_weight'] : null,
            maxWeightUnit: $maxWeightUnit,
            excludedProducts: isset($data['excluded_products']) && is_array($data['excluded_products']) ? array_values(array_map(intval(...), $data['excluded_products'])) : null,
            excludedCategories: isset($data['excluded_categories']) && is_array($data['excluded_categories']) ? array_values(array_map(intval(...), $data['excluded_categories'])) : null,
            excludedBrands: isset($data['excluded_brands']) && is_array($data['excluded_brands']) ? array_values(array_map(intval(...), $data['excluded_brands'])) : null,
            allowedRegions: isset($data['allowed_regions']) && is_array($data['allowed_regions']) ? array_values(array_map(intval(...), $data['allowed_regions'])) : null,
            supportedCurrencies: isset($data['supported_currencies']) && is_array($data['supported_currencies']) ? array_values(array_map(strval(...), $data['supported_currencies'])) : null,
            syncExternalRefunds: isset($data['sync_external_refunds']) ? (bool) $data['sync_external_refunds'] : null,
            isActive: isset($data['is_active']) ? (bool) $data['is_active'] : null,
            provided: $provided,
        );
    }

    public function has(string $field): bool
    {
        return isset($this->provided[$field]);
    }
}
