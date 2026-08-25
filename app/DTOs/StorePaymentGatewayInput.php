<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Enums\PaymentGatewayDriver;
use App\Enums\WeightUnit;

final readonly class StorePaymentGatewayInput
{
    /**
     * @param  array<string, string>|string  $name
     * @param  array<string, mixed>|null  $credentials
     * @param  list<int>  $excludedProducts
     * @param  list<int>  $excludedCategories
     * @param  list<int>  $excludedBrands
     * @param  list<int>  $allowedRegions
     * @param  list<string>  $supportedCurrencies
     */
    public function __construct(
        public array|string $name,
        public PaymentGatewayDriver $driver,
        public ?array $credentials,
        public ?string $minOrderValue,
        public ?string $maxOrderValue,
        public ?string $minWeight,
        public ?WeightUnit $minWeightUnit,
        public ?string $maxWeight,
        public ?WeightUnit $maxWeightUnit,
        public array $excludedProducts,
        public array $excludedCategories,
        public array $excludedBrands,
        public array $allowedRegions,
        public array $supportedCurrencies,
        public bool $syncExternalRefunds,
        public bool $isActive,
    ) {
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $driver = $data['driver'] instanceof PaymentGatewayDriver
            ? $data['driver']
            : PaymentGatewayDriver::from((string) $data['driver']);

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
            name: $data['name'],
            driver: $driver,
            credentials: isset($data['credentials']) && is_array($data['credentials']) ? $data['credentials'] : null,
            minOrderValue: isset($data['min_order_value']) ? (string) $data['min_order_value'] : null,
            maxOrderValue: isset($data['max_order_value']) ? (string) $data['max_order_value'] : null,
            minWeight: isset($data['min_weight']) ? (string) $data['min_weight'] : null,
            minWeightUnit: $minWeightUnit,
            maxWeight: isset($data['max_weight']) ? (string) $data['max_weight'] : null,
            maxWeightUnit: $maxWeightUnit,
            excludedProducts: isset($data['excluded_products']) && is_array($data['excluded_products']) ? array_values(array_map(intval(...), $data['excluded_products'])) : [],
            excludedCategories: isset($data['excluded_categories']) && is_array($data['excluded_categories']) ? array_values(array_map(intval(...), $data['excluded_categories'])) : [],
            excludedBrands: isset($data['excluded_brands']) && is_array($data['excluded_brands']) ? array_values(array_map(intval(...), $data['excluded_brands'])) : [],
            allowedRegions: isset($data['allowed_regions']) && is_array($data['allowed_regions']) ? array_values(array_map(intval(...), $data['allowed_regions'])) : [],
            supportedCurrencies: isset($data['supported_currencies']) && is_array($data['supported_currencies']) ? array_values(array_map(strval(...), $data['supported_currencies'])) : [],
            syncExternalRefunds: (bool) ($data['sync_external_refunds'] ?? false),
            isActive: (bool) $data['is_active'],
        );
    }
}
