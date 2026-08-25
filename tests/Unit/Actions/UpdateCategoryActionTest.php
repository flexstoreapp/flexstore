<?php

declare(strict_types=1);

use App\Actions\UpdateCategoryAction;
use App\DTOs\UpdateCategoryInput;
use App\Exceptions\CategoryHierarchyException;
use App\Models\Category;

covers([
    UpdateCategoryAction::class,
    CategoryHierarchyException::class,
    UpdateCategoryInput::class,
]);

uses()->group('actions', 'category');

test('updates a category with provided fields', function () {
    $category = Category::factory()->create();

    $data = [
        'name' => 'Updated Category',
        'url_handle' => 'updated-category',
        'description' => 'Updated description',
        'parent_id' => null,
        'is_active' => false,
    ];

    $action = new UpdateCategoryAction();
    $result = $action->handle($category, UpdateCategoryInput::fromArray($data));

    expect($result)->toBeInstanceOf(Category::class)
        ->and($result->name)->toBe('Updated Category')
        ->and($result->url_handle)->toBe('updated-category')
        ->and($result->description)->toBe('Updated description')
        ->and($result->parent_id)->toBeNull()
        ->and($result->is_active)->toBeFalse();

    $category->refresh();
    expect($category->name)->toBe('Updated Category')
        ->and($category->is_active)->toBeFalse();
});

test('updates a category with only specified fields', function () {
    $category = Category::factory()->create();

    $data = [
        'name' => 'Updated Category',
        'url_handle' => 'updated-category',
    ];

    $action = new UpdateCategoryAction();
    $result = $action->handle($category, UpdateCategoryInput::fromArray($data));

    expect($result->name)->toBe('Updated Category')
        ->and($result->url_handle)->toBe('updated-category')
        ->and($result->description)->toBe($category->description)
        ->and($result->is_active)->toBe($category->is_active);

    $category->refresh();
    expect($category->name)->toBe('Updated Category')
        ->and($category->description)->toBe($category->description);
});

test('updates a category with parent relationship', function () {
    $parent = Category::factory()->create();
    $category = Category::factory()->create();

    $data = [
        'name' => 'Child Category',
        'parent_id' => $parent->id,
    ];

    $action = new UpdateCategoryAction();
    $result = $action->handle($category, UpdateCategoryInput::fromArray($data));

    expect($result->name)->toBe('Child Category')
        ->and($result->parent_id)->toBe($parent->id);

    $category->refresh();
    expect($category->parent_id)->toBe($parent->id);
});

test('updates category with same parent_id unchanged', function () {
    $parent = Category::factory()->create();
    $category = Category::factory()->create();
    $category->update(['parent_id' => $parent->id, 'sort_order' => 0]);

    $data = [
        'name' => 'Updated Name',
        'parent_id' => $parent->id,
    ];

    $action = new UpdateCategoryAction();
    $result = $action->handle($category, UpdateCategoryInput::fromArray($data));

    expect($result->name)->toBe('Updated Name')
        ->and($result->parent_id)->toBe($parent->id);

    $category->refresh();
    expect($category->parent_id)->toBe($parent->id);
});

test('recalculates sort_order when parent changes', function () {
    $parentA = Category::factory()->create();
    $parentB = Category::factory()->create();

    $existingChild = Category::factory()->create();
    $existingChild->update(['parent_id' => $parentB->id, 'sort_order' => 0]);

    $category = Category::factory()->create();
    $category->update(['parent_id' => $parentA->id, 'sort_order' => 0]);

    $action = new UpdateCategoryAction();
    $result = $action->handle($category, UpdateCategoryInput::fromArray(['parent_id' => $parentB->id]));

    expect($result->parent_id)->toBe($parentB->id)
        ->and($result->sort_order)->toBe(1);
});

test('recalculates sort_order when moving to root', function () {
    $parent = Category::factory()->create(['sort_order' => 0]);
    Category::factory()->create(['sort_order' => 1]);

    $category = Category::factory()->create();
    $category->update(['parent_id' => $parent->id, 'sort_order' => 0]);

    $action = new UpdateCategoryAction();
    $result = $action->handle($category, UpdateCategoryInput::fromArray(['parent_id' => null]));

    expect($result->parent_id)->toBeNull()
        ->and($result->sort_order)->toBe(2);
});

test('does not change sort_order when parent_id is not in data', function () {
    $parent = Category::factory()->create();
    $category = Category::factory()->create();
    $category->update(['parent_id' => $parent->id, 'sort_order' => 5]);

    $action = new UpdateCategoryAction();
    $action->handle($category, UpdateCategoryInput::fromArray(['name' => 'New Name']));

    $category->refresh();
    expect($category->sort_order)->toBe(5);
});

test('prevents moving category to its own descendant', function () {
    $parent = Category::factory()->create();
    $child = Category::factory()->create();
    $child->update(['parent_id' => $parent->id, 'sort_order' => 0]);

    $grandchild = Category::factory()->create();
    $grandchild->update(['parent_id' => $child->id, 'sort_order' => 0]);

    $data = [
        'name' => 'Parent Category',
        'parent_id' => $grandchild->id,
    ];

    $action = new UpdateCategoryAction();

    expect(fn () => $action->handle($parent, UpdateCategoryInput::fromArray($data)))
        ->toThrow(CategoryHierarchyException::class);

    $parent->refresh();
    expect($parent->parent_id)->toBeNull();
});
