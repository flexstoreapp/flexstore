<?php

declare(strict_types=1);

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Queries\RelatedProductsQuery;

covers(RelatedProductsQuery::class);

uses()->group('storefront', 'product');

test('returns products from the same category excluding the current product', function () {
    $category = Category::factory()->active()->create();
    $product = Product::factory()->available()->create(['category_id' => $category->id]);
    Product::factory()->available()->count(3)->create(['category_id' => $category->id]);

    $related = app(RelatedProductsQuery::class)->execute($product, 12);

    expect($related)->toHaveCount(3)
        ->and(collect($related)->pluck('id'))->not->toContain($product->id);
});

test('respects the requested limit', function () {
    $category = Category::factory()->active()->create();
    $product = Product::factory()->available()->create(['category_id' => $category->id]);
    Product::factory()->available()->count(6)->create(['category_id' => $category->id]);

    expect(app(RelatedProductsQuery::class)->execute($product, 4))->toHaveCount(4);
});

test('falls back to the same brand when the product has no category', function () {
    $brand = Brand::factory()->create();
    $product = Product::factory()->available()->create(['category_id' => null, 'brand_id' => $brand->id]);
    Product::factory()->available()->count(2)->create(['category_id' => null, 'brand_id' => $brand->id]);

    $related = app(RelatedProductsQuery::class)->execute($product, 12);

    expect($related)->toHaveCount(2)
        ->and(collect($related)->pluck('id'))->not->toContain($product->id);
});

test('falls back to the latest products when there is no category or brand', function () {
    $product = Product::factory()->available()->create(['category_id' => null, 'brand_id' => null]);
    Product::factory()->available()->count(2)->create(['category_id' => null, 'brand_id' => null]);

    expect(app(RelatedProductsQuery::class)->execute($product, 12))->toHaveCount(2);
});

test('ranks same-category matches above brand-only matches', function () {
    $category = Category::factory()->active()->create();
    $otherCategory = Category::factory()->active()->create();
    $brand = Brand::factory()->create();

    $product = Product::factory()->available()->create([
        'category_id' => $category->id,
        'brand_id' => $brand->id,
        'price' => '100.00',
    ]);
    $categoryMatch = Product::factory()->available()->create(['category_id' => $category->id, 'price' => '100.00']);
    $brandMatch = Product::factory()->available()->create([
        'category_id' => $otherCategory->id,
        'brand_id' => $brand->id,
        'price' => '100.00',
    ]);

    $related = app(RelatedProductsQuery::class)->execute($product, 1);

    expect($related)->toHaveCount(1)
        ->and($related[0]['id'])->toBe($categoryMatch->id)
        ->and($related[0]['id'])->not->toBe($brandMatch->id);
});

test('boosts products within a similar price range', function () {
    $category = Category::factory()->active()->create();

    $product = Product::factory()->available()->create(['category_id' => $category->id, 'price' => '100.00']);
    $nearPrice = Product::factory()->available()->create(['category_id' => $category->id, 'price' => '110.00']);
    Product::factory()->available()->create(['category_id' => $category->id, 'price' => '300.00']);

    $related = app(RelatedProductsQuery::class)->execute($product, 1);

    expect($related)->toHaveCount(1)
        ->and($related[0]['id'])->toBe($nearPrice->id);
});

test('tops up with the latest products when relevant matches are scarce', function () {
    $category = Category::factory()->active()->create();

    $product = Product::factory()->available()->create(['category_id' => $category->id]);
    $categoryMatch = Product::factory()->available()->create(['category_id' => $category->id]);
    Product::factory()->available()->count(5)->create(['category_id' => null, 'brand_id' => null]);

    $related = app(RelatedProductsQuery::class)->execute($product, 5);

    expect($related)->toHaveCount(5)
        ->and(collect($related)->pluck('id'))->toContain($categoryMatch->id)
        ->and(collect($related)->pluck('id'))->not->toContain($product->id);
});

test('tops up from a broader ancestor category before falling back to unrelated products', function () {
    $parent = Category::factory()->active()->create();
    $categoryA = Category::factory()->active()->withParent($parent)->create();
    $categoryB = Category::factory()->active()->withParent($parent)->create();

    $product = Product::factory()->available()->create(['category_id' => $categoryA->id]);
    $directMatch = Product::factory()->available()->create(['category_id' => $categoryA->id]);
    $siblings = Product::factory()->available()->count(3)->create(['category_id' => $categoryB->id]);
    // Newest products, so a naive "newest overall" top-up would pick these.
    $unrelated = Product::factory()->available()->count(2)->create(['category_id' => null, 'brand_id' => null]);

    $related = app(RelatedProductsQuery::class)->execute($product, 4);
    $ids = collect($related)->pluck('id');

    expect($related)->toHaveCount(4)
        ->and($ids)->toContain($directMatch->id)
        ->and($ids)->toContain(...$siblings->pluck('id')->all())
        ->and($ids->intersect($unrelated->pluck('id'))->isEmpty())->toBeTrue();
});
