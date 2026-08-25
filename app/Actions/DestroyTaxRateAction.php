<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\TaxRate;

final readonly class DestroyTaxRateAction
{
    public function handle(TaxRate $taxRate): bool
    {
        return (bool) $taxRate->delete();
    }
}
