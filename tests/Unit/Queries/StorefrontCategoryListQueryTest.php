<?php

declare(strict_types=1);

use App\Models\Category;
use App\Models\Product;
use App\Queries\StorefrontCategoryListQuery;

covers(StorefrontCategoryListQuery::class);

uses()->group('queries', 'storefront');

test('returns active top-level categories with their active children', function () {
    $electronics = Category::factory()->active()->create(['name' => ['en' => 'Electronics'], 'sort_order' => 0]);
    Category::factory()->active()->withParent($electronics)->create(['name' => ['en' => 'Audio']]);
    Category::factory()->inactive()->withParent($electronics)->create(['name' => ['en' => 'Hidden']]);
    Category::factory()->active()->create(['name' => ['en' => 'Fashion'], 'sort_order' => 1]);
    Category::factory()->inactive()->create(['name' => ['en' => 'Draft']]);

    $result = app(StorefrontCategoryListQuery::class)->execute();

    expect($result)->toHaveCount(2)
        ->and($result[0]['name'])->toBe(['en' => 'Electronics'])
        ->and($result[0]['children'])->toHaveCount(1)
        ->and($result[0]['children'][0]['name'])->toBe(['en' => 'Audio'])
        ->and($result[1]['name'])->toBe(['en' => 'Fashion']);
});

test('product count includes descendant categories and only active products', function () {
    $electronics = Category::factory()->active()->create();
    $audio = Category::factory()->active()->withParent($electronics)->create();

    Product::factory()->count(2)->create(['category_id' => $electronics->id, 'is_active' => true]);
    Product::factory()->count(3)->create(['category_id' => $audio->id, 'is_active' => true]);
    Product::factory()->create(['category_id' => $audio->id, 'is_active' => false]);

    $result = app(StorefrontCategoryListQuery::class)->execute();

    expect($result)->toHaveCount(1)
        ->and($result[0]['product_count'])->toBe(5);
});

test('orders top-level categories by sort order', function () {
    Category::factory()->active()->create(['name' => ['en' => 'Second'], 'sort_order' => 5]);
    Category::factory()->active()->create(['name' => ['en' => 'First'], 'sort_order' => 1]);

    $result = app(StorefrontCategoryListQuery::class)->execute();

    expect(array_column($result, 'name'))->toBe([['en' => 'First'], ['en' => 'Second']]);
});
