<?php

declare(strict_types=1);

use App\Models\Announcement;
use App\Models\Setting;
use App\Queries\StorefrontLayoutDataQuery;

covers(StorefrontLayoutDataQuery::class);

uses()->group('queries', 'storefront');

test('returns expected structure', function () {
    $query = app(StorefrontLayoutDataQuery::class);
    $result = $query->execute();

    expect($result)->toBeArray()
        ->toHaveKeys(['customCss', 'customJs', 'announcements', 'trendingSearches', 'header', 'headerMenu', 'browseCategories', 'footer']);
});

test('returns footer settings', function () {
    Setting::setValue('storefront_footer_show_social_links', false);
    Setting::setValue('storefront_footer_payment_method_preset', 'credit_cards');
    Setting::setValue('store_description', 'The best shop in town.');
    Setting::setValue('store_email', 'hello@shop.test');
    Setting::setValue('store_phone', '+1 555 0100');
    Setting::setValue('store_social_facebook', 'https://facebook.com/shop');

    $result = app(StorefrontLayoutDataQuery::class)->execute();

    expect($result)
        ->and($result['store_email'])->toBe('hello@shop.test')
        ->and($result['store_phone'])->toBe('+1 555 0100');

    expect($result['footer'])->toBeArray()
        ->toHaveKeys(['menu', 'description', 'showSocialLinks', 'showPaymentBadges', 'paymentMethods', 'copyrightText', 'showPoweredBy', 'socialLinks'])
        ->and($result['footer']['description'])->toBe('The best shop in town.')
        ->and($result['footer']['showSocialLinks'])->toBeFalse()
        ->and($result['footer']['paymentMethods'])->toBe(['visa', 'mastercard', 'amex', 'discover', 'jcb', 'unionpay', 'mada'])
        ->and($result['footer']['socialLinks'])->toBe(['facebook' => 'https://facebook.com/shop']);
});

test('localizes a translatable store description', function () {
    Setting::setValue('store_description', json_encode([
        'en' => 'The best shop in town.',
        'ar' => 'أفضل متجر في المدينة.',
    ], JSON_UNESCAPED_UNICODE));

    app()->setLocale('ar');

    $result = app(StorefrontLayoutDataQuery::class)->execute();

    expect($result['footer']['description'])->toBe('أفضل متجر في المدينة.');
});

test('returns active announcements ordered by sort order', function () {
    Announcement::factory()->active()->create(['content' => ['en' => 'Second'], 'url' => null, 'sort_order' => 1]);
    Announcement::factory()->active()->create(['content' => ['en' => 'First'], 'url' => '/shop', 'sort_order' => 0]);
    Announcement::factory()->inactive()->create(['content' => ['en' => 'Hidden'], 'sort_order' => 2]);

    $result = app(StorefrontLayoutDataQuery::class)->execute();

    expect($result['announcements'])->toHaveCount(2)
        ->and($result['announcements'][0]['content'])->toBe(['en' => 'First'])
        ->and($result['announcements'][0]['url'])->toBe('/shop')
        ->and($result['announcements'][1]['content'])->toBe(['en' => 'Second'])
        ->and($result['announcements'][1]['url'])->toBeNull();
});

test('excludes scheduled and expired announcements', function () {
    Announcement::factory()->active()->scheduled()->create();
    Announcement::factory()->active()->expired()->create();

    $result = app(StorefrontLayoutDataQuery::class)->execute();

    expect($result['announcements'])->toBeEmpty();
});

test('returns custom css and js from settings', function () {
    Setting::setValue('storefront_custom_css', '.header { color: red; }');
    Setting::setValue('storefront_custom_js', 'console.log("hi");');

    $result = app(StorefrontLayoutDataQuery::class)->execute();

    expect($result['customCss'])->toBe('.header { color: red; }')
        ->and($result['customJs'])->toBe('console.log("hi");');
});

test('returns empty strings when custom code is not set', function () {
    Setting::query()->whereIn('key', ['storefront_custom_css', 'storefront_custom_js'])->delete();

    $result = app(StorefrontLayoutDataQuery::class)->execute();

    expect($result['customCss'])->toBe('')
        ->and($result['customJs'])->toBe('');
});
