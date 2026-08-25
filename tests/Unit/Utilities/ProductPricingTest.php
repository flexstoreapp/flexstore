<?php

declare(strict_types=1);

use App\Utilities\ProductPricing;

covers(ProductPricing::class);

uses()->group('utilities');

test('a compare-at price that is not higher than the selling price is ignored', function () {
    expect(ProductPricing::resolve(
        ['price_range' => ['30.0000', '30.0000'], 'compare_at_price_range' => ['20.0000', '20.0000']],
    )['compare_at'])->toBeNull();
});

test('a product with a price range keeps the regular range when there is no flash range', function () {
    expect(ProductPricing::resolve([
        'price_range' => ['20.0000', '40.0000'],
        'flash_price_range' => null,
    ]))->toBe([
        'price' => '20.0000',
        'compare_at' => null,
        'range' => ['20.0000', '40.0000'],
    ]);
});

test('a simple product without a flash sale uses its collapsed price and compare-at', function () {
    expect(ProductPricing::resolve([
        'price_range' => ['80.0000', '80.0000'],
        'compare_at_price_range' => ['100.0000', '100.0000'],
    ]))->toBe([
        'price' => '80.0000',
        'compare_at' => '100.0000',
        'range' => null,
    ]);
});

test('defaultVariant returns the marked default variant', function () {
    expect(ProductPricing::defaultVariant([
        'variants' => [
            ['id' => 'a', 'is_default' => false, 'price' => '10.0000'],
            ['id' => 'b', 'is_default' => true, 'price' => '20.0000'],
        ],
    ]))->toMatchArray(['id' => 'b', 'price' => '20.0000']);
});
