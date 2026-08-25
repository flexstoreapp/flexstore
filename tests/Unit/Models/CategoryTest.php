<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\Factory;
use Spatie\Translatable\HasTranslations;

covers(Category::class);

uses()->group('models', 'category');

test('has factory', function () {
    expect(Category::factory())->toBeInstanceOf(Factory::class);
});

test('uses HasTranslations trait', function () {
    expect(Category::class)->toUseTrait(HasTranslations::class);
});

test('has translatable fields', function () {
    $category = new Category();

    expect($category->getTranslatableAttributes())->toContain('name');
});

test('casts attributes correctly', function () {
    $category = new Category();
    $casts = $category->casts();

    expect($casts)
        ->toBeArray()
        ->toHaveKey('is_active', 'boolean');
});

test('has parent relationship', function () {
    $parent = Category::factory()->create();
    $category = Category::factory()->create(['parent_id' => $parent->id]);

    expect($category->parent)
        ->toBeInstanceOf(Category::class)
        ->and($category->parent->id)->toBe($parent->id);
});

test('root category has null parent', function () {
    $category = Category::factory()->create();

    expect($category->parent)->toBeNull();
});

test('has children relationship', function () {
    $parent = Category::factory()->create();
    $category = Category::factory()->create(['parent_id' => $parent->id]);

    expect($parent->children)
        ->toBeInstanceOf(Collection::class)
        ->and($parent->children->first())->toBeInstanceOf(Category::class)
        ->and($parent->children->first()->id)->toBe($category->id);
});

test('children are ordered by sort_order', function () {
    $parent = Category::factory()->create();
    $child2 = Category::factory()->create(['parent_id' => $parent->id, 'sort_order' => 1]);
    $child1 = Category::factory()->create(['parent_id' => $parent->id, 'sort_order' => 0]);

    $children = $parent->children;

    expect($children->first()->id)->toBe($child1->id)
        ->and($children->last()->id)->toBe($child2->id);
});

test('ancestors returns empty collection for root category', function () {
    $category = Category::factory()->create();

    $ancestors = $category->ancestors();

    expect($ancestors)
        ->toBeInstanceOf(Collection::class)
        ->toHaveCount(0);
});

test('ancestors returns parent chain ordered root to immediate parent', function () {
    $grandParent = Category::factory()->create();
    $parent = Category::factory()->create(['parent_id' => $grandParent->id]);
    $child = Category::factory()->create(['parent_id' => $parent->id]);

    $ancestors = $child->ancestors();

    expect($ancestors)
        ->toHaveCount(2)
        ->toContainOnlyInstancesOf(Category::class)
        ->and($ancestors->first()->id)->toBe($grandParent->id)
        ->and($ancestors->last()->id)->toBe($parent->id);
});

test('withDescendants scope returns self and all descendants', function () {
    $grandParent = Category::factory()->create();
    $parent = Category::factory()->create(['parent_id' => $grandParent->id]);
    $child = Category::factory()->create(['parent_id' => $parent->id]);

    $ids = Category::query()->withDescendants($grandParent->id)->pluck('id')->all();

    expect($ids)->toContain($grandParent->id, $parent->id, $child->id)
        ->toHaveCount(3);
});

test('withDescendants scope returns only self for leaf category', function () {
    $category = Category::factory()->create();

    $ids = Category::query()->withDescendants($category->id)->pluck('id')->all();

    expect($ids)->toBe([$category->id]);
});

test('withDescendants scope handles deep nesting', function () {
    $level1 = Category::factory()->create();
    $level2 = Category::factory()->create(['parent_id' => $level1->id]);
    $level3 = Category::factory()->create(['parent_id' => $level2->id]);
    $level4 = Category::factory()->create(['parent_id' => $level3->id]);

    $ids = Category::query()->withDescendants($level1->id)->pluck('id')->all();

    expect($ids)->toContain($level1->id, $level2->id, $level3->id, $level4->id)
        ->toHaveCount(4);
});

test('withAncestors scope returns self and all ancestors', function () {
    $grandParent = Category::factory()->create();
    $parent = Category::factory()->create(['parent_id' => $grandParent->id]);
    $child = Category::factory()->create(['parent_id' => $parent->id]);

    $ids = Category::query()->withAncestors($child->id)->pluck('id')->all();

    expect($ids)->toContain($grandParent->id, $parent->id, $child->id)
        ->toHaveCount(3);
});

test('withAncestors scope returns only self for root category', function () {
    $category = Category::factory()->create();

    $ids = Category::query()->withAncestors($category->id)->pluck('id')->all();

    expect($ids)->toBe([$category->id]);
});

test('isAncestorOf returns true when category is ancestor', function () {
    $grandParent = Category::factory()->create();
    $parent = Category::factory()->create(['parent_id' => $grandParent->id]);
    $child = Category::factory()->create(['parent_id' => $parent->id]);

    expect($grandParent->isAncestorOf($child))->toBeTrue()
        ->and($grandParent->isAncestorOf($parent))->toBeTrue()
        ->and($parent->isAncestorOf($child))->toBeTrue();
});

test('isAncestorOf returns false for self', function () {
    $category = Category::factory()->create();

    expect($category->isAncestorOf($category))->toBeFalse();
});

test('isAncestorOf returns false for unrelated categories', function () {
    $categoryA = Category::factory()->create();
    $categoryB = Category::factory()->create();

    expect($categoryA->isAncestorOf($categoryB))->toBeFalse();
});

test('isAncestorOf returns false for descendants', function () {
    $parent = Category::factory()->create();
    $child = Category::factory()->create(['parent_id' => $parent->id]);

    expect($child->isAncestorOf($parent))->toBeFalse();
});

test('has products relationship', function () {
    $category = Category::factory()->create();
    $product = Product::factory()->create([
        'category_id' => $category->id,
    ]);

    expect($category->products)
        ->toBeInstanceOf(Collection::class)
        ->and($category->products->first())->toBeInstanceOf(Product::class)
        ->and($category->products->first()->id)->toBe($product->id);
});
