<?php

declare(strict_types=1);

namespace App\Utilities;

use App\Models\Currency;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;

final readonly class CurrencyAmount
{
    public static function toSmallestUnit(string $amount, string $currencyCode): int
    {
        $decimalPlaces = Currency::getDecimalPlaces($currencyCode);

        return BigDecimal::of($amount)
            ->toScale($decimalPlaces, RoundingMode::HalfUp)
            ->multipliedBy(10 ** $decimalPlaces)
            ->toScale(0, RoundingMode::HalfUp)
            ->toInt();
    }

    public static function fromSmallestUnit(int|string $amount, string $currencyCode): string
    {
        $decimalPlaces = Currency::getDecimalPlaces($currencyCode);

        return BigDecimal::of($amount)
            ->dividedBy(10 ** $decimalPlaces, 4, RoundingMode::HalfUp)
            ->toString();
    }

    public static function format(string $amount, string $currencyCode): string
    {
        $decimalPlaces = Currency::getDecimalPlaces($currencyCode);

        return BigDecimal::of($amount)->toScale($decimalPlaces, RoundingMode::HalfUp)->toString();
    }
}
