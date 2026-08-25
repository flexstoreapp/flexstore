<?php

declare(strict_types=1);

namespace App\DTOs;

use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;

final readonly class TaxCalculationResult
{
    /**
     * @param  list<TaxDetailInput>  $taxDetails
     */
    public function __construct(
        public string $taxTotal,
        public array $taxDetails,
    ) {
    }

    /**
     * @param  int<0, max>  $decimalPlaces
     */
    public function scaledTo(int $decimalPlaces): self
    {
        $scaledDetails = array_map(function (TaxDetailInput $detail) use ($decimalPlaces): TaxDetailInput {
            $taxableAmount = BigDecimal::of($detail->taxableAmount)
                ->toScale($decimalPlaces, RoundingMode::HalfUp)
                ->toScale(4)
                ->toString();
            $taxAmount = BigDecimal::of($detail->taxAmount)
                ->toScale($decimalPlaces, RoundingMode::HalfUp)
                ->toScale(4)
                ->toString();

            return $detail->withScaledAmounts($taxableAmount, $taxAmount);
        }, $this->taxDetails);

        $taxTotal = BigDecimal::zero();
        foreach ($scaledDetails as $detail) {
            $taxTotal = $taxTotal->plus($detail->taxAmount);
        }

        return new self(
            taxTotal: $taxTotal->toScale(4, RoundingMode::HalfUp)->toString(),
            taxDetails: $scaledDetails,
        );
    }

    /**
     * @return list<array{
     *     tax_name: array<string, string>,
     *     tax_rate: string,
     *     taxable_amount: string,
     *     tax_amount: string,
     * }>
     */
    public function aggregatedTaxDetails(): array
    {
        $grouped = [];

        foreach ($this->taxDetails as $detail) {
            $key = $detail->taxRateId ?? json_encode($detail->taxName) . ':' . $detail->taxRate;

            if (! isset($grouped[$key])) {
                $grouped[$key] = [
                    'tax_name' => $detail->taxName,
                    'tax_rate' => $detail->taxRate,
                    'taxable_amount' => BigDecimal::zero(),
                    'tax_amount' => BigDecimal::zero(),
                ];
            }

            $grouped[$key]['taxable_amount'] = $grouped[$key]['taxable_amount']->plus($detail->taxableAmount);
            $grouped[$key]['tax_amount'] = $grouped[$key]['tax_amount']->plus($detail->taxAmount);
        }

        return array_values(array_map(
            fn (array $row): array => [
                'tax_name' => $row['tax_name'],
                'tax_rate' => $row['tax_rate'],
                'taxable_amount' => $row['taxable_amount']->toScale(4, RoundingMode::HalfUp)->toString(),
                'tax_amount' => $row['tax_amount']->toScale(4, RoundingMode::HalfUp)->toString(),
            ],
            $grouped,
        ));
    }

    /**
     * @return list<array{
     *     order_item_id: int|null,
     *     tax_rate_id: int|null,
     *     item_type: string,
     *     tax_name: string,
     *     tax_rate: string,
     *     taxable_amount: string,
     *     tax_amount: string,
     *     is_compound: bool,
     *     proportion: string|null,
     *     order_id: int,
     *     created_at: \Carbon\CarbonInterface,
     *     updated_at: \Carbon\CarbonInterface,
     * }>
     */
    public function taxDetailsForStorage(int $orderId): array
    {
        return array_map(
            fn (TaxDetailInput $detail): array => [
                'order_item_id' => $detail->orderItemId,
                'tax_rate_id' => $detail->taxRateId,
                'item_type' => $detail->itemType->value,
                'tax_name' => json_encode($detail->taxName, JSON_THROW_ON_ERROR),
                'tax_rate' => $detail->taxRate,
                'taxable_amount' => $detail->taxableAmount,
                'tax_amount' => $detail->taxAmount,
                'is_compound' => $detail->isCompound,
                'proportion' => $detail->proportion,
                'order_id' => $orderId,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            $this->taxDetails,
        );
    }
}
