<?php

declare(strict_types=1);

namespace App\Utilities;

final readonly class LocalizedText
{
    public static function resolve(mixed $value): ?string
    {
        $map = self::map($value);

        if ($map === null) {
            return is_string($value) && $value !== '' ? $value : null;
        }

        $locale = app()->getLocale();
        $fallback = (string) config('app.fallback_locale', 'en');
        $localized = $map[$locale] ?? $map[$fallback] ?? reset($map);

        return is_string($localized) && $localized !== '' ? $localized : null;
    }

    public static function merge(mixed $existing, string $text): string
    {
        $map = self::map($existing);

        if ($map === null) {
            return $text;
        }

        $map[app()->getLocale()] = $text;

        return json_encode($map, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: $text;
    }

    /**
     * @return array<string, string>|null
     */
    private static function map(mixed $value): ?array
    {
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            $value = is_array($decoded) ? $decoded : null;
        }

        if (! is_array($value) || $value === []) {
            return null;
        }

        foreach ($value as $locale => $text) {
            if (! is_string($locale) || $locale === '' || ! is_string($text)) {
                return null;
            }
        }

        return $value;
    }
}
