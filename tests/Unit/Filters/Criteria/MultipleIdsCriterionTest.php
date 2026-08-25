<?php

declare(strict_types=1);

namespace Tests\Unit\Filters\Criteria;

use App\Filters\Criteria\MultipleIdsCriterion;
use App\Models\Brand;
use App\Models\Product;

covers(MultipleIdsCriterion::class);

uses()->group('filters');

test('filters by single id from comma-separated string', function () {
    $brand = Brand::factory()->create();
    Product::factory()->count(2)->create(['brand_id' => $brand->id]);
    Product::factory()->count(3)->create();

    $criterion = new MultipleIdsCriterion('brand_id');
    $result = $criterion->apply(Product::query(), (string) $brand->id);

    expect($result->count())->toBe(2);
});

test('filters by multiple ids from comma-separated string', function () {
    $brand1 = Brand::factory()->create();
    $brand2 = Brand::factory()->create();
    Product::factory()->count(2)->create(['brand_id' => $brand1->id]);
    Product::factory()->count(3)->create(['brand_id' => $brand2->id]);
    Product::factory()->count(4)->create();

    $criterion = new MultipleIdsCriterion('brand_id');
    $result = $criterion->apply(Product::query(), "{$brand1->id},{$brand2->id}");

    expect($result->count())->toBe(5);
});

test('filters by array of ids', function () {
    $brand1 = Brand::factory()->create();
    $brand2 = Brand::factory()->create();
    Product::factory()->count(2)->create(['brand_id' => $brand1->id]);
    Product::factory()->count(3)->create(['brand_id' => $brand2->id]);
    Product::factory()->count(4)->create();

    $criterion = new MultipleIdsCriterion('brand_id');
    $result = $criterion->apply(Product::query(), [$brand1->id, $brand2->id]);

    expect($result->count())->toBe(5);
});

test('uses where for single id optimization', function () {
    $brand = Brand::factory()->create();
    Product::factory()->create(['brand_id' => $brand->id]);

    $criterion = new MultipleIdsCriterion('brand_id');
    $builder = Product::query();
    $criterion->apply($builder, (string) $brand->id);

    $sql = $builder->toSql();
    expect($sql)->toContain('where')
        ->and($sql)->not->toContain('in');
});

test('uses whereIn for multiple ids', function () {
    $brand1 = Brand::factory()->create();
    $brand2 = Brand::factory()->create();

    $criterion = new MultipleIdsCriterion('brand_id');
    $builder = Product::query();
    $criterion->apply($builder, "{$brand1->id},{$brand2->id}");

    $sql = $builder->toSql();
    expect($sql)->toContain('in');
});

test('returns unmodified builder for empty array', function () {
    Product::factory()->count(3)->create();

    $criterion = new MultipleIdsCriterion('brand_id');
    $result = $criterion->apply(Product::query(), []);

    expect($result->count())->toBe(3);
});

test('returns unmodified builder for empty string after parsing', function () {
    Product::factory()->count(3)->create();

    $criterion = new MultipleIdsCriterion('brand_id');
    $result = $criterion->apply(Product::query(), ',,,');

    expect($result->count())->toBe(3);
});

test('handles whitespace in comma-separated values', function () {
    $brand1 = Brand::factory()->create();
    $brand2 = Brand::factory()->create();
    Product::factory()->create(['brand_id' => $brand1->id]);
    Product::factory()->create(['brand_id' => $brand2->id]);
    Product::factory()->create();

    $criterion = new MultipleIdsCriterion('brand_id');
    $result = $criterion->apply(Product::query(), " {$brand1->id} , {$brand2->id} ");

    expect($result->count())->toBe(2);
});

test('canApply returns true for non-null non-empty values', function () {
    $criterion = new MultipleIdsCriterion('brand_id');

    expect($criterion->canApply('1'))->toBeTrue()
        ->and($criterion->canApply('1,2,3'))->toBeTrue()
        ->and($criterion->canApply([1, 2, 3]))->toBeTrue()
        ->and($criterion->canApply(1))->toBeTrue();
});

test('canApply returns false for null or empty values', function () {
    $criterion = new MultipleIdsCriterion('brand_id');

    expect($criterion->canApply(null))->toBeFalse()
        ->and($criterion->canApply(''))->toBeFalse();
});
