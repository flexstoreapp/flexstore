<?php

declare(strict_types=1);

namespace App\Address;

final readonly class PostalCodeMatcher
{
    /**
     * Supported pattern forms (case-insensitive, surrounding whitespace ignored):
     *  - Exact:    "90210", "100-0001"   (literal match; hyphens are literal, e.g., JP/ZIP+4)
     *  - Wildcard: "902*"                (prefix match, works for alphanumeric postal_codes)
     *  - Range:    "90210..90299"        (inclusive numeric range, ".." separator)
     *
     * @param  list<string>  $patterns
     */
    public static function matches(string $postal_code, array $patterns): bool
    {
        $postal_code = self::normalize($postal_code);

        if ($postal_code === '') {
            return false;
        }

        return array_any($patterns, fn (string $pattern): bool => self::matchesPattern($postal_code, self::normalize($pattern)));
    }

    public static function isWildcard(string $pattern): bool
    {
        return str_ends_with($pattern, '*');
    }

    public static function isRange(string $pattern): bool
    {
        return (bool) preg_match('/^\d+\.\.\d+$/', $pattern);
    }

    private static function matchesPattern(string $postal_code, string $pattern): bool
    {
        if ($pattern === '') {
            return false;
        }

        if (self::isWildcard($pattern)) {
            return str_starts_with($postal_code, mb_substr($pattern, 0, -1));
        }

        if (self::isRange($pattern)) {
            return self::matchesRange($postal_code, $pattern);
        }

        return $postal_code === $pattern;
    }

    private static function matchesRange(string $postal_code, string $pattern): bool
    {
        if (! ctype_digit($postal_code)) {
            return false;
        }

        [$start, $end] = explode('..', $pattern);

        $value = (int) $postal_code;

        return $value >= (int) $start && $value <= (int) $end;
    }

    private static function normalize(string $value): string
    {
        return mb_strtoupper(mb_trim($value));
    }
}
