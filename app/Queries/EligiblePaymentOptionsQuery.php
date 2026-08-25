<?php

declare(strict_types=1);

namespace App\Queries;

use App\Address\RegionAddressMatcher;
use App\DTOs\AddressLocation;
use App\DTOs\OrderItemsSummary;
use App\Enums\PaymentGatewayDriver;
use App\Models\PaymentGateway;
use App\Models\Region;
use App\Utilities\PaymentOptionPayload;
use App\Utilities\WeightConverter;
use Brick\Math\BigDecimal;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

final readonly class EligiblePaymentOptionsQuery
{
    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function execute(OrderItemsSummary $data, ?AddressLocation $address = null, ?string $currencyCode = null): Collection
    {
        return $this->getPaymentGateways($data->subtotal, $data->totalWeightInGrams, $address, $data->productIds, $data->categoryIds, $data->brandIds, $currencyCode, $data->requiresShipping())
            ->map(fn (PaymentGateway $gateway): array => PaymentOptionPayload::fromGateway($gateway))
            ->values();
    }

    /**
     * @param  array<int>  $productIds
     * @param  array<int>  $categoryIds
     * @param  array<int>  $brandIds
     * @return Collection<int, PaymentGateway>
     */
    private function getPaymentGateways(
        string $subtotal,
        BigDecimal $totalWeightInGrams,
        ?AddressLocation $address,
        array $productIds,
        array $categoryIds,
        array $brandIds,
        ?string $currencyCode = null,
        bool $requiresShipping = true,
    ): Collection {
        $gateways = PaymentGateway::query()
            ->where('is_active', true)
            ->where(function (Builder $query) use ($subtotal): void {
                $query->whereNull('min_order_value')
                    ->orWhere('min_order_value', '<=', $subtotal);
            })
            ->where(function (Builder $query) use ($subtotal): void {
                $query->whereNull('max_order_value')
                    ->orWhere('max_order_value', '>=', $subtotal);
            })
            ->get();

        $allRegionIds = $gateways->flatMap(fn (PaymentGateway $gateway): array => $gateway->allowed_regions ?? [])->unique()->values()->all();
        $regions = $allRegionIds === [] ? collect() : Region::query()->findMany($allRegionIds)->keyBy('id');

        return $gateways
            ->filter(fn (PaymentGateway $gateway): bool => $this->passesWeightConstraints($gateway, $totalWeightInGrams))
            ->filter(fn (PaymentGateway $gateway): bool => $this->passesRegionConstraints($gateway, $address, $regions))
            ->filter(fn (PaymentGateway $gateway): bool => $this->passesExclusionConstraints($gateway, $productIds, $categoryIds, $brandIds))
            ->filter(fn (PaymentGateway $gateway): bool => $this->passesCurrencyConstraints($gateway, $currencyCode))
            ->filter(fn (PaymentGateway $gateway): bool => $this->passesFulfillmentConstraints($gateway, $requiresShipping))
            ->values();
    }

    private function passesFulfillmentConstraints(PaymentGateway $gateway, bool $requiresShipping): bool
    {
        if ($requiresShipping) {
            return true;
        }

        return $gateway->driver !== PaymentGatewayDriver::Cod;
    }

    private function passesCurrencyConstraints(PaymentGateway $gateway, ?string $currencyCode): bool
    {
        if ($currencyCode === null || empty($gateway->supported_currencies)) {
            return true;
        }

        return in_array($currencyCode, $gateway->supported_currencies, true);
    }

    private function passesWeightConstraints(PaymentGateway $gateway, BigDecimal $weightInGrams): bool
    {
        if ($gateway->min_weight !== null && $gateway->min_weight_unit !== null) {
            $minWeightInGrams = BigDecimal::of(
                WeightConverter::toGrams($gateway->min_weight, $gateway->min_weight_unit)
            );

            if ($weightInGrams->isLessThan($minWeightInGrams)) {
                return false;
            }
        }

        if ($gateway->max_weight !== null && $gateway->max_weight_unit !== null) {
            $maxWeightInGrams = BigDecimal::of(
                WeightConverter::toGrams($gateway->max_weight, $gateway->max_weight_unit)
            );

            if ($weightInGrams->isGreaterThan($maxWeightInGrams)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  Collection<int, Region>  $allRegions
     */
    private function passesRegionConstraints(PaymentGateway $gateway, ?AddressLocation $address, Collection $allRegions): bool
    {
        if (empty($gateway->allowed_regions)) {
            return true;
        }

        if (! $address instanceof AddressLocation) {
            return false;
        }

        foreach ($gateway->allowed_regions as $regionId) {
            $region = $allRegions[$regionId] ?? null;

            if ($region !== null && RegionAddressMatcher::matches($region, $address)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<int>  $productIds
     * @param  array<int>  $categoryIds
     * @param  array<int>  $brandIds
     */
    private function passesExclusionConstraints(PaymentGateway $gateway, array $productIds, array $categoryIds, array $brandIds): bool
    {
        if (! empty($gateway->excluded_products) && array_intersect($productIds, $gateway->excluded_products) !== []) {
            return false;
        }

        if (! empty($gateway->excluded_categories) && array_intersect($categoryIds, $gateway->excluded_categories) !== []) {
            return false;
        }

        if (! empty($gateway->excluded_brands) && array_intersect($brandIds, $gateway->excluded_brands) !== []) {
            return false;
        }

        return true;
    }
}
