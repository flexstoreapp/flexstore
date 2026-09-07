<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\CurrencySymbolPosition;
use App\Models\Currency;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Currency>
 */
final class CurrencyFactory extends Factory
{
    public function definition(): array
    {
        return [
            'code' => fake()->unique()->currencyCode(),
            'symbol' => fake()->randomElement(['$', '€', '£', '¥']),
            'exchange_rate' => fake()->randomFloat(4, 0.1, 10),
            'symbol_position' => CurrencySymbolPosition::Before,
            'thousands_separator' => ',',
            'decimal_separator' => '.',
            'decimal_places' => 2,
            'is_active' => fake()->boolean(80),
        ];
    }

    public function active()
    {
        return $this->state(fn (): array => [
            'is_active' => true,
        ]);
    }

    public function inactive()
    {
        return $this->state(fn (): array => [
            'is_active' => false,
        ]);
    }

    public function nonDefault()
    {
        return $this->state(fn (): array => [
            'code' => fake()->unique()->randomElement(['EUR', 'GBP', 'JPY', 'CAD', 'AUD', 'CHF', 'CNY', 'SEK', 'NZD']),
        ]);
    }
}
