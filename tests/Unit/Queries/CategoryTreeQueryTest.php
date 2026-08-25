<?php

declare(strict_types=1);

use App\Models\Category;
use App\Queries\CategoryTreeQuery;

covers(CategoryTreeQuery::class);

uses()->group('queries', 'category');

test('returns categories as tree', function () {
    $parent = Category::factory()->create();
    $child = Category::factory()->create(['parent_id' => $parent->id]);

    $query = new CategoryTreeQuery();
    $result = $query->execute();

    $parentNode = $result->firstWhere('id', $parent->id);

    expect($parentNode)->not->toBeNull()
        ->and($parentNode->children)->toHaveCount(1)
        ->and($parentNode->children->first()->id)->toBe($child->id);
});

test('returns root categories ordered by sort_order', function () {
    $c3 = Category::factory()->create(['sort_order' => 2]);
    $c1 = Category::factory()->create(['sort_order' => 0]);
    $c2 = Category::factory()->create(['sort_order' => 1]);

    $query = new CategoryTreeQuery();
    $result = $query->execute();

    expect($result->pluck('id')->all())->toBe([$c1->id, $c2->id, $c3->id]);
});

test('returns children ordered by sort_order within parent', function () {
    $parent = Category::factory()->create();
    $child3 = Category::factory()->create(['parent_id' => $parent->id, 'sort_order' => 2]);
    $child1 = Category::factory()->create(['parent_id' => $parent->id, 'sort_order' => 0]);
    $child2 = Category::factory()->create(['parent_id' => $parent->id, 'sort_order' => 1]);

    $query = new CategoryTreeQuery();
    $result = $query->execute();

    $parentNode = $result->firstWhere('id', $parent->id);

    expect($parentNode->children->pluck('id')->all())->toBe([$child1->id, $child2->id, $child3->id]);
});

test('builds three-level deep tree correctly', function () {
    $root = Category::factory()->create();
    $child = Category::factory()->create(['parent_id' => $root->id]);
    $grandchild = Category::factory()->create(['parent_id' => $child->id]);

    $query = new CategoryTreeQuery();
    $result = $query->execute();

    expect($result)->toHaveCount(1);

    $rootNode = $result->first();
    expect($rootNode->id)->toBe($root->id)
        ->and($rootNode->children)->toHaveCount(1);

    $childNode = $rootNode->children->first();
    expect($childNode->id)->toBe($child->id)
        ->and($childNode->children)->toHaveCount(1)
        ->and($childNode->children->first()->id)->toBe($grandchild->id);
});

test('returns empty collection when no categories exist', function () {
    $query = new CategoryTreeQuery();
    $result = $query->execute();

    expect($result)->toBeEmpty();
});
