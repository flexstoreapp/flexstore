<?php

declare(strict_types=1);

namespace Tests\Unit\Filters;

use App\Filters\Configs\ProductFilterConfig;
use App\Filters\FilterManager;
use App\Models\Category;
use App\Models\Product;

covers(FilterManager::class);

uses()->group('filters');

test('FilterManager applies filters correctly', function () {
    $category = Category::factory()->create(['url_handle' => 'electronics']);
    Product::factory()->create([
        'title' => 'Test Product',
        'sku' => 'TEST-001',
        'price' => '100',
        'category_id' => $category->id,
        'is_active' => true,
        'in_stock' => true,
    ]);
    Product::factory()->create([
        'title' => 'Another Product',
        'sku' => 'ANOTHER-001',
        'price' => '200',
        'category_id' => $category->id,
        'is_active' => false,
        'in_stock' => false,
    ]);

    $filters = [
        'query' => 'test',
        'min_price' => '50.0000',
        'is_active' => 'true',
    ];

    $filterManager = (new FilterManager())->setCriteria(ProductFilterConfig::getCriteria());
    $result = $filterManager->apply(Product::query(), $filters);

    expect($result->count())->toBe(1)
        ->and($result->first()->title)->toBe('Test Product');
});

test('FilterManager ignores empty filter values', function () {
    Product::factory(3)->create();

    $filters = [
        'query' => '',
        'min_price' => null,
        'is_active' => '',
    ];

    $filterManager = (new FilterManager())->setCriteria(ProductFilterConfig::getCriteria());
    $result = $filterManager->apply(Product::query(), $filters);

    expect($result->count())->toBe(3);
});

test('FilterManager handles multiple filters simultaneously', function () {
    $category = Category::factory()->create(['url_handle' => 'electronics']);
    Product::factory()->create([
        'title' => 'Test Product',
        'price' => '100',
        'category_id' => $category->id,
        'is_active' => true,
        'in_stock' => true,
    ]);
    Product::factory()->create([
        'title' => 'Test Another',
        'price' => '150',
        'category_id' => $category->id,
        'is_active' => true,
        'in_stock' => false,
    ]);
    Product::factory()->create([
        'title' => 'Different Product',
        'price' => '200',
        'category_id' => $category->id,
        'is_active' => false,
        'in_stock' => true,
    ]);

    $filters = [
        'query' => 'test',
        'min_price' => '90.0000',
        'max_price' => '160.0000',
        'is_active' => 'true',
        'in_stock' => 'true',
    ];

    $filterManager = (new FilterManager())->setCriteria(ProductFilterConfig::getCriteria());
    $result = $filterManager->apply(Product::query(), $filters);

    expect($result->count())->toBe(1)
        ->and($result->first()->title)->toBe('Test Product');
});

test('FilterManager returns all results when no filters applied', function () {
    Product::factory()->count(5)->create();

    $filterManager = (new FilterManager())->setCriteria(ProductFilterConfig::getCriteria());
    $result = $filterManager->apply(Product::query(), []);

    expect($result->count())->toBe(5);
});

test('getFilterValue returns null when criteria does not have key', function () {
    $filterManager = (new FilterManager())->setCriteria(ProductFilterConfig::getCriteria());

    expect($filterManager->getFilterValue('non_existent_key', ['query' => 'test']))->toBeNull();
});

test('getFilterValue returns value when filter is present', function () {
    $filterManager = (new FilterManager())->setCriteria(ProductFilterConfig::getCriteria());

    expect($filterManager->getFilterValue('query', ['query' => 'test-product']))->toBe('test-product');
});

test('applies sort direction when both sort and direction are provided', function () {
    Product::factory()->create(['title' => 'Apple', 'price' => '100']);
    Product::factory()->create(['title' => 'Banana', 'price' => '200']);
    Product::factory()->create(['title' => 'Cherry', 'price' => '150']);

    $filters = ['sort' => 'price', 'direction' => 'desc'];

    $filterManager = (new FilterManager())->setCriteria(ProductFilterConfig::getCriteria($filters['direction']));
    $result = $filterManager->apply(Product::query(), $filters)->get();

    expect($result->pluck('price')->toArray())->toBe(['200.0000', '150.0000', '100.0000']);
});

test('ignores direction when sort is not present', function () {
    Product::factory()->create(['title' => 'Apple', 'price' => '100']);
    Product::factory()->create(['title' => 'Banana', 'price' => '200']);
    Product::factory()->create(['title' => 'Cherry', 'price' => '150']);

    $filters = ['direction' => 'desc'];

    $filterManager = (new FilterManager())->setCriteria(ProductFilterConfig::getCriteria($filters['direction']));
    $result = $filterManager->apply(Product::query(), $filters)->get();

    expect($result->count())->toBe(3);
});
