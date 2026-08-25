<?php

declare(strict_types=1);

use App\Enums\ReviewStatus;
use App\Models\Category;
use App\Models\Product;
use App\Models\Review;
use App\Queries\ProductDetailQuery;

covers(ProductDetailQuery::class);

uses()->group('storefront', 'product');

test('returns the buy-box payload plus detail-only keys', function () {
    $product = Product::factory()->available()->create();

    $data = app(ProductDetailQuery::class)->execute($product);

    expect($data)->toHaveKeys([
        'id', 'title', 'price', 'media', 'options', 'variants',
        'seo_title', 'seo_description', 'barcode', 'type',
        'category', 'rating_distribution',
    ])->and($data['id'])->toBe($product->id);
});

test('includes category ancestors ordered from root', function () {
    $root = Category::factory()->active()->create();
    $child = Category::factory()->active()->withParent($root)->create();
    $product = Product::factory()->available()->create(['category_id' => $child->id]);

    $data = app(ProductDetailQuery::class)->execute($product);

    expect($data['category']['ancestors'])->toHaveCount(1)
        ->and($data['category']['ancestors'][0]['url_handle'])->toBe($root->url_handle);
});

test('builds the rating distribution from approved reviews only', function () {
    $product = Product::factory()->available()->create();
    Review::factory()->for($product)->count(2)->create(['rating' => 5, 'status' => ReviewStatus::Approved]);
    Review::factory()->for($product)->create(['rating' => 3, 'status' => ReviewStatus::Approved]);
    Review::factory()->for($product)->create(['rating' => 5, 'status' => ReviewStatus::Pending]);

    $distribution = app(ProductDetailQuery::class)->execute($product)['rating_distribution'];

    expect($distribution[5])->toBe(2)
        ->and($distribution[3])->toBe(1)
        ->and($distribution[1])->toBe(0);
});

test('marks a digital product so the storefront can hide the quantity picker', function () {
    $product = Product::factory()->digital()->available()->create();

    expect(app(ProductDetailQuery::class)->execute($product)['type'])->toBe('digital');
});
