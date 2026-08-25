<?php

declare(strict_types=1);

namespace Tests\Unit\Queries;

use App\DTOs\ProductFilterInput;
use App\Enums\ProductSortOption;
use App\Filters\Criteria\StorefrontSortCriterion;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Queries\StorefrontProductListQuery;

covers(StorefrontProductListQuery::class, StorefrontSortCriterion::class);

uses()->group('queries');

/**
 * @return list<string>
 */
function sortedHandles(ProductSortOption $sort): array
{
    $paginator = app(StorefrontProductListQuery::class)->execute(new ProductFilterInput(sort: $sort->value));

    /** @var list<string> $handles */
    $handles = $paginator->getCollection()->pluck('url_handle')->all();

    return $handles;
}

function variantProduct(string $handle, string ...$prices): void
{
    $product = Product::factory()->active()->create(['url_handle' => $handle, 'price' => null, 'in_stock' => null]);

    foreach ($prices as $price) {
        ProductVariant::factory()->for($product)->create([
            'price' => $price,
            'in_stock' => true,
        ]);
    }
}

test('price low to high orders variant products by their lowest variant price', function () {
    Product::factory()->active()->create(['url_handle' => 'simple-cheap', 'price' => '10.00']);
    Product::factory()->active()->create(['url_handle' => 'simple-mid', 'price' => '50.00']);
    Product::factory()->active()->create(['url_handle' => 'simple-expensive', 'price' => '900.00']);
    variantProduct('variant-cheap', '5.00', '7.00');
    variantProduct('variant-mid', '60.00', '80.00');
    variantProduct('variant-expensive', '500.00', '1200.00');

    expect(sortedHandles(ProductSortOption::PriceLowHigh))->toBe([
        'variant-cheap',
        'simple-cheap',
        'simple-mid',
        'variant-mid',
        'variant-expensive',
        'simple-expensive',
    ]);
});

test('price range filter includes variant products whose variants fall in the range', function () {
    Product::factory()->active()->create(['url_handle' => 'simple-in', 'price' => '15.00']);
    Product::factory()->active()->create(['url_handle' => 'simple-out', 'price' => '500.00']);
    variantProduct('variant-in', '18.00', '300.00');
    variantProduct('variant-out', '200.00', '900.00');

    $paginator = app(StorefrontProductListQuery::class)->execute(new ProductFilterInput(
        minPrice: '10',
        maxPrice: '20',
    ));

    expect($paginator->getCollection()->pluck('url_handle')->sort()->values()->all())
        ->toBe(['simple-in', 'variant-in']);
});

test('price high to low orders variant products by their lowest variant price', function () {
    Product::factory()->active()->create(['url_handle' => 'simple-cheap', 'price' => '10.00']);
    Product::factory()->active()->create(['url_handle' => 'simple-mid', 'price' => '50.00']);
    Product::factory()->active()->create(['url_handle' => 'simple-expensive', 'price' => '900.00']);
    variantProduct('variant-cheap', '5.00', '7.00');
    variantProduct('variant-mid', '60.00', '80.00');
    variantProduct('variant-expensive', '500.00', '1200.00');

    expect(sortedHandles(ProductSortOption::PriceHighLow))->toBe([
        'simple-expensive',
        'variant-expensive',
        'variant-mid',
        'simple-mid',
        'simple-cheap',
        'variant-cheap',
    ]);
});
