<?php

declare(strict_types=1);

namespace Tests\Unit\Filters\Criteria;

use App\Filters\Criteria\CategoryWithDescendantsCriterion;
use App\Models\Category;
use App\Models\Product;

covers(CategoryWithDescendantsCriterion::class);

uses()->group('filters');

test('filters products by category id', function () {
    $category = Category::factory()->create();
    $otherCategory = Category::factory()->create();

    Product::factory()->count(2)->create(['category_id' => $category->id]);
    Product::factory()->count(3)->create(['category_id' => $otherCategory->id]);

    $criterion = new CategoryWithDescendantsCriterion();
    $result = $criterion->apply(Product::query(), $category->id);

    expect($result->count())->toBe(2);
});

test('includes products from child categories', function () {
    $parent = Category::factory()->create();
    $child = Category::factory()->create(['parent_id' => $parent->id]);

    Product::factory()->count(2)->create(['category_id' => $parent->id]);
    Product::factory()->count(3)->create(['category_id' => $child->id]);
    Product::factory()->count(4)->create();

    $criterion = new CategoryWithDescendantsCriterion();
    $result = $criterion->apply(Product::query(), $parent->id);

    expect($result->count())->toBe(5);
});

test('includes products from deeply nested categories', function () {
    $grandparent = Category::factory()->create();
    $parent = Category::factory()->create(['parent_id' => $grandparent->id]);
    $child = Category::factory()->create(['parent_id' => $parent->id]);

    Product::factory()->create(['category_id' => $grandparent->id]);
    Product::factory()->create(['category_id' => $parent->id]);
    Product::factory()->create(['category_id' => $child->id]);
    Product::factory()->count(2)->create();

    $criterion = new CategoryWithDescendantsCriterion();
    $result = $criterion->apply(Product::query(), $grandparent->id);

    expect($result->count())->toBe(3);
});

test('returns no results for non-existent category', function () {
    Product::factory()->count(3)->create();

    $criterion = new CategoryWithDescendantsCriterion();
    $result = $criterion->apply(Product::query(), 99999);

    expect($result->count())->toBe(0);
});

test('handles string category id', function () {
    $category = Category::factory()->create();
    Product::factory()->count(2)->create(['category_id' => $category->id]);
    Product::factory()->create();

    $criterion = new CategoryWithDescendantsCriterion();
    $result = $criterion->apply(Product::query(), (string) $category->id);

    expect($result->count())->toBe(2);
});

test('canApply returns true for non-null non-empty values', function () {
    $criterion = new CategoryWithDescendantsCriterion();

    expect($criterion->canApply(1))->toBeTrue()
        ->and($criterion->canApply('1'))->toBeTrue()
        ->and($criterion->canApply(123))->toBeTrue();
});

test('canApply returns false for null or empty values', function () {
    $criterion = new CategoryWithDescendantsCriterion();

    expect($criterion->canApply(null))->toBeFalse()
        ->and($criterion->canApply(''))->toBeFalse();
});
