<?php

declare(strict_types=1);

use App\Actions\ReorderCategoryAction;
use App\Enums\Permission;
use App\Enums\Role as RoleEnum;
use App\Http\Controllers\Admin\CategoryReorderController;
use App\Http\Requests\Admin\ReorderCategoryRequest;
use App\Models\Category;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\patch;

covers(CategoryReorderController::class, ReorderCategoryRequest::class, ReorderCategoryAction::class);

uses()->group('category');

test('successfully reorders category to new parent', function () {
    $oldParent = Category::factory()->create(['name' => 'Old Parent']);
    $newParent = Category::factory()->create(['name' => 'New Parent']);

    $category = Category::factory()->create(['name' => 'Category to Move']);
    $category->update(['parent_id' => $oldParent->id, 'sort_order' => 0]);

    $existingChild = Category::factory()->create(['name' => 'Existing Child']);
    $existingChild->update(['parent_id' => $newParent->id, 'sort_order' => 0]);

    $response = actingAsSuperAdmin()->patch(route('admin.categories.reorder', $category), [
        'parent_id' => $newParent->id,
        'position' => 0,
    ]);

    $response->assertRedirectBack()
        ->assertSessionHasNoErrors();

    $category->refresh();
    expect($category->parent_id)->toBe($newParent->id);

    $newParent->refresh();
    $children = $newParent->children()->orderBy('sort_order')->get();
    expect($children)->toHaveCount(2)
        ->and($children->first()->id)->toBe($category->id);
});

test('successfully reorders category to root level', function () {

    $parent = Category::factory()->create(['name' => 'Parent']);

    $category = Category::factory()->create(['name' => 'Category to Move']);
    $category->update(['parent_id' => $parent->id, 'sort_order' => 0]);

    expect($category->parent_id)->toBe($parent->id);

    $response = actingAsSuperAdmin()->patch(route('admin.categories.reorder', $category), [
        'parent_id' => null,
        'position' => 0,
    ]);

    $response->assertRedirectBack()
        ->assertSessionHasNoErrors();

    $category->refresh();
    expect($category->parent_id)->toBeNull();
});

test('successfully reorders category within same parent', function () {

    $parent = Category::factory()->create(['name' => 'Parent']);

    $child1 = Category::factory()->create(['name' => 'Child 1']);
    $child1->update(['parent_id' => $parent->id, 'sort_order' => 0]);

    $child2 = Category::factory()->create(['name' => 'Child 2']);
    $child2->update(['parent_id' => $parent->id, 'sort_order' => 1]);

    $child3 = Category::factory()->create(['name' => 'Child 3']);
    $child3->update(['parent_id' => $parent->id, 'sort_order' => 2]);

    $response = actingAsSuperAdmin()->patch(route('admin.categories.reorder', $child3), [
        'parent_id' => $parent->id,
        'position' => 0,
    ]);

    $response->assertRedirectBack()
        ->assertSessionHasNoErrors();

    $parent->refresh();
    $children = $parent->children()->orderBy('sort_order')->get();
    expect($children)->toHaveCount(3)
        ->and($children->pluck('id')->toArray())->toBe([
            $child3->id,
            $child1->id,
            $child2->id,
        ]);
});

test('validates parent_id must exist', function () {

    $category = Category::factory()->create(['name' => 'Category']);

    $response = actingAsSuperAdmin()->patch(route('admin.categories.reorder', $category), [
        'parent_id' => 99999, // Non-existent ID
        'position' => 0,
    ]);

    $response->assertRedirectBack()
        ->assertInvalid('parent_id');
});

test('validates position is required', function () {

    $category = Category::factory()->create(['name' => 'Category']);

    $response = actingAsSuperAdmin()->patch(route('admin.categories.reorder', $category), [
        'parent_id' => null,
        // Missing position
    ]);

    $response->assertRedirectBack()
        ->assertInvalid('position');
});

test('validates position must be non-negative', function () {

    $category = Category::factory()->create(['name' => 'Category']);

    $response = actingAsSuperAdmin()->patch(route('admin.categories.reorder', $category), [
        'parent_id' => null,
        'position' => -1,
    ]);

    $response->assertRedirectBack()
        ->assertInvalid('position');
});

test('prevents category from being its own parent', function () {

    $category = Category::factory()->create(['name' => 'Category']);

    $response = actingAsSuperAdmin()->patch(route('admin.categories.reorder', $category), [
        'parent_id' => $category->id,
        'position' => 0,
    ]);

    $response->assertRedirectBack()
        ->assertInvalid('parent_id');

    $category->refresh();
    expect($category->parent_id)->not->toBe($category->id);
});

test('prevents circular reference by moving parent under its own child', function () {

    $parent = Category::factory()->create(['name' => 'Parent']);
    $child = Category::factory()->create(['name' => 'Child']);
    $child->update(['parent_id' => $parent->id, 'sort_order' => 0]);

    $grandchild = Category::factory()->create(['name' => 'Grandchild']);
    $grandchild->update(['parent_id' => $child->id, 'sort_order' => 0]);

    $response = actingAsSuperAdmin()->patch(route('admin.categories.reorder', $parent), [
        'parent_id' => $grandchild->id,
        'position' => 0,
    ]);

    $response->assertRedirectBack()
        ->assertInvalid('parent_id');

    $parent->refresh();
    expect($parent->parent_id)->toBeNull();
    expect($grandchild->isAncestorOf($parent))->toBeFalse();
});

test('prevents circular reference by moving parent under its direct child', function () {

    $parent = Category::factory()->create(['name' => 'Parent']);
    $child = Category::factory()->create(['name' => 'Child']);
    $child->update(['parent_id' => $parent->id, 'sort_order' => 0]);

    $response = actingAsSuperAdmin()->patch(route('admin.categories.reorder', $parent), [
        'parent_id' => $child->id,
        'position' => 0,
    ]);

    $response->assertRedirectBack()
        ->assertInvalid('parent_id');

    $parent->refresh();
    expect($parent->parent_id)->toBeNull();
});

test('maintains tree structure integrity after reorder', function () {

    $root1 = Category::factory()->create(['name' => 'Root 1']);
    $root2 = Category::factory()->create(['name' => 'Root 2']);

    $child1 = Category::factory()->create(['name' => 'Child 1']);
    $child1->update(['parent_id' => $root1->id, 'sort_order' => 0]);

    $child2 = Category::factory()->create(['name' => 'Child 2']);
    $child2->update(['parent_id' => $root1->id, 'sort_order' => 1]);

    $grandchild = Category::factory()->create(['name' => 'Grandchild']);
    $grandchild->update(['parent_id' => $child1->id, 'sort_order' => 0]);

    $response = actingAsSuperAdmin()->patch(route('admin.categories.reorder', $child1), [
        'parent_id' => $root2->id,
        'position' => 0,
    ]);

    $response->assertRedirect()
        ->assertSessionHasNoErrors();

    $child1->refresh();
    expect($child1->parent_id)->toBe($root2->id);

    $grandchild->refresh();
    expect($grandchild->parent_id)->toBe($child1->id);
});

test('maintains tree structure after moving category with multiple descendants', function () {

    $parent1 = Category::factory()->create(['name' => 'Parent 1']);
    $parent2 = Category::factory()->create(['name' => 'Parent 2']);

    $category = Category::factory()->create(['name' => 'Category']);
    $category->update(['parent_id' => $parent1->id, 'sort_order' => 0]);

    $child1 = Category::factory()->create(['name' => 'Child 1']);
    $child1->update(['parent_id' => $category->id, 'sort_order' => 0]);

    $child2 = Category::factory()->create(['name' => 'Child 2']);
    $child2->update(['parent_id' => $category->id, 'sort_order' => 1]);

    $grandchild = Category::factory()->create(['name' => 'Grandchild']);
    $grandchild->update(['parent_id' => $child1->id, 'sort_order' => 0]);

    $response = actingAsSuperAdmin()->patch(route('admin.categories.reorder', $category), [
        'parent_id' => $parent2->id,
        'position' => 0,
    ]);

    $response->assertRedirect()
        ->assertSessionHasNoErrors();

    $category->refresh();
    expect($category->parent_id)->toBe($parent2->id);

    $child1->refresh();
    $child2->refresh();
    $grandchild->refresh();

    expect($child1->parent_id)->toBe($category->id)
        ->and($child2->parent_id)->toBe($category->id)
        ->and($grandchild->parent_id)->toBe($child1->id);
});

test('reorders root categories', function () {

    $root1 = Category::factory()->create(['name' => 'Root 1']);
    $root2 = Category::factory()->create(['name' => 'Root 2']);
    $root3 = Category::factory()->create(['name' => 'Root 3']);

    $response = actingAsSuperAdmin()->patch(route('admin.categories.reorder', $root3), [
        'parent_id' => null,
        'position' => 0,
    ]);

    $response->assertRedirect()
        ->assertSessionHasNoErrors();

    $rootCategories = Category::whereNull('parent_id')->orderBy('sort_order')->get();
    expect($rootCategories)->toHaveCount(3)
        ->and($rootCategories->pluck('id')->toArray())->toBe([
            $root3->id,
            $root1->id,
            $root2->id,
        ]);
});

test('verifies parent_id and sort_order integrity after reorder', function () {

    $root = Category::factory()->create(['name' => 'Root']);

    $child1 = Category::factory()->create(['name' => 'Child 1']);
    $child1->update(['parent_id' => $root->id, 'sort_order' => 0]);

    $child2 = Category::factory()->create(['name' => 'Child 2']);
    $child2->update(['parent_id' => $root->id, 'sort_order' => 1]);

    $grandchild = Category::factory()->create(['name' => 'Grandchild']);
    $grandchild->update(['parent_id' => $child1->id, 'sort_order' => 0]);

    $response = actingAsSuperAdmin()->patch(route('admin.categories.reorder', $child2), [
        'parent_id' => $root->id,
        'position' => 0,
    ]);

    $response->assertRedirect()
        ->assertSessionHasNoErrors();

    $allCategories = Category::all();
    foreach ($allCategories as $category) {
        if ($category->parent_id) {
            expect(Category::where('id', $category->parent_id)->exists())->toBeTrue(
                "Category '{$category->name}' references non-existent parent_id {$category->parent_id}"
            );
        }

        expect($category->sort_order)->toBeGreaterThanOrEqual(0);
    }

    $children = $root->children()->orderBy('sort_order')->get();
    expect($children->first()->id)->toBe($child2->id)
        ->and($children->last()->id)->toBe($child1->id);

    $grandchild->refresh();
    expect($grandchild->parent_id)->toBe($child1->id);
});

test('requires authentication', function () {
    $category = Category::factory()->create();

    $response = patch(route('admin.categories.reorder', $category), [
        'parent_id' => null,
        'position' => 0,
    ]);

    $response->assertRedirect(route('admin.login'));
});

test('requires categories.update permission', function () {
    $role = Role::query()->where(['name' => RoleEnum::Admin])->firstOrFail();
    $category = Category::factory()->create();

    $response = actingAsAdmin()->patch(route('admin.categories.reorder', $category), [
        'parent_id' => null,
        'position' => 0,
    ]);

    $response->assertRedirectBack()
        ->assertSessionHasNoErrors();

    $role->revokePermissionTo(Permission::CategoriesManage);

    $anotherCategory = Category::factory()->create();

    $response = actingAsAdmin()->patch(route('admin.categories.reorder', $anotherCategory), [
        'parent_id' => null,
        'position' => 0,
    ]);

    $response->assertForbidden();
});
