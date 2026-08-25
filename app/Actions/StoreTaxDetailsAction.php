<?php

declare(strict_types=1);

namespace App\Actions;

use App\DTOs\TaxDetailInput;
use App\Models\OrderTaxDetail;
use Illuminate\Support\Facades\Date;

final readonly class StoreTaxDetailsAction
{
    /**
     * @param  list<TaxDetailInput>  $taxDetails
     */
    public function handle(int $orderId, array $taxDetails): void
    {
        if ($taxDetails === []) {
            return;
        }

        $now = Date::now();

        $rows = array_map(
            fn (TaxDetailInput $detail): array => [
                'order_id' => $orderId,
                'order_item_id' => $detail->orderItemId,
                'tax_rate_id' => $detail->taxRateId,
                'item_type' => $detail->itemType->value,
                'tax_name' => json_encode($detail->taxName, JSON_THROW_ON_ERROR),
                'tax_rate' => $detail->taxRate,
                'taxable_amount' => $detail->taxableAmount,
                'tax_amount' => $detail->taxAmount,
                'is_compound' => $detail->isCompound,
                'proportion' => $detail->proportion,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            $taxDetails,
        );

        OrderTaxDetail::query()->insert($rows);
    }
}
