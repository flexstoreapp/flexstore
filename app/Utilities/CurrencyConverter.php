<?php

declare(strict_types=1);

namespace App\Utilities;

use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;

final readonly class CurrencyConverter
{
    /**
     * @param  int<0, max>  $currencyDecimalPlaces
     */
    public function convert(string $amount, string $exchangeRate, int $currencyDecimalPlaces = 2): string
    {
        return BigDecimal::of($amount)
            ->multipliedBy($exchangeRate)
            ->toScale($currencyDecimalPlaces, RoundingMode::HalfUp)
            ->toScale(4)
            ->toString();
    }

    /**
     * @param  int<0, max>  $currencyDecimalPlaces
     */
    public function round(string $amount, int $currencyDecimalPlaces): string
    {
        return BigDecimal::of($amount)
            ->toScale($currencyDecimalPlaces, RoundingMode::HalfUp)
            ->toScale(4)
            ->toString();
    }

    /**
     * @param  int<0, max>  $scale
     */
    public function convertBack(string $amount, string $exchangeRate, int $scale = 4): string
    {
        return BigDecimal::of($amount)
            ->dividedBy($exchangeRate, 6, RoundingMode::HalfUp)
            ->toScale($scale, RoundingMode::HalfUp)
            ->toString();
    }
}
