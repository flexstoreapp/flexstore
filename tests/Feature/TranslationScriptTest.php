<?php

declare(strict_types=1);

use App\Http\Controllers\TranslationScriptController;
use App\Utilities\Translations;

use function Pest\Laravel\get;

covers(TranslationScriptController::class);

uses()->group('translations');

function translationScriptUrl(string $bundle = Translations::STOREFRONT, string $locale = 'en'): string
{
    return route('translations.script', [
        'locale' => $locale,
        'bundle' => $bundle,
        'version' => Translations::version($bundle, $locale),
    ]);
}

test('serves a bundle as an immutable javascript asset', function () {
    $response = get(translationScriptUrl());

    $response->assertOk()
        ->assertHeader('Content-Type', 'application/javascript; charset=utf-8')
        ->assertHeader('Cache-Control', 'immutable, max-age=31536000, public');

    expect($response->getContent())->toBe(Translations::script(Translations::STOREFRONT, 'en'));
});

test('serves each bundle separately', function (string $bundle) {
    get(translationScriptUrl($bundle))->assertOk();
})->with(Translations::BUNDLES);

test('an unknown bundle is not found', function () {
    get('/translations/en/marketing/abc123')->assertNotFound();
});

test('an uninstalled locale is not found', function () {
    get('/translations/xx/storefront/abc123')->assertNotFound();
});

test('a stale version still serves the current bundle', function () {
    $response = get('/translations/en/storefront/stale123');

    $response->assertOk();

    expect($response->getContent())->toBe(Translations::script(Translations::STOREFRONT, 'en'));
});

test('pages embed the translation bundle for client hydration', function () {
    $response = get(route('home'));

    $response->assertOk()
        ->assertSee('id="app-translations"', false)
        ->assertSee('Add to cart', false)
        ->assertDontSee('window.translations=', false);
});
