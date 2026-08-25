<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CurrencySymbolPosition;
use Brick\Math\BigDecimal;
use Database\Factories\CurrencyFactory;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Override;

/**
 * @property-read int $id
 * @property-read string $code
 * @property-read string $symbol
 * @property-read string $exchange_rate
 * @property-read CurrencySymbolPosition $symbol_position
 * @property-read string $thousands_separator
 * @property-read string $decimal_separator
 * @property-read int<0, max> $decimal_places
 * @property-read bool $is_active
 * @property-read \Illuminate\Support\Carbon $created_at
 * @property-read \Illuminate\Support\Carbon $updated_at
 */
#[UseFactory(CurrencyFactory::class)]
final class Currency extends Model
{
    /** @use HasFactory<\Database\Factories\CurrencyFactory> */
    use HasFactory;

    /**
     * @return int<0, max>
     */
    public static function getDecimalPlaces(string $currencyCode): int
    {
        return self::getFormat($currencyCode)['decimal_places'];
    }

    public static function getExchangeRate(string $currencyCode): string
    {
        $currencyCode = mb_strtoupper($currencyCode);

        return Cache::memo('array')->remember(
            key: "memo:currency_exchange_rate.{$currencyCode}",
            ttl: now()->addMinute(),
            callback: function () use ($currencyCode): string {
                $rate = self::query()
                    ->where('code', $currencyCode)
                    ->value('exchange_rate');

                return $rate !== null && BigDecimal::of($rate)->isPositive()
                    ? $rate
                    : '1.0000';
            },
        );
    }

    public static function existsByCode(string $currencyCode): bool
    {
        $currencyCode = mb_strtoupper($currencyCode);

        return Cache::memo('array')->remember(
            key: "memo:currency_exists.{$currencyCode}",
            ttl: now()->addMinute(),
            callback: fn (): bool => self::query()->where('code', $currencyCode)->exists(),
        );
    }

    /**
     * @return array{
     *     symbol: string,
     *     symbol_position: CurrencySymbolPosition,
     *     thousands_separator: string,
     *     decimal_separator: string,
     *     decimal_places: int<0, max>,
     * }
     */
    public static function getFormat(string $currencyCode): array
    {
        $currencyCode = mb_strtoupper($currencyCode);

        return Cache::memo('array')->remember(
            key: "memo:currency_format.{$currencyCode}",
            ttl: now()->addMinute(),
            callback: function () use ($currencyCode): array {
                $currency = self::query()
                    ->where('code', $currencyCode)
                    ->first(['symbol', 'symbol_position', 'thousands_separator', 'decimal_separator', 'decimal_places']);

                return [
                    'symbol' => $currency->symbol ?? $currencyCode,
                    'symbol_position' => $currency->symbol_position ?? CurrencySymbolPosition::BeforeWithSpace,
                    'thousands_separator' => $currency->thousands_separator ?? ',',
                    'decimal_separator' => $currency->decimal_separator ?? '.',
                    'decimal_places' => $currency->decimal_places ?? 2,
                ];
            },
        );
    }

    #[Override]
    public function casts(): array
    {
        return [
            'exchange_rate' => 'decimal:4',
            'symbol_position' => CurrencySymbolPosition::class,
            'decimal_places' => 'integer',
            'is_active' => 'boolean',
        ];
    }
}
