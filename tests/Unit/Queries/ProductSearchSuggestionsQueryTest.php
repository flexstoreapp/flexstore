<?php

declare(strict_types=1);

use App\Models\Product;
use App\Queries\ProductSearchSuggestionsQuery;

covers(ProductSearchSuggestionsQuery::class);

uses()->group('queries', 'search');

test('returns empty result for empty query string', function () {
    $query = app(ProductSearchSuggestionsQuery::class);
    $result = $query->execute('');

    expect($result['suggestions']->isEmpty())->toBeTrue()
        ->and($result['total'])->toBe(0)
        ->and($result['has_more'])->toBeFalse()
        ->and($result['query'])->toBe('');
});

test('returns empty result for whitespace-only query', function () {
    $query = app(ProductSearchSuggestionsQuery::class);
    $result = $query->execute('   ');

    expect($result['suggestions']->isEmpty())->toBeTrue()
        ->and($result['query'])->toBe('');
});

test('trims query string', function () {
    Product::factory()->active()->create([
        'title' => 'Test Product',
    ]);

    $query = app(ProductSearchSuggestionsQuery::class);
    $result = $query->execute('  test  ');

    expect($result['query'])->toBe('test');
});

test('limits to 5 suggestions', function () {
    Product::factory()->count(8)->active()->create([
        'title' => 'Test Product',
    ]);

    $query = app(ProductSearchSuggestionsQuery::class);
    $result = $query->execute('test');

    expect($result['suggestions'])->toHaveCount(5)
        ->and($result['total'])->toBe(8)
        ->and($result['has_more'])->toBeTrue();
});

test('has_more is false when total is 5 or less', function () {
    Product::factory()->count(5)->active()->create([
        'title' => 'Test Product',
    ]);

    $query = app(ProductSearchSuggestionsQuery::class);
    $result = $query->execute('test');

    expect($result['suggestions'])->toHaveCount(5)
        ->and($result['total'])->toBe(5)
        ->and($result['has_more'])->toBeFalse();
});

test('returns null category for product without a category', function () {
    Product::factory()->active()->create([
        'title' => 'Test Product',
        'category_id' => null,
    ]);

    $query = app(ProductSearchSuggestionsQuery::class);
    $result = $query->execute('test');

    expect($result['suggestions']->first()['category'])->toBeNull();
});

test('returns price range for product with variants', function () {
    $product = Product::factory()->active()->create([
        'title' => 'Test Product',
        'price' => null,
    ]);

    $product->variants()->createMany([
        ['title' => ['en' => 'Small'], 'price' => 19.99, 'in_stock' => true, 'track_stock' => true],
        ['title' => ['en' => 'Large'], 'price' => 49.99, 'in_stock' => true, 'track_stock' => true],
    ]);

    $query = app(ProductSearchSuggestionsQuery::class);
    $result = $query->execute('test');

    expect($result['suggestions']->first()['price_range'])
        ->toBe(['19.9900', '49.9900']);
});

test('exposes the variant compare at price range so a variant product can show its discount', function () {
    $product = Product::factory()->active()->create([
        'title' => 'Test Product',
        'price' => null,
        'compare_at_price' => null,
    ]);

    $product->variants()->createMany([
        ['title' => ['en' => 'Small'], 'price' => 184.99, 'compare_at_price' => 230.99, 'in_stock' => true, 'track_stock' => true],
        ['title' => ['en' => 'Large'], 'price' => 184.99, 'compare_at_price' => 230.99, 'in_stock' => true, 'track_stock' => true],
    ]);

    $query = app(ProductSearchSuggestionsQuery::class);
    $suggestion = $query->execute('test')['suggestions']->first();

    expect($suggestion['price_range'])->toBe(['184.9900', '184.9900'])
        ->and($suggestion['compare_at_price_range'])->toBe(['230.9900', '230.9900']);
});

test('only returns active products', function () {
    Product::factory()->active()->create([
        'title' => 'Active Product',
    ]);

    Product::factory()->inactive()->create([
        'title' => 'Inactive Product',
    ]);

    $query = app(ProductSearchSuggestionsQuery::class);
    $result = $query->execute('product');

    expect($result['suggestions'])->toHaveCount(1)
        ->and($result['suggestions']->first()['title']['en'])->toBe('Active Product');
});

test('suggestions are ranked by relevance, not insertion order', function () {
    $contains = Product::factory()->active()->create(['title' => 'Blue Cotton Shirt']);
    $exact = Product::factory()->active()->create(['title' => 'Shirt']);
    $prefix = Product::factory()->active()->create(['title' => 'Shirt Dress']);

    $result = app(ProductSearchSuggestionsQuery::class)->execute('shirt');

    expect($result['suggestions']->pluck('id')->all())->toBe([$exact->id, $prefix->id, $contains->id]);
});

test('multi-word query finds products with non-contiguous words', function () {
    Product::factory()->active()->create(['title' => 'Cotton Premium Shirt']);
    Product::factory()->active()->create(['title' => 'Linen Shirt']);

    $result = app(ProductSearchSuggestionsQuery::class)->execute('cotton shirt');

    expect($result['total'])->toBe(1)
        ->and($result['suggestions']->first()['title']['en'])->toBe('Cotton Premium Shirt');
});
