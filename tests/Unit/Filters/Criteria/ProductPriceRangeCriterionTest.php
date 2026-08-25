<?php

declare(strict_types=1);

use App\Filters\Criteria\ProductPriceRangeCriterion;
use App\Models\Product;
use App\Models\ProductVariant;

covers(ProductPriceRangeCriterion::class);

uses()->group('filters');

function variantPriced(string $title, string ...$prices): Product
{
    $product = Product::factory()->create(['title' => $title, 'price' => null]);

    foreach ($prices as $price) {
        ProductVariant::factory()->for($product)->create(['price' => $price]);
    }

    return $product;
}

test('simple products filter on their own price', function () {
    Product::factory()->create(['title' => 'Cheap', 'price' => '10.00']);
    Product::factory()->create(['title' => 'Expensive', 'price' => '90.00']);

    $result = (new ProductPriceRangeCriterion())->apply(Product::query(), ['min' => '5', 'max' => '20']);

    expect($result->pluck('title')->all())->toBe(['Cheap']);
});

test('a variant product matches when any variant price is in range', function () {
    variantPriced('Spread', '15.00', '80.00');
    variantPriced('Outside', '90.00', '120.00');

    $result = (new ProductPriceRangeCriterion())->apply(Product::query(), ['min' => '10', 'max' => '20']);

    expect($result->pluck('title')->all())->toBe(['Spread']);
});

test('a variant product with no variant inside the band is excluded even when the band is between its variants', function () {
    variantPriced('Gapped', '10.00', '100.00');

    $result = (new ProductPriceRangeCriterion())->apply(Product::query(), ['min' => '40', 'max' => '60']);

    expect($result->count())->toBe(0);
});

test('open-ended bounds work', function () {
    Product::factory()->create(['title' => 'Simple', 'price' => '50.00']);
    variantPriced('Varied', '5.00', '200.00');

    $criterion = new ProductPriceRangeCriterion();

    expect($criterion->apply(Product::query(), ['min' => '100', 'max' => null])->pluck('title')->all())->toBe(['Varied'])
        ->and($criterion->apply(Product::query(), ['min' => null, 'max' => '10'])->pluck('title')->all())->toBe(['Varied']);
});

test('canApply requires at least one numeric bound', function () {
    $criterion = new ProductPriceRangeCriterion();

    expect($criterion->canApply(['min' => '5', 'max' => null]))->toBeTrue()
        ->and($criterion->canApply(['min' => null, 'max' => null]))->toBeFalse()
        ->and($criterion->canApply(['min' => 'abc', 'max' => null]))->toBeFalse()
        ->and($criterion->canApply('10'))->toBeFalse();
});
