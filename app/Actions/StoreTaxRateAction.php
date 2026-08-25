<?php

declare(strict_types=1);

namespace App\Actions;

use App\DTOs\StoreTaxRateInput;
use App\Models\TaxRate;

final readonly class StoreTaxRateAction
{
    public function handle(StoreTaxRateInput $input): TaxRate
    {
        return TaxRate::query()->create([
            'name' => $input->name,
            'tax_category' => $input->taxCategory,
            'region_id' => $input->regionId,
            'rate' => $input->rate,
            'priority' => $input->priority ?? 0,
            'min_order_value' => $input->minOrderValue,
            'max_order_value' => $input->maxOrderValue,
            'is_compound' => $input->isCompound,
            'is_active' => $input->isActive,
        ]);
    }
}
