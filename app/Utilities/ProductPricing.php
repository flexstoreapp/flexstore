<?php

declare(strict_types=1);

namespace App\Utilities;

use Brick\Math\BigDecimal;

final readonly class ProductPricing
{
    /**
     * @param  array<string, mixed>  $product
     * @param  array<string, mixed>|null  $variant
     * @return array{price: string|null, compare_at: string|null, range: array{0: string, 1: string}|null}
     */
    public static function resolve(array $product, ?array $variant = null): array
    {
        if ($variant !== null) {
            $price = self::amount($variant['price'] ?? null);
            $compareAt = self::amount($variant['compare_at_price'] ?? null);

            return [
                'price' => $price,
                'compare_at' => self::isDiscounted($price, $compareAt) ? $compareAt : null,
                'range' => null,
            ];
        }

        $range = self::range($product['price_range'] ?? null);

        if ($range !== null && $range[0] !== $range[1]) {
            return [
                'price' => $range[0],
                'compare_at' => null,
                'range' => $range,
            ];
        }

        $price = self::collapse($range);
        $compareAt = self::collapse(self::range($product['compare_at_price_range'] ?? null));

        return [
            'price' => $price,
            'compare_at' => self::isDiscounted($price, $compareAt) ? $compareAt : null,
            'range' => null,
        ];
    }

    /**
     * @param  array<string, mixed>  $detail
     * @return array<string, mixed>|null
     */
    public static function defaultVariant(array $detail): ?array
    {
        foreach ($detail['variants'] ?? [] as $variant) {
            if (is_array($variant) && ($variant['is_default'] ?? false) === true) {
                return $variant;
            }
        }

        return null;
    }

    /**
     * @param  array{0: string, 1: string}|null  $range
     */
    private static function collapse(?array $range): ?string
    {
        if ($range === null || $range[0] !== $range[1]) {
            return null;
        }

        return $range[0];
    }

    /**
     * @return array{0: string, 1: string}|null
     */
    private static function range(mixed $value): ?array
    {
        if (! is_array($value) || ! isset($value[0], $value[1])) {
            return null;
        }

        $low = self::amount($value[0]);
        $high = self::amount($value[1]);

        if ($low === null || $high === null) {
            return null;
        }

        return [$low, $high];
    }

    private static function amount(mixed $value): ?string
    {
        if (is_string($value) && $value !== '') {
            return $value;
        }

        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        return null;
    }

    private static function isDiscounted(?string $price, ?string $compareAt): bool
    {
        if ($price === null || $compareAt === null) {
            return false;
        }

        return BigDecimal::of($compareAt)->isGreaterThan(BigDecimal::of($price));
    }
}
