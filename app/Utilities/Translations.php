<?php

declare(strict_types=1);

namespace App\Utilities;

use Illuminate\Support\Facades\Cache;

final class Translations
{
    public const string COMMON = 'common';

    public const string ADMIN = 'admin';

    public const string STOREFRONT = 'storefront';

    /**
     * @var list<string>
     */
    public const array BUNDLES = [self::COMMON, self::ADMIN, self::STOREFRONT];

    /**
     * @return array<string, string>
     */
    public static function bundle(string $bundle, string $locale): array
    {
        return [...self::translations(self::COMMON, $locale), ...self::translations($bundle, $locale)];
    }

    public static function script(string $bundle, string $locale): string
    {
        return Cache::remember(
            "translations.script.{$bundle}.{$locale}." . self::version($bundle, $locale),
            now()->addMonth(),
            fn (): string => 'window.translations=' . json_encode(
                self::bundle($bundle, $locale),
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR,
            ) . ';',
        );
    }

    public static function version(string $bundle, string $locale): string
    {
        $signature = '';

        foreach ([self::COMMON, $bundle] as $group) {
            $path = self::path($group, $locale);
            $signature .= is_file($path) ? filemtime($path) . ':' . filesize($path) . '|' : '|';
        }

        return mb_substr(hash('xxh128', $signature), 0, 12);
    }

    public static function isBundle(string $bundle): bool
    {
        return in_array($bundle, self::BUNDLES, true);
    }

    /**
     * @return list<string>
     */
    public static function availableLocales(): array
    {
        $directory = lang_path(self::COMMON);
        $entries = is_dir($directory) ? scandir($directory) ?: [] : [];

        $locales = [];

        foreach ($entries as $entry) {
            if (str_ends_with($entry, '.json')) {
                $locales[] = mb_substr($entry, 0, -5);
            }
        }

        sort($locales);

        return $locales;
    }

    public static function hasLocale(string $locale): bool
    {
        return preg_match('/^[A-Za-z][A-Za-z0-9_-]*$/', $locale) === 1
            && is_file(self::path(self::COMMON, $locale));
    }

    public static function resolveLocale(string $locale): string
    {
        return self::hasLocale($locale) ? $locale : (string) config('app.fallback_locale', 'en');
    }

    /**
     * @return array<string, string>
     */
    private static function translations(string $bundle, string $locale): array
    {
        $fallback = (string) config('app.fallback_locale', 'en');

        $translations = self::read($bundle, $fallback);

        if ($locale !== $fallback) {
            return [...$translations, ...self::read($bundle, $locale)];
        }

        return $translations;
    }

    /**
     * @return array<string, string>
     */
    private static function read(string $bundle, string $locale): array
    {
        $path = self::path($bundle, $locale);

        if (! is_file($path)) {
            return [];
        }

        /** @var array<string, string> $translations */
        $translations = json_decode((string) file_get_contents($path), true) ?: [];

        return $translations;
    }

    private static function path(string $bundle, string $locale): string
    {
        return lang_path("{$bundle}/{$locale}.json");
    }
}
