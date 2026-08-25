<?php

declare(strict_types=1);

use App\Actions\StoreCategoryAction;
use App\DTOs\StoreCategoryInput;
use App\Models\Category;

covers(StoreCategoryAction::class, StoreCategoryInput::class);

uses()->group('actions', 'category');

test('creates a category with a parent', function () {
    $parent = Category::factory()->create();

    $data = [
        'name' => 'Child Category',
        'url_handle' => 'child-category',
        'description' => 'Child description',
        'parent_id' => $parent->id,
        'is_active' => true,
    ];

    $action = new StoreCategoryAction();
    $result = $action->handle(StoreCategoryInput::fromArray($data));

    expect($result->name)->toBe('Child Category')
        ->and($result->parent_id)->toBe($parent->id)
        ->and(Category::query()->count())->toBe(2);
});

test('assigns sort_order 0 to first category under a parent', function () {
    $parent = Category::factory()->create();

    $action = new StoreCategoryAction();
    $result = $action->handle(StoreCategoryInput::fromArray([
        'name' => 'First Child',
        'url_handle' => 'first-child',
        'parent_id' => $parent->id,
        'is_active' => true,
    ]));

    expect($result->sort_order)->toBe(0);
});

test('assigns incrementing sort_order to sibling categories', function () {
    $parent = Category::factory()->create();

    $action = new StoreCategoryAction();

    $first = $action->handle(StoreCategoryInput::fromArray([
        'name' => 'First',
        'url_handle' => 'first',
        'parent_id' => $parent->id,
        'is_active' => true,
    ]));

    $second = $action->handle(StoreCategoryInput::fromArray([
        'name' => 'Second',
        'url_handle' => 'second',
        'parent_id' => $parent->id,
        'is_active' => true,
    ]));

    expect($first->sort_order)->toBe(0)
        ->and($second->sort_order)->toBe(1);
});

test('assigns sort_order independently per parent', function () {
    $parentA = Category::factory()->create();
    $parentB = Category::factory()->create();

    $action = new StoreCategoryAction();

    $action->handle(StoreCategoryInput::fromArray(['name' => 'A1', 'url_handle' => 'a1', 'parent_id' => $parentA->id, 'is_active' => true]));
    $action->handle(StoreCategoryInput::fromArray(['name' => 'A2', 'url_handle' => 'a2', 'parent_id' => $parentA->id, 'is_active' => true]));

    $b1 = $action->handle(StoreCategoryInput::fromArray(['name' => 'B1', 'url_handle' => 'b1', 'parent_id' => $parentB->id, 'is_active' => true]));

    expect($b1->sort_order)->toBe(0);
});
