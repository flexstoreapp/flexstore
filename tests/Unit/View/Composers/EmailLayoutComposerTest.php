<?php

declare(strict_types=1);

use App\Enums\DisplayTaxTotals;
use App\Models\Media;
use App\Models\Setting;
use App\View\Composers\EmailLayoutComposer;
use Illuminate\Support\Facades\View;

covers(EmailLayoutComposer::class);

uses()->group('email', 'view-composers');

function composeEmailLayout(): array
{
    $view = View::make('emails.layout', ['title' => 'Test'])->with('content', 'Body');
    (new EmailLayoutComposer)->compose($view);

    return $view->getData();
}

test('composer attaches store name from settings', function () {
    Setting::setValue('store_name', 'Acme Goods');

    $data = composeEmailLayout();

    expect($data['storeName'])->toBe('Acme Goods');
});

test('composer falls back to app name when store name is missing', function () {
    Setting::query()->where('key', 'store_name')->delete();

    $data = composeEmailLayout();

    expect($data['storeName'])->toBe(config('app.name'));
});

test('composer exposes light and dark logo media from settings', function () {
    $light = Media::factory()->create();
    $dark = Media::factory()->create();
    Setting::setValue('store_logo', (string) $light->id);
    Setting::setValue('store_logo_dark', (string) $dark->id);

    $data = composeEmailLayout();

    expect($data['storeLogo'])->toMatchArray([
        'id' => $light->id,
        'type' => $light->type->value,
        'alt' => $light->alt,
        'url' => $light->url,
        'thumbnail_url' => $light->thumbnail_url,
    ])->and($data['storeLogoDark'])->toMatchArray([
        'id' => $dark->id,
        'type' => $dark->type->value,
        'alt' => $dark->alt,
        'url' => $dark->url,
        'thumbnail_url' => $dark->thumbnail_url,
    ]);
});

test('composer resolves accent name to light and dark hex colors', function () {
    Setting::setValue('storefront_theme_color', 'green');

    $data = composeEmailLayout();

    expect($data['themeColor'])->toBe('#11813c')
        ->and($data['themeColorDark'])->toBe('#43a65f');
});

test('composer falls back to blue hex for unknown accent', function () {
    Setting::setValue('storefront_theme_color', 'not-a-real-accent');

    $data = composeEmailLayout();

    expect($data['themeColor'])->toBe('#005eb3')
        ->and($data['themeColorDark'])->toBe('#1a83db');
});

test('composer marks RTL locales correctly', function () {
    app()->setLocale('ar');

    $data = composeEmailLayout();

    expect($data['isRtl'])->toBeTrue()
        ->and($data['locale'])->toBe('ar');
});

test('composer marks LTR locales correctly', function () {
    app()->setLocale('en');

    $data = composeEmailLayout();

    expect($data['isRtl'])->toBeFalse()
        ->and($data['locale'])->toBe('en');
});

test('composer collapses store address into non-empty lines', function () {
    Setting::setValue('store_street_address', '1453 Mission St');
    Setting::setValue('store_city', 'San Francisco');
    Setting::setValue('store_state', 'CA');
    Setting::setValue('store_postal_code', '94103');
    Setting::setValue('store_country_code', 'US');

    $data = composeEmailLayout();

    expect($data['storeAddressLines'])->toBe([
        '1453 Mission St',
        'San Francisco, CA 94103',
        'United States',
    ]);
});

test('composer omits empty address parts', function () {
    Setting::setValue('store_street_address', '1453 Mission St');
    Setting::setValue('store_city', '');
    Setting::setValue('store_state', '');
    Setting::setValue('store_postal_code', '');
    Setting::setValue('store_country_code', '');

    $data = composeEmailLayout();

    expect($data['storeAddressLines'])->toBe(['1453 Mission St']);
});

test('composer exposes display tax totals setting', function () {
    Setting::setValue('display_tax_totals', DisplayTaxTotals::Itemized->value);

    $data = composeEmailLayout();

    expect($data['displayTaxTotals'])->toBe(DisplayTaxTotals::Itemized);
});

test('composer falls back to single tax total when setting missing', function () {
    Setting::query()->where('key', 'display_tax_totals')->delete();

    $data = composeEmailLayout();

    expect($data['displayTaxTotals'])->toBe(DisplayTaxTotals::Single);
});

test('composer only includes social links that are set', function () {
    Setting::setValue('store_social_facebook', 'https://facebook.com/example');
    Setting::setValue('store_social_instagram', '');
    Setting::setValue('store_social_x', 'https://x.com/example');
    Setting::setValue('store_social_tiktok', '');
    Setting::setValue('store_social_pinterest', '');
    Setting::setValue('store_social_youtube', '');

    $data = composeEmailLayout();

    expect($data['socialLinks'])->toBe([
        'Facebook' => 'https://facebook.com/example',
        'X' => 'https://x.com/example',
    ]);
});
