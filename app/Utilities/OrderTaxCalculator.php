<?php

declare(strict_types=1);

namespace App\Utilities;

use App\Address\RegionAddressMatcher;
use App\DTOs\AddressLocation;
use App\DTOs\TaxableItem;
use App\DTOs\TaxCalculationInput;
use App\DTOs\TaxCalculationResult;
use App\DTOs\TaxDetailInput;
use App\Enums\OrderTaxDetailItemType;
use App\Enums\TaxBasedOn;
use App\Models\Region;
use App\Models\TaxRate;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

final readonly class OrderTaxCalculator
{
    public function calculate(TaxCalculationInput $input): TaxCalculationResult
    {
        if ($input->pricesIncludeTax) {
            return new TaxCalculationResult(taxTotal: '0.0000', taxDetails: []);
        }

        $totalTaxAmount = BigDecimal::zero();
        $taxDetails = $this->calculateItemTaxes($input);
        $totalTaxAmount = $this->addTaxAmounts($totalTaxAmount, $taxDetails);

        if ($input->shippingIsTaxable && BigDecimal::of($input->shippingTotal)->isGreaterThan(BigDecimal::zero())) {
            $shippingTaxDetails = $this->calculateShippingTaxes($input);
            $taxDetails = [...$taxDetails, ...$shippingTaxDetails];
            $totalTaxAmount = $this->addTaxAmounts($totalTaxAmount, $shippingTaxDetails);
        }

        return new TaxCalculationResult(
            taxTotal: $this->formatCurrency($totalTaxAmount),
            taxDetails: $taxDetails,
        );
    }

    /**
     * @return Collection<int, Region>
     */
    private function getActiveRegions(): Collection
    {
        return Cache::memo('array')->remember(
            key: 'memo:tax_calculator.active_regions',
            ttl: now()->addMinute(),
            callback: fn () => Region::query()->where('is_active', true)->get(),
        );
    }

    /**
     * @return Collection<int, TaxRate>
     */
    private function getActiveTaxRates(): Collection
    {
        return Cache::memo('array')->remember(
            key: 'memo:tax_calculator.active_tax_rates',
            ttl: now()->addMinute(),
            callback: fn () => TaxRate::query()
                ->where('is_active', true)
                ->orderBy('priority')
                ->get(),
        );
    }

    /**
     * @return list<TaxDetailInput>
     */
    private function calculateItemTaxes(TaxCalculationInput $input): array
    {
        $discountRatio = $this->calculateDiscountRatio(
            BigDecimal::of($input->subtotal),
            BigDecimal::of($input->discountTotal)
        );

        $taxDetails = [];
        foreach ($input->items as $item) {
            $itemTaxDetails = $this->calculateSingleItemTaxes($item, $input, $discountRatio);
            $taxDetails = [...$taxDetails, ...$itemTaxDetails];
        }

        return $taxDetails;
    }

    /**
     * @return list<TaxDetailInput>
     */
    private function calculateSingleItemTaxes(TaxableItem $item, TaxCalculationInput $input, BigDecimal $discountRatio): array
    {
        $discountedItemAmount = $this->calculateDiscountedItemAmount($item, $discountRatio);
        $taxRates = $this->getApplicableTaxRates($item, $input);

        if ($taxRates->isEmpty()) {
            return $this->calculateDefaultTax($item, $discountedItemAmount, $input->defaultTaxRate);
        }

        return $this->calculateTaxRateBasedTaxes($item, $taxRates, $discountedItemAmount);
    }

    /**
     * @return list<TaxDetailInput>
     */
    private function calculateDefaultTax(TaxableItem $item, BigDecimal $discountedItemAmount, ?string $defaultTaxRate): array
    {

        if (! $defaultTaxRate || $item->isTaxExempt) {
            return [];
        }

        $taxAmount = BigDecimal::of($this->calculateTaxAmountFromRate($defaultTaxRate, $discountedItemAmount));

        if ($taxAmount->isZero()) {
            return [];
        }

        return [new TaxDetailInput(
            orderItemId: $item->id,
            taxRateId: null,
            itemType: OrderTaxDetailItemType::Product,
            taxName: [app()->getLocale() => 'Default Tax'],
            taxRate: $defaultTaxRate,
            taxableAmount: $this->formatCurrency($discountedItemAmount),
            taxAmount: $this->formatCurrency($taxAmount),
            isCompound: false,
            proportion: null,
        )];
    }

    /**
     * @param  Collection<int, TaxRate>  $taxRates
     * @return list<TaxDetailInput>
     */
    private function calculateTaxRateBasedTaxes(TaxableItem $item, Collection $taxRates, BigDecimal $discountedItemAmount): array
    {
        $taxDetails = [];
        $cumulativeTaxAmount = BigDecimal::zero();

        foreach ($taxRates as $taxRate) {
            $taxableAmount = $taxRate->is_compound
                ? $discountedItemAmount->plus($cumulativeTaxAmount)
                : $discountedItemAmount;

            $taxAmount = BigDecimal::of($this->calculateTaxAmount($taxRate, $taxableAmount));

            if ($taxAmount->isZero()) {
                continue;
            }

            $cumulativeTaxAmount = $cumulativeTaxAmount->plus($taxAmount);

            $taxDetails[] = new TaxDetailInput(
                orderItemId: $item->id,
                taxRateId: $taxRate->id,
                itemType: OrderTaxDetailItemType::Product,
                taxName: $taxRate->getTranslations('name'),
                taxRate: $taxRate->rate,
                taxableAmount: $this->formatCurrency($taxableAmount),
                taxAmount: $this->formatCurrency($taxAmount),
                isCompound: $taxRate->is_compound,
                proportion: null,
            );
        }

        return $taxDetails;
    }

    /**
     * @return list<TaxDetailInput>
     */
    private function calculateShippingTaxes(TaxCalculationInput $input): array
    {
        $shippingAmount = BigDecimal::of($input->shippingTotal);

        if ($shippingAmount->isZero()) {
            return [];
        }

        $itemTaxRates = $this->getProportionalTaxRatesFromItems($input);

        if ($itemTaxRates === []) {
            return $this->calculateDefaultShippingTax($shippingAmount, $input->defaultTaxRate);
        }

        return $this->calculateProportionalShippingTaxes($shippingAmount, $itemTaxRates);
    }

    /**
     * @return list<TaxDetailInput>
     */
    private function calculateDefaultShippingTax(BigDecimal $shippingAmount, ?string $defaultTaxRate): array
    {
        if (! $defaultTaxRate) {
            return [];
        }

        $taxAmount = BigDecimal::of($this->calculateTaxAmountFromRate($defaultTaxRate, $shippingAmount));

        if ($taxAmount->isZero()) {
            return [];
        }

        return [new TaxDetailInput(
            orderItemId: null,
            taxRateId: null,
            itemType: OrderTaxDetailItemType::Shipping,
            taxName: [app()->getLocale() => 'Default Tax'],
            taxRate: $defaultTaxRate,
            taxableAmount: $this->formatCurrency($shippingAmount),
            taxAmount: $this->formatCurrency($taxAmount),
            isCompound: false,
            proportion: '1.0000',
        )];
    }

    /**
     * @param  list<array{tax_rate: TaxRate, proportion: string}>  $itemTaxRates
     * @return list<TaxDetailInput>
     */
    private function calculateProportionalShippingTaxes(BigDecimal $shippingAmount, array $itemTaxRates): array
    {
        $taxDetails = [];

        foreach ($itemTaxRates as $taxRateData) {
            $taxRate = $taxRateData['tax_rate'];
            $proportion = $taxRateData['proportion'];

            $proportionalShippingAmount = $shippingAmount->multipliedBy($proportion);
            $taxAmount = BigDecimal::of($this->calculateTaxAmount($taxRate, $proportionalShippingAmount));

            if ($taxAmount->isZero()) {
                continue;
            }

            $taxDetails[] = new TaxDetailInput(
                orderItemId: null,
                taxRateId: $taxRate->id,
                itemType: OrderTaxDetailItemType::Shipping,
                taxName: $taxRate->getTranslations('name'),
                taxRate: $taxRate->rate,
                taxableAmount: $this->formatCurrency($proportionalShippingAmount),
                taxAmount: $this->formatCurrency($taxAmount),
                isCompound: $taxRate->is_compound,
                proportion: $proportion,
            );
        }

        return $taxDetails;
    }

    /**
     * @return list<array{tax_rate: TaxRate, proportion: string}>
     */
    private function getProportionalTaxRatesFromItems(TaxCalculationInput $input): array
    {
        $discountRatio = $this->calculateDiscountRatio(
            BigDecimal::of($input->subtotal),
            BigDecimal::of($input->discountTotal)
        );

        $applicableRegions = $this->getApplicableRegions($input);
        if ($applicableRegions->isEmpty()) {
            return [];
        }

        $regionIds = $applicableRegions->pluck('id')->toArray();
        $orderTotal = BigDecimal::of($input->subtotal)->minus($input->discountTotal);

        $itemTaxRates = [];
        $totalItemValue = BigDecimal::zero();

        foreach ($input->items as $item) {
            $applicableTaxRates = $this->getActiveTaxRates()
                ->whereIn('region_id', $regionIds)
                ->filter(
                    fn (TaxRate $taxRate): bool => $this->appliesTo($taxRate, $item) &&
                        $this->meetsOrderValueRequirementsWithTotal($taxRate, $orderTotal)
                );

            if ($applicableTaxRates->isEmpty()) {
                continue;
            }

            $discountedItemAmount = $this->calculateDiscountedItemAmount($item, $discountRatio);
            $totalItemValue = $totalItemValue->plus($discountedItemAmount);

            foreach ($applicableTaxRates as $taxRate) {
                $key = $taxRate->id;

                if (! isset($itemTaxRates[$key])) {
                    $itemTaxRates[$key] = [
                        'tax_rate' => $taxRate,
                        'total_value' => BigDecimal::zero(),
                    ];
                }

                $itemTaxRates[$key]['total_value'] = $itemTaxRates[$key]['total_value']->plus($discountedItemAmount);
            }
        }

        if ($itemTaxRates === [] || $totalItemValue->isZero()) {
            return [];
        }

        $proportionalTaxRates = [];
        foreach ($itemTaxRates as $taxRateData) {
            $proportion = $taxRateData['total_value']
                ->dividedBy($totalItemValue, 4, RoundingMode::HalfUp)
                ->toString();

            $proportionalTaxRates[] = [
                'tax_rate' => $taxRateData['tax_rate'],
                'proportion' => $proportion,
            ];
        }

        return $proportionalTaxRates;
    }

    /**
     * @return Collection<int, TaxRate>
     */
    private function getApplicableTaxRates(TaxableItem $item, TaxCalculationInput $input): Collection
    {
        $applicableRegions = $this->getApplicableRegions($input);

        if ($applicableRegions->isEmpty()) {
            return new Collection();
        }

        $regionIds = $applicableRegions->pluck('id')->toArray();
        $orderTotal = BigDecimal::of($input->subtotal)->minus($input->discountTotal);

        return $this->getActiveTaxRates()
            ->whereIn('region_id', $regionIds)
            ->filter(
                fn (TaxRate $taxRate): bool => $this->appliesTo($taxRate, $item) &&
                    $this->meetsOrderValueRequirementsWithTotal($taxRate, $orderTotal)
            );
    }

    private function appliesTo(TaxRate $taxRate, TaxableItem $item): bool
    {
        if ($item->isTaxExempt) {
            return false;
        }

        if (! $taxRate->tax_category) {
            return true;
        }

        return $item->taxCategory === $taxRate->tax_category;
    }

    private function meetsOrderValueRequirementsWithTotal(TaxRate $taxRate, BigDecimal $orderTotal): bool
    {
        if ($taxRate->min_order_value) {
            $minOrderValue = BigDecimal::of($taxRate->min_order_value);
            if ($orderTotal->isLessThan($minOrderValue)) {
                return false;
            }
        }

        if ($taxRate->max_order_value) {
            $maxOrderValue = BigDecimal::of($taxRate->max_order_value);
            if ($orderTotal->isGreaterThan($maxOrderValue)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return Collection<int, Region>
     */
    private function getApplicableRegions(TaxCalculationInput $input): Collection
    {
        $address = $this->getAddressLocation($input);

        if (! $address instanceof AddressLocation) {
            return new Collection();
        }

        return $this->findRegionsByAddress($address);
    }

    /**
     * @return Collection<int, Region>
     */
    private function findRegionsByAddress(AddressLocation $address): Collection
    {
        $matchingRegions = $this->getActiveRegions()
            ->filter(fn (Region $region): bool => RegionAddressMatcher::matches($region, $address))
            ->values()
            ->all();

        if (empty($matchingRegions)) {
            return new Collection();
        }

        if (count($matchingRegions) === 1) {
            return new Collection([$matchingRegions[0]]);
        }

        $mostSpecificRegion = $this->findMostSpecificRegion($matchingRegions);

        return $mostSpecificRegion instanceof Region ? new Collection([$mostSpecificRegion]) : new Collection();
    }

    /**
     * @param  array<Region>  $regions
     */
    private function findMostSpecificRegion(array $regions): ?Region
    {
        $mostSpecific = null;
        $highestSpecificity = -1;

        foreach ($regions as $region) {
            $specificity = $this->calculateRegionSpecificity($region);

            if ($specificity > $highestSpecificity) {
                $highestSpecificity = $specificity;
                $mostSpecific = $region;
            }
        }

        return $mostSpecific;
    }

    private function calculateRegionSpecificity(Region $region): int
    {
        $score = 0;

        if (! empty($region->countries)) {
            $score += 100;
        }

        if (! empty($region->states)) {
            $score += 10;
        }

        if (! empty($region->postal_codes)) {
            $score += 1;
        }

        return $score;
    }

    private function getAddressLocation(TaxCalculationInput $input): ?AddressLocation
    {
        $taxBasedOn = TaxBasedOn::from($input->taxBasedOn);

        $primary = match ($taxBasedOn) {
            TaxBasedOn::Shipping => $input->shippingAddress,
            TaxBasedOn::Billing => $input->billingAddress,
            TaxBasedOn::Store => $input->storeAddress,
        };

        return $primary
            ?? $input->billingAddress
            ?? $input->shippingAddress
            ?? $input->storeAddress;
    }

    private function calculateDiscountRatio(BigDecimal $subtotal, BigDecimal $discountTotal): BigDecimal
    {
        if ($subtotal->isZero() || $discountTotal->isZero()) {
            return BigDecimal::zero();
        }

        return $discountTotal->dividedBy($subtotal, 4, RoundingMode::HalfUp);
    }

    private function calculateDiscountedItemAmount(TaxableItem $item, BigDecimal $discountRatio): BigDecimal
    {
        $itemTotalPrice = BigDecimal::of($item->totalPrice);
        $itemDiscountAmount = $itemTotalPrice->multipliedBy($discountRatio);

        return $itemTotalPrice->minus($itemDiscountAmount);
    }

    private function calculateTaxAmount(TaxRate $taxRate, BigDecimal $taxableAmount): string
    {
        return $this->calculateTaxAmountFromRate($taxRate->rate, $taxableAmount);
    }

    private function calculateTaxAmountFromRate(string $rateValue, BigDecimal $taxableAmount): string
    {
        if ($taxableAmount->isLessThanOrEqualTo(BigDecimal::zero())) {
            return '0.0000';
        }

        $rateDecimal = BigDecimal::of($rateValue)->dividedBy(100, 4, RoundingMode::HalfUp);
        $taxAmount = $taxableAmount->multipliedBy($rateDecimal);

        return $this->formatCurrency($taxAmount);
    }

    private function formatCurrency(BigDecimal $amount): string
    {
        return $amount->toScale(4, RoundingMode::HalfUp)->toString();
    }

    /**
     * @param  list<TaxDetailInput>  $taxDetails
     */
    private function addTaxAmounts(BigDecimal $totalTaxAmount, array $taxDetails): BigDecimal
    {
        foreach ($taxDetails as $taxDetail) {
            $totalTaxAmount = $totalTaxAmount->plus(BigDecimal::of($taxDetail->taxAmount));
        }

        return $totalTaxAmount;
    }
}
