<?php

declare(strict_types=1);

use App\Models\Currency;
use App\Models\Media;
use App\Models\Setting;
use App\Queries\ApiStoreConfigQuery;

covers(ApiStoreConfigQuery::class);

uses()->group('queries', 'api');

test('it reports the store identity, locales, currencies, checkout switches and which policies are written', function (): void {
    Setting::setValue('store_name', 'Northmart');
    Setting::setValue('store_country_code', 'US');
    Setting::setValue('base_currency', 'USD');
    Setting::setValue('available_locales', ['en', 'ar']);
    Setting::setValue('default_locale', 'en');
    Setting::setValue('guest_checkout_enabled', true);
    Setting::setValue('privacy_policy', 'We keep your data safe.');

    Currency::query()->delete();
    Currency::factory()->create(['code' => 'USD', 'is_active' => true, 'decimal_places' => 2]);
    Currency::factory()->create(['code' => 'EUR', 'is_active' => false]);

    $config = (new ApiStoreConfigQuery())->execute();

    expect($config['store']['name'])->toBe('Northmart')
        ->and($config['store']['country_code'])->toBe('US')
        ->and($config['locales'])->toBe(['default' => 'en', 'available' => ['en', 'ar']])
        ->and($config['currencies']['base'])->toBe('USD')
        ->and($config['checkout']['guest_checkout_enabled'])->toBeTrue()
        ->and($config['policies'])->toBe(['privacy']);
});

test('it lists only active currencies, with the details a client needs to format money', function (): void {
    Currency::query()->delete();
    Currency::factory()->create([
        'code' => 'USD',
        'is_active' => true,
        'symbol' => '$',
        'decimal_places' => 2,
        'thousands_separator' => ',',
        'decimal_separator' => '.',
    ]);
    Currency::factory()->create(['code' => 'EUR', 'is_active' => false]);

    $currencies = (new ApiStoreConfigQuery())->execute()['currencies']['available'];

    expect($currencies)->toHaveCount(1)
        ->and($currencies[0]['code'])->toBe('USD')
        ->and($currencies[0]['symbol'])->toBe('$')
        ->and($currencies[0]['decimal_places'])->toBe(2)
        ->and($currencies[0])->toHaveKeys(['symbol_position', 'thousands_separator', 'decimal_separator']);
});

test('the logo is resolved to a url', function (): void {
    $media = Media::factory()->create();
    Setting::setValue('store_logo', (string) $media->id);

    // Settings resolve a media id into a media array, not a bare id.
    expect((new ApiStoreConfigQuery())->execute()['store']['logo_url'])->toBe($media->url);
});

test('no logo yields a null url rather than an error', function (): void {
    Setting::setValue('store_logo', null);

    expect((new ApiStoreConfigQuery())->execute()['store']['logo_url'])->toBeNull();
});
