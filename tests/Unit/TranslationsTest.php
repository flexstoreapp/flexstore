<?php

declare(strict_types=1);

use App\Utilities\Translations;

uses()->group('translations');

function getLocaleBundles(): array
{
    $langDir = dirname(__DIR__, 2) . '/lang';
    $datasets = [];

    foreach (Translations::BUNDLES as $bundle) {
        foreach (glob($langDir . "/{$bundle}/*.json") ?: [] as $file) {
            $locale = basename($file, '.json');

            // Scratch locales written by installTestLocale() may appear and vanish
            // while a parallel worker is collecting this dataset.
            if (str_starts_with($locale, 'zz')) {
                continue;
            }

            $datasets["{$bundle}/{$locale}"] = [$bundle, $locale];
        }
    }

    return $datasets;
}

function getBundleKeys(string $bundle, string $locale = 'en'): array
{
    $translationFile = lang_path("{$bundle}/{$locale}.json");

    expect($translationFile)->toBeFile();

    $translations = json_decode(file_get_contents($translationFile), true);

    expect($translations)->toBeArray();

    return array_keys($translations);
}

function getAllTranslationKeys(): array
{
    $keys = [];

    foreach (Translations::BUNDLES as $bundle) {
        $keys = [...$keys, ...getBundleKeys($bundle)];
    }

    return $keys;
}

/**
 * @return array<string, string>
 */
function getKeyBundles(): array
{
    $keyBundles = [];

    foreach (Translations::BUNDLES as $bundle) {
        foreach (getBundleKeys($bundle) as $key) {
            $keyBundles[$key] = $bundle;
        }
    }

    return $keyBundles;
}

function getBundleForFile(string $relativePath): string
{
    $adminPaths = [
        'resources/js/pages/admin/',
        'resources/js/components/admin/',
        'resources/js/layouts/admin/',
        'app/Http/Controllers/Admin/',
        'app/Http/Requests/Admin/',
        'app/Importer/',
        'app/Notifications/Admin',
        'resources/views/emails/admin/',
        'resources/js/components/ui/',
        'resources/js/lib/activity-utils.ts',
        'resources/js/lib/admin-theme-config.ts',
        'resources/js/lib/order-utils.ts',
        'resources/js/lib/permissions.ts',
        'resources/js/hooks/admin/',
        'app/Enums/ProductAgeGroup.php',
        'app/Enums/ProductCondition.php',
        'app/Enums/ProductGender.php',
        'app/Enums/ReportType.php',
        'app/Enums/TaxCategory.php',
    ];

    $storefrontPaths = [
        'resources/js/pages/storefront/',
        'resources/js/components/storefront/',
        'resources/js/layouts/storefront/',
        'resources/js/hooks/storefront/',
        'app/Http/Controllers/Storefront/',
        'app/Http/Requests/Storefront/',
        'app/Http/Middleware/CheckStorefrontMaintenance.php',
        'app/Notifications/Customer',
        'app/Utilities/StorefrontHead.php',
        'resources/views/emails/customer/',
    ];

    if (str_starts_with($relativePath, 'app/Queries/') && str_ends_with($relativePath, 'ReportQuery.php')) {
        return Translations::ADMIN;
    }

    foreach ($adminPaths as $path) {
        if (str_starts_with($relativePath, $path)) {
            return Translations::ADMIN;
        }
    }

    foreach ($storefrontPaths as $path) {
        if (str_starts_with($relativePath, $path)) {
            return Translations::STOREFRONT;
        }
    }

    return Translations::COMMON;
}

/**
 * @return array<string, list<string>>
 */
function extractAllUsedTranslationKeys(): array
{
    $usedKeys = [];

    foreach ([extractTranslationKeysFromPhpFiles(), extractTranslationKeysFromTsTsxFiles(), extractTranslationKeysFromBladeFiles(), extractTranslationKeysFromAddressFieldRules()] as $keys) {
        foreach ($keys as $key => $files) {
            $usedKeys[$key] = array_merge($usedKeys[$key] ?? [], $files);
        }
    }

    return $usedKeys;
}

/**
 * Address field labels are resolved at runtime from the country rules data, so no source file references them.
 *
 * @return array<string, list<string>>
 */
function extractTranslationKeysFromAddressFieldRules(): array
{
    $path = 'resources/data/address-field-rules.json';

    /** @var array<string, array<string, array{label?: string}>> $data */
    $data = json_decode((string) file_get_contents(base_path($path)), true);

    $keys = [];

    foreach ($data as $entry) {
        foreach (['city', 'state', 'postal_code'] as $field) {
            $label = $entry[$field]['label'] ?? null;

            if ($label !== null) {
                $keys[$label][] = $path;
            }
        }
    }

    return $keys;
}

function extractTranslationKeysFromPhpFiles(): array
{
    $keys = [];
    $directories = [
        app_path(),
    ];

    $files = collectFilesWithExtensions($directories, ['php']);

    foreach ($files as $file) {
        $content = file_get_contents($file);

        // Match __('...') and __("...")
        preg_match_all('/(?<!\w)__\(\s*([\'"])((?:(?!\1).)*?)\1/', $content, $matches);

        foreach ($matches[2] as $key) {
            if (isNamespacedTranslationKey($key)) {
                continue;
            }

            $keys[$key] = $keys[$key] ?? [];
            $keys[$key][] = getRelativePath($file);
        }

        // Match trans_choice('...', ...) and trans_choice("...", ...)
        preg_match_all('/trans_choice\(\s*([\'"])((?:(?!\1).)*?)\1/', $content, $matches);

        foreach ($matches[2] as $key) {
            $keys[$key] = $keys[$key] ?? [];
            $keys[$key][] = getRelativePath($file);
        }
    }

    return $keys;
}

function extractTranslationKeysFromBladeFiles(): array
{
    $keys = [];
    $directories = [
        resource_path('views'),
    ];

    $files = collectFilesWithExtensions($directories, ['php']);

    foreach ($files as $file) {
        $content = file_get_contents($file);

        // Match @lang('...') and @lang("...") — including multiline with string concatenation
        preg_match_all('~@lang\(\s*((?:"(?:[^"\\\\]|\\\\.)*"|\'(?:[^\'\\\\]|\\\\.)*\')(?:\s*\.\s*(?:"(?:[^"\\\\]|\\\\.)*"|\'(?:[^\'\\\\]|\\\\.)*\'))*)~s', $content, $matches);

        foreach ($matches[1] as $raw) {
            // Extract individual string literals and join them
            preg_match_all('/(?:"((?:[^"\\\\]|\\\\.)*)"|\'((?:[^\'\\\\]|\\\\.)*)\')/', $raw, $parts);
            $key = '';
            foreach ($parts[0] as $i => $part) {
                $key .= $parts[1][$i] !== '' ? $parts[1][$i] : $parts[2][$i];
            }
            $key = str_replace(['\\n', '\\"'], ["\n", '"'], $key);

            if (isNamespacedTranslationKey($key)) {
                continue;
            }

            $keys[$key] = $keys[$key] ?? [];
            $keys[$key][] = getRelativePath($file);
        }

        // Match __('...') and trans_choice('...') in Blade files
        preg_match_all('/(?<!\w)(?:__|trans_choice)\(\s*([\'"])((?:(?!\1).)*?)\1/', $content, $matches);

        foreach ($matches[2] as $key) {
            if (isNamespacedTranslationKey($key)) {
                continue;
            }

            $keys[$key] = $keys[$key] ?? [];
            $keys[$key][] = getRelativePath($file);
        }
    }

    return $keys;
}

function extractTranslationKeysFromTsTsxFiles(): array
{
    $keys = [];
    $directories = [
        resource_path('js'),
    ];

    $files = collectFilesWithExtensions($directories, ['ts', 'tsx']);

    // Exclude the i18n.ts definition file itself
    $files = array_filter($files, fn (string $file) => ! str_ends_with($file, '/lib/i18n.ts'));

    foreach ($files as $file) {
        $content = file_get_contents($file);

        // Match __('...'), __nodes('...') and their double-quoted forms
        preg_match_all('/(?<!\w)(?:__nodes|__)\(\s*([\'"])((?:(?!\1).)*?)\1/', $content, $matches);

        foreach ($matches[2] as $key) {
            $keys[$key] = $keys[$key] ?? [];
            $keys[$key][] = getRelativePath($file);
        }

        // Match transChoice('...', ...) and transChoice("...", ...)
        preg_match_all('/transChoice\(\s*([\'"])((?:(?!\1).)*?)\1/', $content, $matches);

        foreach ($matches[2] as $key) {
            $keys[$key] = $keys[$key] ?? [];
            $keys[$key][] = getRelativePath($file);
        }
    }

    return $keys;
}

/**
 * @return list<string>
 */
function extractAddressFieldLabels(): array
{
    $path = resource_path('data/address-field-rules.json');

    if (! is_file($path)) {
        return [];
    }

    /** @var array<string, array<string, array{label?: string}>> $data */
    $data = json_decode((string) file_get_contents($path), true);

    $labels = ['City', 'State', 'Postal code'];

    foreach ($data as $entry) {
        foreach (['city', 'state', 'postal_code'] as $field) {
            if (isset($entry[$field]['label'])) {
                $labels[] = $entry[$field]['label'];
            }
        }
    }

    return array_values(array_unique($labels));
}

function collectFilesWithExtensions(array $directories, array $extensions): array
{
    $files = [];

    foreach ($directories as $directory) {
        if (! is_dir($directory)) {
            continue;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS),
        );

        foreach ($iterator as $file) {
            if (! $file->isFile()) {
                continue;
            }

            $extension = $file->getExtension();

            if (in_array($extension, $extensions, true)) {
                $files[] = $file->getPathname();
            }
        }
    }

    return $files;
}

function getRelativePath(string $absolutePath): string
{
    // Windows reports paths with backslashes, so normalise before trimming the
    // base path; the bundle prefixes below are all written with forward slashes.
    $normalized = str_replace('\\', '/', $absolutePath);
    $base = str_replace('\\', '/', base_path()) . '/';

    return str_starts_with($normalized, $base)
        ? mb_substr($normalized, mb_strlen($base))
        : $normalized;
}

function isNamespacedTranslationKey(string $key): bool
{
    return (bool) preg_match('/^[a-z_]+(?:\.[a-z_]+)+$/', $key);
}

test('all translation keys used in PHP files exist in lang files', function () {
    $missingKeys = array_diff_key(extractTranslationKeysFromPhpFiles(), array_flip(getAllTranslationKeys()));

    expect($missingKeys)->toBeEmpty(formatMissingKeys($missingKeys));
});

test('all translation keys used in TypeScript files exist in lang files', function () {
    $missingKeys = array_diff_key(extractTranslationKeysFromTsTsxFiles(), array_flip(getAllTranslationKeys()));

    expect($missingKeys)->toBeEmpty(formatMissingKeys($missingKeys));
});

test('all translation keys used in Blade files exist in lang files', function () {
    $missingKeys = array_diff_key(extractTranslationKeysFromBladeFiles(), array_flip(getAllTranslationKeys()));

    expect($missingKeys)->toBeEmpty(formatMissingKeys($missingKeys));
});

test('all translation keys in lang files are used in PHP or TypeScript files', function () {
    $definedKeys = getAllTranslationKeys();
    $allUsedKeys = array_unique(array_merge(
        array_keys(extractAllUsedTranslationKeys()),
        extractAddressFieldLabels(),
    ));

    $nonNamespacedKeys = array_filter($definedKeys, fn (string $key) => ! isNamespacedTranslationKey($key));
    $orphanedKeys = array_values(array_diff($nonNamespacedKeys, $allUsedKeys));

    expect($orphanedKeys)->toBeEmpty(formatKeyList('Orphaned keys in lang files', $orphanedKeys));
});

test('every key is defined in exactly one bundle', function () {
    $seen = [];
    $duplicates = [];

    foreach (Translations::BUNDLES as $bundle) {
        foreach (getBundleKeys($bundle) as $key) {
            if (isset($seen[$key])) {
                $duplicates[] = "{$key} ({$seen[$key]} + {$bundle})";

                continue;
            }

            $seen[$key] = $bundle;
        }
    }

    expect($duplicates)->toBeEmpty(formatKeyList('Keys defined in more than one bundle', $duplicates));
});

test('a side bundle key is never used by the other side', function () {
    $keyBundles = getKeyBundles();
    $violations = [];

    foreach (extractAllUsedTranslationKeys() as $key => $files) {
        $bundle = $keyBundles[$key] ?? null;

        if ($bundle === null || $bundle === Translations::COMMON) {
            continue;
        }

        foreach (array_unique($files) as $file) {
            if (getBundleForFile($file) !== $bundle) {
                $violations[] = "\"{$key}\" lives in the {$bundle} bundle but is used in {$file}";
            }
        }
    }

    expect($violations)->toBeEmpty(formatKeyList('Keys used outside their bundle', $violations));
});

test('a common bundle key is never exclusive to one side', function () {
    $keyBundles = getKeyBundles();
    $misplaced = [];

    foreach (extractAllUsedTranslationKeys() as $key => $files) {
        if (($keyBundles[$key] ?? null) !== Translations::COMMON) {
            continue;
        }

        $sides = array_values(array_unique(array_map(getBundleForFile(...), array_unique($files))));

        if (count($sides) === 1 && $sides[0] !== Translations::COMMON) {
            $misplaced[] = "\"{$key}\" is only used by {$sides[0]} files";
        }
    }

    expect($misplaced)->toBeEmpty(formatKeyList('Keys that belong in a side bundle', $misplaced));
});

test('lang files have no duplicate keys', function (string $bundle, string $locale) {
    $content = file_get_contents(lang_path("{$bundle}/{$locale}.json"));
    preg_match_all('/^\s+"((?:[^"\\\\]|\\\\.)*)"\s*:/m', $content, $matches);

    $keys = $matches[1];
    $counts = array_count_values($keys);
    $duplicates = array_filter($counts, fn (int $count) => $count > 1);

    expect($duplicates)->toBeEmpty(formatKeyList("Duplicate keys in {$bundle}/{$locale}.json", array_keys($duplicates)));
})->with(getLocaleBundles());

test('every locale defines the same keys as the base locale', function (string $bundle, string $locale) {
    $baseKeys = getBundleKeys($bundle);
    $localeKeys = getBundleKeys($bundle, $locale);

    $missingKeys = array_values(array_diff($baseKeys, $localeKeys));
    $extraKeys = array_values(array_diff($localeKeys, $baseKeys));

    expect($missingKeys)->toBeEmpty(formatKeyList("Keys missing from {$bundle}/{$locale}.json", $missingKeys));
    expect($extraKeys)->toBeEmpty(formatKeyList("Extra keys in {$bundle}/{$locale}.json", $extraKeys));
})->with(getLocaleBundles());

test('keys are alphabetically sorted within each group', function (string $bundle) {
    $content = (string) file_get_contents(lang_path("{$bundle}/en.json"));
    $unsorted = [];

    foreach (explode("\n\n", $content) as $block) {
        $keys = translationKeysInOrder($block);
        $sorted = $keys;
        usort($sorted, fn (string $a, string $b): int => sortableTranslationKey($a) <=> sortableTranslationKey($b));

        foreach ($keys as $index => $key) {
            if ($key !== $sorted[$index]) {
                $unsorted[] = "{$bundle}/en.json: \"{$key}\" is out of order";

                break;
            }
        }
    }

    expect($unsorted)->toBeEmpty(formatKeyList('Unsorted keys', $unsorted));
})->with(Translations::BUNDLES);

test('a translated locale mirrors the key order of the base locale', function () {
    $mismatches = [];

    foreach (getTranslatedLocaleBundles() as [$bundle, $locale]) {
        $baseKeys = translationKeysInOrder((string) file_get_contents(lang_path("{$bundle}/en.json")));
        $localeKeys = translationKeysInOrder((string) file_get_contents(lang_path("{$bundle}/{$locale}.json")));

        if ($localeKeys !== $baseKeys) {
            $mismatches[] = "{$bundle}/{$locale}.json does not follow the key order of {$bundle}/en.json";
        }
    }

    expect($mismatches)->toBeEmpty(formatKeyList('Locale files out of order', $mismatches));
});

test('groups are separated by a single blank line', function (string $bundle, string $locale) {
    $content = (string) file_get_contents(lang_path("{$bundle}/{$locale}.json"));
    $blocks = count(explode("\n\n", $content));

    expect($content)->not->toContain("\n\n\n")
        ->and($content)->toStartWith("{\n")
        ->and($content)->toEndWith("}\n")
        ->and($blocks)->toBeGreaterThan(1, "{$bundle}/{$locale}.json has no groups — it was likely rewritten by a script instead of edited in place");
})->with(getLocaleBundles());

/**
 * @return list<string>
 */
function translationKeysInOrder(string $content): array
{
    preg_match_all('/^\s+"((?:[^"\\\\]|\\\\.)*)"\s*:/m', $content, $matches);

    return $matches[1];
}

function sortableTranslationKey(string $key): string
{
    return preg_replace('/^[^0-9a-z]+/', '', mb_strtolower($key)) . "\x00" . $key;
}

function getTranslatedLocaleBundles(): array
{
    return array_filter(getLocaleBundles(), static fn (array $dataset): bool => $dataset[1] !== 'en');
}

function formatMissingKeys(array $missingKeys): string
{
    if ($missingKeys === []) {
        return '';
    }

    $lines = ['Missing translation keys in lang files:'];

    foreach ($missingKeys as $key => $files) {
        $lines[] = "  \"{$key}\" used in: " . implode(', ', array_unique($files));
    }

    return implode("\n", $lines);
}

function formatKeyList(string $heading, array $keys): string
{
    if ($keys === []) {
        return '';
    }

    return $heading . ":\n  " . implode("\n  ", $keys);
}
