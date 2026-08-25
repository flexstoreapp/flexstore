<?php

declare(strict_types=1);

use App\Actions\ReorderCategoryAction;
use App\Exceptions\CategoryHierarchyException;
use App\Models\Category;

covers(ReorderCategoryAction::class);

uses()->group('actions', 'category');

test('moves category to new parent at first position', function () {
    $oldParent = Category::factory()->create(['name' => 'Old Parent']);
    $newParent = Category::factory()->create(['name' => 'New Parent']);

    $category = Category::factory()->create(['name' => 'Category to Move']);
    $category->update(['parent_id' => $oldParent->id, 'sort_order' => 0]);

    $existingChild1 = Category::factory()->create(['name' => 'Existing Child 1']);
    $existingChild1->update(['parent_id' => $newParent->id, 'sort_order' => 0]);
    $existingChild2 = Category::factory()->create(['name' => 'Existing Child 2']);
    $existingChild2->update(['parent_id' => $newParent->id, 'sort_order' => 1]);

    $action = new ReorderCategoryAction();
    $result = $action->handle($category, $newParent->id, 0);

    expect($result)->toBeInstanceOf(Category::class)
        ->and($result->parent_id)->toBe($newParent->id);

    $category->refresh();
    expect($category->parent_id)->toBe($newParent->id);

    $newParent->refresh();
    $children = $newParent->children()->orderBy('sort_order')->get();
    expect($children)->toHaveCount(3)
        ->and($children->first()->id)->toBe($category->id);
});

test('moves category to new parent at last position', function () {
    $oldParent = Category::factory()->create(['name' => 'Old Parent']);
    $newParent = Category::factory()->create(['name' => 'New Parent']);

    $category = Category::factory()->create(['name' => 'Category to Move']);
    $category->update(['parent_id' => $oldParent->id, 'sort_order' => 0]);

    $existingChild1 = Category::factory()->create(['name' => 'Existing Child 1']);
    $existingChild1->update(['parent_id' => $newParent->id, 'sort_order' => 0]);
    $existingChild2 = Category::factory()->create(['name' => 'Existing Child 2']);
    $existingChild2->update(['parent_id' => $newParent->id, 'sort_order' => 1]);

    $action = new ReorderCategoryAction();
    $result = $action->handle($category, $newParent->id, 2);

    expect($result)->toBeInstanceOf(Category::class)
        ->and($result->parent_id)->toBe($newParent->id);

    $category->refresh();
    expect($category->parent_id)->toBe($newParent->id);

    $newParent->refresh();
    $children = $newParent->children()->orderBy('sort_order')->get();
    expect($children)->toHaveCount(3)
        ->and($children->last()->id)->toBe($category->id);
});

test('moves category to root level', function () {
    $parent = Category::factory()->create(['name' => 'Parent']);

    $category = Category::factory()->create(['name' => 'Category to Move']);
    $category->update(['parent_id' => $parent->id, 'sort_order' => 0]);

    $rootCategory1 = Category::factory()->create(['name' => 'Root 1']);
    $rootCategory2 = Category::factory()->create(['name' => 'Root 2']);

    expect($category->parent_id)->toBe($parent->id);

    $action = new ReorderCategoryAction();
    $result = $action->handle($category, null, 0);

    expect($result)->toBeInstanceOf(Category::class)
        ->and($result->parent_id)->toBeNull();

    $category->refresh();
    expect($category->parent_id)->toBeNull();

    $rootCategories = Category::whereNull('parent_id')->orderBy('sort_order')->get();
    expect($rootCategories)->toHaveCount(4)
        ->and($rootCategories->pluck('id')->toArray())->toContain($category->id);
});

test('moves category from root to parent', function () {
    $category = Category::factory()->create(['name' => 'Root Category']);

    $parent = Category::factory()->create(['name' => 'Parent']);

    expect($category->parent_id)->toBeNull();

    $action = new ReorderCategoryAction();
    $result = $action->handle($category, $parent->id, 0);

    expect($result)->toBeInstanceOf(Category::class)
        ->and($result->parent_id)->toBe($parent->id);

    $category->refresh();
    expect($category->parent_id)->toBe($parent->id);
});

test('reorders category within same parent moving down', function () {
    $parent = Category::factory()->create(['name' => 'Parent']);

    $child1 = Category::factory()->create(['name' => 'Child 1']);
    $child1->update(['parent_id' => $parent->id, 'sort_order' => 0]);

    $child2 = Category::factory()->create(['name' => 'Child 2']);
    $child2->update(['parent_id' => $parent->id, 'sort_order' => 1]);

    $child3 = Category::factory()->create(['name' => 'Child 3']);
    $child3->update(['parent_id' => $parent->id, 'sort_order' => 2]);

    $child4 = Category::factory()->create(['name' => 'Child 4']);
    $child4->update(['parent_id' => $parent->id, 'sort_order' => 3]);

    $action = new ReorderCategoryAction();
    $result = $action->handle($child1, $parent->id, 2);

    expect($result)->toBeInstanceOf(Category::class);

    $parent->refresh();
    $children = $parent->children()->orderBy('sort_order')->get();
    expect($children)->toHaveCount(4)
        ->and($children->pluck('id')->toArray())->toBe([
            $child2->id,
            $child3->id,
            $child1->id,
            $child4->id,
        ]);
});

test('reorders category within same parent moving up', function () {
    $parent = Category::factory()->create(['name' => 'Parent']);

    $child1 = Category::factory()->create(['name' => 'Child 1']);
    $child1->update(['parent_id' => $parent->id, 'sort_order' => 0]);

    $child2 = Category::factory()->create(['name' => 'Child 2']);
    $child2->update(['parent_id' => $parent->id, 'sort_order' => 1]);

    $child3 = Category::factory()->create(['name' => 'Child 3']);
    $child3->update(['parent_id' => $parent->id, 'sort_order' => 2]);

    $child4 = Category::factory()->create(['name' => 'Child 4']);
    $child4->update(['parent_id' => $parent->id, 'sort_order' => 3]);

    $action = new ReorderCategoryAction();
    $result = $action->handle($child4, $parent->id, 1);

    expect($result)->toBeInstanceOf(Category::class);

    $parent->refresh();
    $children = $parent->children()->orderBy('sort_order')->get();
    expect($children)->toHaveCount(4)
        ->and($children->pluck('id')->toArray())->toBe([
            $child1->id,
            $child4->id,
            $child2->id,
            $child3->id,
        ]);
});

test('clamps position to last when exceeding sibling count', function () {
    $parent = Category::factory()->create(['name' => 'Parent']);

    $child1 = Category::factory()->create(['name' => 'Child 1']);
    $child1->update(['parent_id' => $parent->id, 'sort_order' => 0]);

    $child2 = Category::factory()->create(['name' => 'Child 2']);
    $child2->update(['parent_id' => $parent->id, 'sort_order' => 1]);

    $action = new ReorderCategoryAction();
    $result = $action->handle($child1, $parent->id, 999);

    $children = $parent->children()->orderBy('sort_order')->get();
    expect($children->pluck('id')->toArray())->toBe([
        $child2->id,
        $child1->id,
    ]);
});

test('reorders root categories', function () {
    $root1 = Category::factory()->create(['name' => 'Root 1']);
    $root2 = Category::factory()->create(['name' => 'Root 2']);
    $root3 = Category::factory()->create(['name' => 'Root 3']);

    $action = new ReorderCategoryAction();
    $result = $action->handle($root3, null, 0);

    expect($result)->toBeInstanceOf(Category::class)
        ->and($result->parent_id)->toBeNull();

    $rootCategories = Category::whereNull('parent_id')->orderBy('sort_order')->get();
    expect($rootCategories)->toHaveCount(3)
        ->and($rootCategories->pluck('id')->toArray())->toBe([
            $root3->id,
            $root1->id,
            $root2->id,
        ]);
});

test('maintains tree integrity after moving category with children', function () {
    $parent1 = Category::factory()->create(['name' => 'Parent 1']);
    $parent2 = Category::factory()->create(['name' => 'Parent 2']);

    $category = Category::factory()->create(['name' => 'Category']);
    $category->update(['parent_id' => $parent1->id, 'sort_order' => 0]);

    $grandchild1 = Category::factory()->create(['name' => 'Grandchild 1']);
    $grandchild1->update(['parent_id' => $category->id, 'sort_order' => 0]);

    $grandchild2 = Category::factory()->create(['name' => 'Grandchild 2']);
    $grandchild2->update(['parent_id' => $category->id, 'sort_order' => 1]);

    $action = new ReorderCategoryAction();
    $result = $action->handle($category, $parent2->id, 0);

    expect($result)->toBeInstanceOf(Category::class)
        ->and($result->parent_id)->toBe($parent2->id);

    $category->refresh();
    expect($category->parent_id)->toBe($parent2->id);

    $children = $category->children()->get();
    expect($children)->toHaveCount(2)
        ->and($children->pluck('id')->toArray())->toContain($grandchild1->id, $grandchild2->id);
});

test('handles moving category to same position gracefully', function () {
    $parent = Category::factory()->create(['name' => 'Parent']);

    $child1 = Category::factory()->create(['name' => 'Child 1']);
    $child1->update(['parent_id' => $parent->id, 'sort_order' => 0]);

    $child2 = Category::factory()->create(['name' => 'Child 2']);
    $child2->update(['parent_id' => $parent->id, 'sort_order' => 1]);

    $action = new ReorderCategoryAction();
    $result = $action->handle($child1, $parent->id, 0);

    expect($result)->toBeInstanceOf(Category::class);

    $child1->refresh();
    expect($child1->parent_id)->toBe($parent->id);

    $parent->refresh();
    $children = $parent->children()->orderBy('sort_order')->get();
    expect($children)->toHaveCount(2);
});

test('verifies parent-child relationships after reorder', function () {
    $root = Category::factory()->create(['name' => 'Root']);

    $child1 = Category::factory()->create(['name' => 'Child 1']);
    $child1->update(['parent_id' => $root->id, 'sort_order' => 0]);

    $child2 = Category::factory()->create(['name' => 'Child 2']);
    $child2->update(['parent_id' => $root->id, 'sort_order' => 1]);

    $grandchild1 = Category::factory()->create(['name' => 'Grandchild 1']);
    $grandchild1->update(['parent_id' => $child1->id, 'sort_order' => 0]);

    $action = new ReorderCategoryAction();
    $action->handle($child2, $root->id, 0);

    $root->refresh();
    $child1->refresh();
    $child2->refresh();
    $grandchild1->refresh();

    expect($child1->parent_id)->toBe($root->id)
        ->and($child2->parent_id)->toBe($root->id)
        ->and($grandchild1->parent_id)->toBe($child1->id);
});

test('moves deeply nested category to different branch', function () {
    $branch1 = Category::factory()->create(['name' => 'Branch 1']);
    $branch2 = Category::factory()->create(['name' => 'Branch 2']);

    $level1 = Category::factory()->create(['name' => 'Level 1']);
    $level1->update(['parent_id' => $branch1->id, 'sort_order' => 0]);

    $level2 = Category::factory()->create(['name' => 'Level 2']);
    $level2->update(['parent_id' => $level1->id, 'sort_order' => 0]);

    $level3 = Category::factory()->create(['name' => 'Level 3']);
    $level3->update(['parent_id' => $level2->id, 'sort_order' => 0]);

    $action = new ReorderCategoryAction();
    $result = $action->handle($level2, $branch2->id, 0);

    expect($result)->toBeInstanceOf(Category::class)
        ->and($result->parent_id)->toBe($branch2->id);

    $level2->refresh();
    expect($level2->parent_id)->toBe($branch2->id);

    $level3->refresh();
    expect($level3->parent_id)->toBe($level2->id);

    $level1->refresh();
    $level1Children = $level1->children()->get();
    expect($level1Children)->toHaveCount(0);

    $branch2->refresh();
    $branch2Children = $branch2->children()->get();
    expect($branch2Children)->toHaveCount(1)
        ->and($branch2Children->first()->id)->toBe($level2->id);
});

test('prevents circular reference by moving parent under its own child', function () {
    $parent = Category::factory()->create(['name' => 'Parent']);
    $child = Category::factory()->create(['name' => 'Child']);
    $child->update(['parent_id' => $parent->id, 'sort_order' => 0]);

    $grandchild = Category::factory()->create(['name' => 'Grandchild']);
    $grandchild->update(['parent_id' => $child->id, 'sort_order' => 0]);

    $action = new ReorderCategoryAction();

    expect(fn () => $action->handle($parent, $grandchild->id, 0))
        ->toThrow(CategoryHierarchyException::class);
});

test('prevents moving category to itself as parent', function () {
    $category = Category::factory()->create(['name' => 'Category']);

    $action = new ReorderCategoryAction();

    expect(fn () => $action->handle($category, $category->id, 0))
        ->toThrow(CategoryHierarchyException::class);
});

test('maintains correct tree structure after multiple reorder operations', function () {
    $root1 = Category::factory()->create(['name' => 'Root 1']);
    $root2 = Category::factory()->create(['name' => 'Root 2']);

    $child1 = Category::factory()->create(['name' => 'Child 1']);
    $child1->update(['parent_id' => $root1->id, 'sort_order' => 0]);

    $child2 = Category::factory()->create(['name' => 'Child 2']);
    $child2->update(['parent_id' => $root1->id, 'sort_order' => 1]);

    $grandchild = Category::factory()->create(['name' => 'Grandchild']);
    $grandchild->update(['parent_id' => $child1->id, 'sort_order' => 0]);

    $action = new ReorderCategoryAction();

    // 1. Move child2 to root2
    $action->handle($child2, $root2->id, 0);

    // 2. Move grandchild to root level
    $action->handle($grandchild, null, 0);

    // 3. Move child1 to root2
    $action->handle($child1, $root2->id, 1);

    $root1->refresh();
    $root2->refresh();
    $child1->refresh();
    $child2->refresh();
    $grandchild->refresh();

    expect($root1->children()->count())->toBe(0);

    expect($root2->children()->count())->toBe(2);

    expect($grandchild->parent_id)->toBeNull();
});

test('handles sibling position adjustment when already at target position', function () {
    $parent = Category::factory()->create(['name' => 'Parent']);

    $child1 = Category::factory()->create(['name' => 'Child 1']);
    $child1->update(['parent_id' => $parent->id, 'sort_order' => 0]);

    $child2 = Category::factory()->create(['name' => 'Child 2']);
    $child2->update(['parent_id' => $parent->id, 'sort_order' => 1]);

    $child3 = Category::factory()->create(['name' => 'Child 3']);
    $child3->update(['parent_id' => $parent->id, 'sort_order' => 2]);

    $action = new ReorderCategoryAction();
    $action->handle($child1, $parent->id, 0);

    $parent->refresh();
    $children = $parent->children()->orderBy('sort_order')->get();
    expect($children->pluck('id')->toArray())->toBe([
        $child1->id,
        $child2->id,
        $child3->id,
    ]);
});

test('handles moving category to same parent with same position', function () {
    $parent = Category::factory()->create(['name' => 'Parent']);

    $child1 = Category::factory()->create(['name' => 'Child 1']);
    $child1->update(['parent_id' => $parent->id, 'sort_order' => 0]);

    $child2 = Category::factory()->create(['name' => 'Child 2']);
    $child2->update(['parent_id' => $parent->id, 'sort_order' => 1]);

    $action = new ReorderCategoryAction();
    $result = $action->handle($child1, $parent->id, 0);

    expect($result)->toBeInstanceOf(Category::class);

    $child1->refresh();
    expect($child1->parent_id)->toBe($parent->id);

    $parent->refresh();
    $children = $parent->children()->orderBy('sort_order')->get();
    expect($children->pluck('id')->toArray())->toBe([
        $child1->id,
        $child2->id,
    ]);
});
