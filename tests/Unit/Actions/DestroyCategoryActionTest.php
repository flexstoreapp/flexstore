<?php

declare(strict_types=1);

use App\Actions\DestroyCategoryAction;
use App\Models\Category;

covers(DestroyCategoryAction::class);

uses()->group('actions', 'category');

test('can delete a category', function () {
    $category = Category::factory()->create();

    $action = new DestroyCategoryAction();
    $result = $action->handle($category);

    expect($result)->toBeTrue();
    expect(Category::find($category->id))->toBeNull();
});

test('deletes category with children', function () {
    $parent = Category::factory()->create();
    $child1 = Category::factory()->create(['parent_id' => $parent->id]);
    $child2 = Category::factory()->create(['parent_id' => $parent->id]);
    $grandchild = Category::factory()->create(['parent_id' => $child1->id]);

    $action = new DestroyCategoryAction();
    $result = $action->handle($parent);

    expect($result)->toBeTrue();
    expect(Category::find($parent->id))->toBeNull();
    expect(Category::find($child1->id))->toBeNull();
    expect(Category::find($child2->id))->toBeNull();
    expect(Category::find($grandchild->id))->toBeNull();
});
