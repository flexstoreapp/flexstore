<?php

declare(strict_types=1);

namespace App\Actions;

use App\DTOs\UpdateTaxRateInput;
use App\Models\TaxRate;

final readonly class UpdateTaxRateAction
{
    public function handle(TaxRate $taxRate, UpdateTaxRateInput $input): TaxRate
    {
        $taxRate->update([
            'name' => $input->has('name') ? $input->name : $taxRate->name,
            'tax_category' => $input->has('tax_category') ? $input->taxCategory : $taxRate->tax_category,
            'region_id' => $input->has('region_id') ? $input->regionId : $taxRate->region_id,
            'rate' => $input->has('rate') ? $input->rate : $taxRate->rate,
            'priority' => $input->has('priority') ? $input->priority : $taxRate->priority,
            'min_order_value' => $input->has('min_order_value') ? $input->minOrderValue : $taxRate->min_order_value,
            'max_order_value' => $input->has('max_order_value') ? $input->maxOrderValue : $taxRate->max_order_value,
            'is_compound' => $input->has('is_compound') ? $input->isCompound : $taxRate->is_compound,
            'is_active' => $input->has('is_active') ? $input->isActive : $taxRate->is_active,
        ]);

        return $taxRate;
    }
}
