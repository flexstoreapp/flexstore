<?php

declare(strict_types=1);

use App\Utilities\Translations;
use Illuminate\Support\Facades\Cache;

covers(Translations::class);

uses()->group('translations');

test('a bundle merges the common bundle with its own strings', function () {
    $admin = Translations::bundle(Translations::ADMIN, 'en');

    $adminOnly = json_decode((string) file_get_contents(lang_path('admin/en.json')), true);
    $common = json_decode((string) file_get_contents(lang_path('common/en.json')), true);

    expect($admin)->toHaveKeys([array_key_first($adminOnly), array_key_first($common)]);
});

test('a bundle excludes the other side', function () {
    $storefrontOnly = json_decode((string) file_get_contents(lang_path('storefront/en.json')), true);

    expect(Translations::bundle(Translations::ADMIN, 'en'))->not->toHaveKey(array_key_first($storefrontOnly));
});

test('a bundle falls back to the base locale for untranslated strings', function () {
    installTestLocale('zztb', ['Save' => 'Guardar']);

    $common = Translations::bundle(Translations::COMMON, 'zztb');
    $english = json_decode((string) file_get_contents(lang_path('common/en.json')), true);

    expect($common['Save'])->toBe('Guardar')
        ->and($common['Cancel'])->toBe($english['Cancel']);
})->after(fn () => removeTestLocale('zztb'));

test('the script assigns the bundle to window', function () {
    $script = Translations::script(Translations::STOREFRONT, 'en');

    expect($script)->toStartWith('window.translations={')
        ->and($script)->toEndWith(';');

    $decoded = json_decode(mb_substr($script, mb_strlen('window.translations='), -1), true);

    expect($decoded)->toBe(Translations::bundle(Translations::STOREFRONT, 'en'));
});

test('the script is cached and rebuilt when a bundle changes', function () {
    Cache::flush();

    installTestLocale('zztb', ['Save' => 'Guardar']);
    $version = Translations::version(Translations::COMMON, 'zztb');

    expect(Translations::script(Translations::COMMON, 'zztb'))->toContain('"Save":"Guardar"')
        ->and(Cache::has("translations.script.common.zztb.{$version}"))->toBeTrue();

    installTestLocale('zztb', ['Save' => 'Sauvegarder']);

    expect(Translations::version(Translations::COMMON, 'zztb'))->not->toBe($version)
        ->and(Translations::script(Translations::COMMON, 'zztb'))->toContain('"Save":"Sauvegarder"');
})->after(fn () => removeTestLocale('zztb'));

test('the version changes only when the bundle files change', function () {
    $version = Translations::version(Translations::ADMIN, 'en');

    expect(Translations::version(Translations::ADMIN, 'en'))->toBe($version)
        ->and(Translations::version(Translations::STOREFRONT, 'en'))->not->toBe($version);
});

test('available locales are discovered from the shipped bundles', function () {
    $shipped = array_map(
        static fn (string $path): string => basename($path, '.json'),
        glob(lang_path('common/*.json')) ?: [],
    );

    sort($shipped);

    expect(Translations::availableLocales())->toBe($shipped)
        ->and(Translations::availableLocales())->toContain('en')
        ->and(Translations::hasLocale('en'))->toBeTrue()
        ->and(Translations::hasLocale('xx'))->toBeFalse();
});

test('an uninstalled locale resolves to the fallback locale', function () {
    expect(Translations::resolveLocale('en'))->toBe('en')
        ->and(Translations::resolveLocale('xx'))->toBe('en');
});

test('a locale with a path traversal attempt is rejected', function () {
    expect(Translations::hasLocale('../common/en'))->toBeFalse()
        ->and(Translations::hasLocale('..'))->toBeFalse()
        ->and(Translations::hasLocale(''))->toBeFalse()
        ->and(Translations::resolveLocale('../common/en'))->toBe('en');
});
