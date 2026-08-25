<?php

declare(strict_types=1);

use App\Models\Category;
use App\Rules\NoCircularReference;

covers(NoCircularReference::class);

uses()->group('rules', 'category');

test('validation is skipped when value is blank', function () {
    $category = Category::factory()->create();
    $rule = new NoCircularReference($category);

    $failClosureCalled = false;

    foreach ([null, '', '   '] as $value) {
        $rule->validate('parent_id', $value, function () use (&$failClosureCalled) {
            $failClosureCalled = true;
        });

        expect($failClosureCalled)->toBeFalse();
    }
});

test('validation passes when new parent does not exist', function () {
    $category = Category::factory()->create();
    $rule = new NoCircularReference($category);

    $failClosureCalled = false;

    $rule->validate('parent_id', 99999, function () use (&$failClosureCalled) {
        $failClosureCalled = true;
    });

    expect($failClosureCalled)->toBeFalse();
});

test('validation passes when new parent is not a descendant', function () {
    // Create a hierarchy: grandparent -> parent -> category -> child
    $grandparent = Category::factory()->create();
    $parent = Category::factory()->create(['parent_id' => $grandparent->id]);
    $category = Category::factory()->create(['parent_id' => $parent->id]);
    $child = Category::factory()->create(['parent_id' => $category->id]);

    $grandparent->refresh();
    $parent->refresh();
    $category->refresh();
    $child->refresh();

    $rule = new NoCircularReference($category);

    $failClosureCalled = false;

    // Moving category under grandparent should be allowed
    $rule->validate('parent_id', $grandparent->id, function () use (&$failClosureCalled) {
        $failClosureCalled = true;
    });

    expect($failClosureCalled)->toBeFalse();
});

test('validation fails when new parent is a descendant', function () {
    // Create a hierarchy: parent -> category -> child
    $parent = Category::factory()->create();
    $category = Category::factory()->create(['parent_id' => $parent->id]);
    $child = Category::factory()->create(['parent_id' => $category->id]);

    $parent->refresh();
    $category->refresh();
    $child->refresh();

    $rule = new NoCircularReference($category);

    $failClosureCalled = false;

    // Trying to move category under its child should fail
    $rule->validate('parent_id', $child->id, function () use (&$failClosureCalled) {
        $failClosureCalled = true;
    });

    expect($failClosureCalled)->toBeTrue();
});

test('validation fails when category is ancestor of new parent', function () {
    // Create a hierarchy: category -> child -> grandchild
    $category = Category::factory()->create();
    $child = Category::factory()->create(['parent_id' => $category->id]);
    $grandchild = Category::factory()->create(['parent_id' => $child->id]);

    $category->refresh();
    $child->refresh();
    $grandchild->refresh();

    $rule = new NoCircularReference($category);

    $failClosureCalled = false;

    // Trying to move category under its grandchild should fail
    $rule->validate('parent_id', $grandchild->id, function () use (&$failClosureCalled) {
        $failClosureCalled = true;
    });

    expect($failClosureCalled)->toBeTrue();
});
