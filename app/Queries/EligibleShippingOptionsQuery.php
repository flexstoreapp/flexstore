<?php

declare(strict_types=1);

namespace App\Queries;

use App\Address\RegionAddressMatcher;
use App\DTOs\AddressLocation;
use App\DTOs\OrderItemsSummary;
use App\Models\Region;
use App\Models\ShippingRate;
use App\Utilities\WeightConverter;
use Brick\Math\BigDecimal;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

final readonly class EligibleShippingOptionsQuery
{
    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function execute(OrderItemsSummary $data, ?AddressLocation $address = null): Collection
    {
        return $this->getShippingRates($data->subtotal, $data->totalWeightInGrams, $address, $data->productIds, $data->categoryIds, $data->brandIds) // @phpstan-ignore return.type
            ->map(fn (ShippingRate $shippingRate): array => [
                'id' => $shippingRate->id,
                'name' => $shippingRate->getTranslations('name'),
                'carrier_name' => $shippingRate->carrier->getTranslations('name'),
                'type' => $shippingRate->type,
                'rate' => $shippingRate->rate,
                'delivery_time' => $shippingRate->getTranslations('delivery_time'),
            ])
            ->values();
    }

    /**
     * @param  array<int>  $productIds
     * @param  array<int>  $categoryIds
     * @param  array<int>  $brandIds
     * @return Collection<int, ShippingRate>
     */
    private function getShippingRates(
        string $subtotal,
        BigDecimal $totalWeightInGrams,
        ?AddressLocation $address,
        array $productIds,
        array $categoryIds,
        array $brandIds,
    ): Collection {
        return ShippingRate::query()
            ->where('is_active', true)
            ->with('region', 'carrier')
            ->whereHas('carrier', fn (Builder $query): Builder => $query->where('is_active', true))
            ->where(function (Builder $query) use ($subtotal): void {
                $query->whereNull('min_order_value')
                    ->orWhere('min_order_value', '<=', $subtotal);
            })
            ->where(function (Builder $query) use ($subtotal): void {
                $query->whereNull('max_order_value')
                    ->orWhere('max_order_value', '>=', $subtotal);
            })
            ->get()
            ->filter(fn (ShippingRate $rate): bool => $this->passesWeightConstraints($rate, $totalWeightInGrams))
            ->filter(fn (ShippingRate $rate): bool => $this->passesRegionConstraints($rate->region, $address))
            ->filter(fn (ShippingRate $rate): bool => $this->passesExclusionConstraints($rate, $productIds, $categoryIds, $brandIds))
            ->values();
    }

    private function passesWeightConstraints(ShippingRate $shippingRate, BigDecimal $weightInGrams): bool
    {
        if ($shippingRate->min_weight !== null && $shippingRate->min_weight_unit !== null) {
            $minWeightInGrams = BigDecimal::of(
                WeightConverter::toGrams($shippingRate->min_weight, $shippingRate->min_weight_unit)
            );

            if ($weightInGrams->isLessThan($minWeightInGrams)) {
                return false;
            }
        }

        if ($shippingRate->max_weight !== null && $shippingRate->max_weight_unit !== null) {
            $maxWeightInGrams = BigDecimal::of(
                WeightConverter::toGrams($shippingRate->max_weight, $shippingRate->max_weight_unit)
            );

            if ($weightInGrams->isGreaterThan($maxWeightInGrams)) {
                return false;
            }
        }

        return true;
    }

    private function passesRegionConstraints(Region $region, ?AddressLocation $address): bool
    {
        $hasConstraints = ! empty($region->countries) || ! empty($region->states) || ! empty($region->postal_codes);

        if (! $hasConstraints) {
            return true;
        }

        if (! $address instanceof AddressLocation) {
            return false;
        }

        return RegionAddressMatcher::matches($region, $address);
    }

    /**
     * @param  array<int>  $productIds
     * @param  array<int>  $categoryIds
     * @param  array<int>  $brandIds
     */
    private function passesExclusionConstraints(ShippingRate $shippingRate, array $productIds, array $categoryIds, array $brandIds): bool
    {
        if (! empty($shippingRate->excluded_products) && array_intersect($productIds, $shippingRate->excluded_products) !== []) {
            return false;
        }

        if (! empty($shippingRate->excluded_categories) && array_intersect($categoryIds, $shippingRate->excluded_categories) !== []) {
            return false;
        }

        if (! empty($shippingRate->excluded_brands) && array_intersect($brandIds, $shippingRate->excluded_brands) !== []) {
            return false;
        }

        return true;
    }
}
