<?php

declare(strict_types=1);

namespace App\Utilities;

use App\Enums\CurrencySymbolPosition;
use App\Models\Currency;
use App\Models\Setting;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;

final readonly class MoneyFormatter
{
    public static function format(string|float|int $amount, ?string $currencyCode = null): string
    {
        $numericValue = self::normalizeNumericValue($amount);
        $currencyCode ??= (string) Setting::getValue('base_currency', 'USD');
        $format = Currency::getFormat($currencyCode);

        $isNegative = $numericValue < 0;
        $formatted = number_format(abs($numericValue), $format['decimal_places'], $format['decimal_separator'], $format['thousands_separator']);
        $sign = $isNegative ? '-' : '';

        return $sign . self::applySymbol($formatted, $format['symbol'], $format['symbol_position']);
    }

    public static function withSymbol(string $value, ?string $currencyCode = null): string
    {
        $currencyCode ??= (string) Setting::getValue('base_currency', 'USD');
        $format = Currency::getFormat($currencyCode);

        return self::applySymbol($value, $format['symbol'], $format['symbol_position']);
    }

    public static function toDecimal(string|int|null $value): string
    {
        return BigDecimal::of($value ?? '0')->toScale(4, RoundingMode::HalfUp)->toString();
    }

    public static function divideToDecimal(string|int|null $dividend, int $divisor): string
    {
        if ($divisor <= 0) {
            return '0.0000';
        }

        return BigDecimal::of($dividend ?? '0')->dividedBy($divisor, 4, RoundingMode::HalfUp)->toString();
    }

    private static function applySymbol(string $value, string $symbol, CurrencySymbolPosition $position): string
    {
        return match ($position) {
            CurrencySymbolPosition::After => "{$value}{$symbol}",
            CurrencySymbolPosition::AfterWithSpace => "{$value} {$symbol}",
            CurrencySymbolPosition::BeforeWithSpace => "{$symbol} {$value}",
            CurrencySymbolPosition::Before => "{$symbol}{$value}",
        };
    }

    private static function normalizeNumericValue(string|float|int $amount): float
    {
        if (is_numeric($amount)) {
            $numericValue = (float) $amount;

            return is_nan($numericValue) ? 0.0 : $numericValue;
        }

        return 0.0;
    }
}
