<?php

declare(strict_types=1);

use App\Enums\ProductSortOption;

covers(ProductSortOption::class);

uses()->group('enums', 'product');

test('exposes the storefront sort values', function () {
    expect(array_map(fn (ProductSortOption $case): string => $case->value, ProductSortOption::cases()))
        ->toBe(['relevance', 'latest', 'price_asc', 'price_desc', 'name_asc', 'name_desc']);
});

test('resolve keeps a requested option that is valid', function () {
    expect(ProductSortOption::resolve('price_desc', false, 'latest'))->toBe('price_desc');
});

test('resolve falls back to relevance when searching', function () {
    expect(ProductSortOption::resolve('unknown', true, 'latest'))->toBe('relevance')
        ->and(ProductSortOption::resolve(null, true, 'latest'))->toBe('relevance');
});

test('resolve falls back to the given default when not searching', function () {
    expect(ProductSortOption::resolve('unknown', false, 'name_asc'))->toBe('name_asc')
        ->and(ProductSortOption::resolve(null, false, 'latest'))->toBe('latest');
});
