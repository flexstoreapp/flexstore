<?php

declare(strict_types=1);

use App\Utilities\StorefrontLinkTarget;

covers(StorefrontLinkTarget::class);

uses()->group('utilities', 'api');

test('it resolves storefront paths to a navigable target', function (string $link, array $expected): void {
    expect(StorefrontLinkTarget::for($link))->toBe($expected);
})->with([
    ['/', ['type' => 'home']],
    ['/shop', ['type' => 'shop']],
    ['/shop?on_sale=true', ['type' => 'shop', 'query' => ['on_sale' => 'true']]],
    ['/shop?sort=latest&rating=4', ['type' => 'shop', 'query' => ['sort' => 'latest', 'rating' => '4']]],
    ['/search?query=knit', ['type' => 'search', 'query' => 'knit']],
    ['/categories', ['type' => 'categories']],
    ['/categories/electronics', ['type' => 'category', 'handle' => 'electronics']],
    ['/brands', ['type' => 'brands']],
    ['/brands/northwind', ['type' => 'brand', 'handle' => 'northwind']],
    ['/products/merino-crew-knit', ['type' => 'product', 'handle' => 'merino-crew-knit']],
    ['/flash-sales', ['type' => 'flash_sales']],
    ['/flash-sales/winter', ['type' => 'flash_sale', 'handle' => 'winter']],
    ['/blog', ['type' => 'blog']],
    ['/blog/how-to-knit', ['type' => 'post', 'handle' => 'how-to-knit']],
    ['/cart', ['type' => 'cart']],
    ['/checkout', ['type' => 'checkout']],
    ['/policies/privacy', ['type' => 'policy', 'policy' => 'privacy']],
    ['/account/orders', ['type' => 'account']],
]);

test('an absolute link is marked external', function (): void {
    expect(StorefrontLinkTarget::for('https://example.com/promo'))
        ->toBe(['type' => 'external', 'url' => 'https://example.com/promo']);
});

test('an unrecognised path falls back to the raw url', function (): void {
    expect(StorefrontLinkTarget::for('/shipping'))->toBe(['type' => 'url', 'url' => '/shipping'])
        ->and(StorefrontLinkTarget::for('/categories/a/b'))->toBe(['type' => 'url', 'url' => '/categories/a/b']);
});

test('a missing or blank link has no target', function (mixed $link): void {
    expect(StorefrontLinkTarget::for($link))->toBeNull();
})->with([null, '', '   ', 42]);

test('a trailing slash does not change the target', function (): void {
    expect(StorefrontLinkTarget::for('/categories/electronics/'))
        ->toBe(['type' => 'category', 'handle' => 'electronics']);
});
