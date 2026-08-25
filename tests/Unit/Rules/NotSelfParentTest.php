<?php

declare(strict_types=1);

use App\Models\Category;
use App\Rules\NotSelfParent;

covers(NotSelfParent::class);

uses()->group('rules', 'category');

test('validation is skipped when value is blank', function () {
    $category = Category::factory()->create();
    $rule = new NotSelfParent($category);

    $failClosureCalled = false;

    foreach ([null, '', '   '] as $value) {
        $rule->validate('parent_id', $value, function () use (&$failClosureCalled) {
            $failClosureCalled = true;
        });

        expect($failClosureCalled)->toBeFalse();
    }
});

test('validation passes when parent_id is different from category id', function () {
    $category = Category::factory()->create();
    $otherCategory = Category::factory()->create();

    $rule = new NotSelfParent($category);

    $failClosureCalled = false;

    $rule->validate('parent_id', $otherCategory->id, function () use (&$failClosureCalled) {
        $failClosureCalled = true;
    });

    expect($failClosureCalled)->toBeFalse();
});

test('validation fails when parent_id equals category id', function () {
    $category = Category::factory()->create();
    $rule = new NotSelfParent($category);

    $failClosureCalled = false;

    $rule->validate('parent_id', $category->id, function () use (&$failClosureCalled) {
        $failClosureCalled = true;
    });

    expect($failClosureCalled)->toBeTrue();
});

test('validation fails when parent_id equals category id as string', function () {
    $category = Category::factory()->create();
    $rule = new NotSelfParent($category);

    $failClosureCalled = false;

    $rule->validate('parent_id', (string) $category->id, function () use (&$failClosureCalled) {
        $failClosureCalled = true;
    });

    expect($failClosureCalled)->toBeTrue();
});
