<?php

declare(strict_types=1);

namespace Tests\Unit\Filters\Criteria;

use App\Filters\Criteria\MultipleCategoriesWithDescendantsCriterion;
use App\Models\Category;
use App\Models\Product;

covers(MultipleCategoriesWithDescendantsCriterion::class);

uses()->group('filters');

test('filters products by multiple category ids', function () {
    $electronics = Category::factory()->create();
    $fashion = Category::factory()->create();
    $other = Category::factory()->create();

    Product::factory()->create(['category_id' => $electronics->id]);
    Product::factory()->create(['category_id' => $fashion->id]);
    Product::factory()->create(['category_id' => $other->id]);

    $criterion = new MultipleCategoriesWithDescendantsCriterion();
    $result = $criterion->apply(Product::query(), "{$electronics->id},{$fashion->id}");

    expect($result->count())->toBe(2);
});

test('includes products from descendants of each selected category', function () {
    $electronics = Category::factory()->create();
    $phones = Category::factory()->create(['parent_id' => $electronics->id]);
    $fashion = Category::factory()->create();

    Product::factory()->create(['category_id' => $phones->id]);
    Product::factory()->create(['category_id' => $fashion->id]);
    Product::factory()->create();

    $criterion = new MultipleCategoriesWithDescendantsCriterion();
    $result = $criterion->apply(Product::query(), "{$electronics->id},{$fashion->id}");

    expect($result->count())->toBe(2);
});

test('accepts an array of ids', function () {
    $one = Category::factory()->create();
    $two = Category::factory()->create();

    Product::factory()->create(['category_id' => $one->id]);
    Product::factory()->create(['category_id' => $two->id]);
    Product::factory()->create();

    $criterion = new MultipleCategoriesWithDescendantsCriterion();
    $result = $criterion->apply(Product::query(), [$one->id, $two->id]);

    expect($result->count())->toBe(2);
});

test('does not filter when no ids are given', function () {
    Product::factory()->count(3)->create();

    $criterion = new MultipleCategoriesWithDescendantsCriterion();
    $result = $criterion->apply(Product::query(), '');

    expect($result->count())->toBe(3);
});

test('returns no results for non-existent categories', function () {
    Product::factory()->count(2)->create();

    $criterion = new MultipleCategoriesWithDescendantsCriterion();
    $result = $criterion->apply(Product::query(), '99999');

    expect($result->count())->toBe(0);
});

test('canApply returns true for non-empty values', function () {
    $criterion = new MultipleCategoriesWithDescendantsCriterion();

    expect($criterion->canApply('1,2'))->toBeTrue()
        ->and($criterion->canApply([1, 2]))->toBeTrue();
});

test('canApply returns false for null or empty values', function () {
    $criterion = new MultipleCategoriesWithDescendantsCriterion();

    expect($criterion->canApply(null))->toBeFalse()
        ->and($criterion->canApply(''))->toBeFalse();
});
