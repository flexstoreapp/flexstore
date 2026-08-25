<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\Currency as CurrencyEnum;
use App\Models\Currency;
use Illuminate\Database\Seeder;

final class CurrencySeeder extends Seeder
{
    public function run(): void
    {
        $this->createCurrency(CurrencyEnum::EUR, '€', 0.85);
        $this->createCurrency(CurrencyEnum::GBP, '£', 1.0);
    }

    private function createCurrency(CurrencyEnum $currency, string $symbol, float $exchangeRate): void
    {
        Currency::query()->create([
            'code' => $currency->name,
            'symbol' => $symbol,
            'exchange_rate' => $exchangeRate,
            'is_active' => true,
        ]);
    }
}
