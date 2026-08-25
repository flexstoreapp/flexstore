<?php

declare(strict_types=1);

use App\Models\Brand;
use App\Models\Product;
use App\Queries\BrandListQuery;
use Illuminate\Pagination\LengthAwarePaginator;

covers(BrandListQuery::class);

uses()->group('queries', 'brand');

test('returns paginated brands', function () {
    Brand::factory()->count(20)->create();

    $query = app(BrandListQuery::class);
    $result = $query->execute(perPage: 15);

    expect($result)->toBeInstanceOf(LengthAwarePaginator::class)
        ->and($result)->toHaveCount(15)
        ->and($result->total())->toBe(20);
});

test('returns empty paginator when no brands exist', function () {
    $query = app(BrandListQuery::class);
    $result = $query->execute();

    expect($result)->toBeEmpty();
});

test('includes products count', function () {
    $brand = Brand::factory()->create();
    Product::factory()->count(3)->create(['brand_id' => $brand->id]);

    $query = app(BrandListQuery::class);
    $result = $query->execute();

    expect($result->first()->products_count)->toBe(3);
});

test('returns zero products count when brand has no products', function () {
    Brand::factory()->create();

    $query = app(BrandListQuery::class);
    $result = $query->execute();

    expect($result->first()->products_count)->toBe(0);
});

test('respects per page parameter', function () {
    Brand::factory()->count(10)->create();

    $query = app(BrandListQuery::class);
    $result = $query->execute(perPage: 5);

    expect($result)->toHaveCount(5)
        ->and($result->total())->toBe(10);
});
