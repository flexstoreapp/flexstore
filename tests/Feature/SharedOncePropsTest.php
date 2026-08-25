<?php

declare(strict_types=1);

use App\Http\Middleware\HandleInertiaRequests;
use Inertia\Support\Header;

use function Pest\Laravel\get;
use function Pest\Laravel\withHeaders;

covers(HandleInertiaRequests::class);

uses()->group('middleware');

$storefrontOnceProps = ['appUrl', 'storeName', 'storeLogo', 'storeLogoDark', 'defaultLocale', 'availableLocales', 'sellingCountries', 'baseCurrency', 'availableCurrencies', 'storefront', 'theme'];

test('storefront layout props are shared once', function () use ($storefrontOnceProps) {
    $response = get(route('home'));

    $response->assertOk();

    $once = $response->viewData('page')['onceProps'] ?? [];

    expect(array_keys($once))->toEqualCanonicalizing($storefrontOnceProps);
});

test('storefront layout props are omitted once the client has them', function () use ($storefrontOnceProps) {
    $version = get(route('home'))->viewData('page')['version'];

    $response = withHeaders([
        Header::INERTIA => 'true',
        Header::VERSION => $version,
        Header::EXCEPT_ONCE_PROPS => implode(',', $storefrontOnceProps),
    ])->get(route('shop.index'));

    $response->assertOk();

    $props = json_decode($response->getContent(), true)['props'];

    expect($props)->not->toHaveKey('storefront')
        ->and($props)->not->toHaveKey('availableCurrencies')
        ->and($props)->not->toHaveKey('theme')
        ->and($props)->toHaveKey('cart')
        ->and($props)->toHaveKey('products');
});

test('mutable storefront props are never shared once', function () {
    $response = withHeaders([
        Header::INERTIA => 'true',
        Header::VERSION => Inertia\Inertia::getVersion(),
    ])->get(route('shop.index'));

    $once = array_keys(json_decode($response->getContent(), true)['onceProps'] ?? []);

    expect($once)->not->toContain('cart')
        ->and($once)->not->toContain('wishlist')
        ->and($once)->not->toContain('compare')
        ->and($once)->not->toContain('auth')
        ->and($once)->not->toContain('flash')
        ->and($once)->not->toContain('activeLocale')
        ->and($once)->not->toContain('activeCurrency');
});
