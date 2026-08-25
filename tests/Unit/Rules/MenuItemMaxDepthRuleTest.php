<?php

declare(strict_types=1);

use App\Models\MenuItem;
use App\Rules\MenuItemMaxDepthRule;

covers(MenuItemMaxDepthRule::class);

uses()->group('rules', 'menu-item');

test('passes when parent_id is null', function () {
    $rule = new MenuItemMaxDepthRule();

    $fail = fn (string $message) => throw new Exception($message);

    $rule->validate('parent_id', null, $fail);

    expect(true)->toBeTrue();
});

test('passes when parent does not exist', function () {
    $rule = new MenuItemMaxDepthRule();

    $fail = fn (string $message) => throw new Exception($message);

    $rule->validate('parent_id', 99999, $fail);

    expect(true)->toBeTrue();
});

test('passes when parent is at depth 1', function () {
    $parent = MenuItem::factory()->create(['parent_id' => null]);

    $rule = new MenuItemMaxDepthRule();

    $fail = fn (string $message) => throw new Exception($message);

    $rule->validate('parent_id', $parent->id, $fail);

    expect(true)->toBeTrue();
});

test('passes when parent is at depth 2', function () {
    $grandparent = MenuItem::factory()->create(['parent_id' => null]);
    $parent = MenuItem::factory()->create(['parent_id' => $grandparent->id]);

    $rule = new MenuItemMaxDepthRule();

    $fail = fn (string $message) => throw new Exception($message);

    $rule->validate('parent_id', $parent->id, $fail);

    expect(true)->toBeTrue();
});

test('fails when parent is at depth 3 with default max depth', function () {
    $root = MenuItem::factory()->create(['parent_id' => null]);
    $level2 = MenuItem::factory()->create(['parent_id' => $root->id]);
    $level3 = MenuItem::factory()->create(['parent_id' => $level2->id]);

    $rule = new MenuItemMaxDepthRule();

    $failCalled = false;
    $failMessage = '';

    $fail = function (string $message) use (&$failCalled, &$failMessage) {
        $failCalled = true;
        $failMessage = $message;
    };

    $rule->validate('parent_id', $level3->id, $fail);

    expect($failCalled)->toBeTrue();
    expect($failMessage)->toBe('The menu item cannot be nested more than 3 levels deep.');
});

test('respects custom max depth', function () {
    $root = MenuItem::factory()->create(['parent_id' => null]);
    $level2 = MenuItem::factory()->create(['parent_id' => $root->id]);

    $rule = new MenuItemMaxDepthRule(maxDepth: 2);

    $failCalled = false;
    $failMessage = '';

    $fail = function (string $message) use (&$failCalled, &$failMessage) {
        $failCalled = true;
        $failMessage = $message;
    };

    $rule->validate('parent_id', $level2->id, $fail);

    expect($failCalled)->toBeTrue();
    expect($failMessage)->toBe('The menu item cannot be nested more than 2 levels deep.');
});

test('passes when parent is at max depth minus one with custom max depth', function () {
    $root = MenuItem::factory()->create(['parent_id' => null]);

    $rule = new MenuItemMaxDepthRule(maxDepth: 2);

    $fail = fn (string $message) => throw new Exception($message);

    $rule->validate('parent_id', $root->id, $fail);

    expect(true)->toBeTrue();
});

test('calculates depth correctly for deeply nested items', function () {
    $root = MenuItem::factory()->create(['parent_id' => null]);
    $level2 = MenuItem::factory()->create(['parent_id' => $root->id]);

    $rule = new MenuItemMaxDepthRule(maxDepth: 3);

    $fail = fn (string $message) => throw new Exception($message);

    $rule->validate('parent_id', $level2->id, $fail);

    expect(true)->toBeTrue();
});

test('fails when max depth is 1 and parent exists', function () {
    $root = MenuItem::factory()->create(['parent_id' => null]);

    $rule = new MenuItemMaxDepthRule(maxDepth: 1);

    $failCalled = false;

    $fail = function (string $message) use (&$failCalled) {
        $failCalled = true;
    };

    $rule->validate('parent_id', $root->id, $fail);

    expect($failCalled)->toBeTrue();
});

test('handles orphaned menu items gracefully', function () {
    $parent = MenuItem::factory()->create(['parent_id' => null]);
    $menuItem = MenuItem::factory()->create(['parent_id' => $parent->id]);

    // Delete the parent to create an orphaned menu item
    $parent->delete();

    $rule = new MenuItemMaxDepthRule();

    $fail = fn (string $message) => throw new Exception($message);

    $rule->validate('parent_id', $menuItem->id, $fail);

    expect(true)->toBeTrue();
});
