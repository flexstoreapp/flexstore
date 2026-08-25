<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\TaxCategory;
use App\Models\Region;
use App\Models\TaxRate;
use Illuminate\Database\Seeder;

final class TaxRateSeeder extends Seeder
{
    public function run(): void
    {
        $northAmericaRegion = Region::query()
            ->where('name->en', 'North America')
            ->first();

        if (! $northAmericaRegion) {
            return;
        }

        TaxRate::query()->create([
            'name' => 'Standard VAT',
            'rate' => 20.00,
            'region_id' => $northAmericaRegion->id,
            'tax_category' => null,
            'priority' => 10,
            'is_compound' => false,
            'is_active' => true,
        ]);

        TaxRate::query()->create([
            'name' => 'Reduced VAT',
            'rate' => 5.00,
            'region_id' => $northAmericaRegion->id,
            'tax_category' => TaxCategory::FoodAndBeverages,
            'priority' => 20,
            'is_compound' => false,
            'is_active' => true,
        ]);
    }
}
