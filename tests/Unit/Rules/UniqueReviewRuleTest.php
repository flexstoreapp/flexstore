<?php

declare(strict_types=1);

namespace Tests\Unit\Rules;

use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use App\Rules\UniqueReviewRule;

covers(UniqueReviewRule::class);

uses()->group('rules', 'review');

test('validation is skipped when product_id is missing', function () {
    $rule = new UniqueReviewRule();
    $rule->setData([
        'user_id' => 1,
    ]);

    $failClosureCalled = false;

    $rule->validate('product_id', 1, function () use (&$failClosureCalled) {
        $failClosureCalled = true;
    });

    expect($failClosureCalled)->toBeFalse();
});

test('validation is skipped when user_id is missing', function () {
    $rule = new UniqueReviewRule();
    $rule->setData([
        'product_id' => 1,
    ]);

    $failClosureCalled = false;

    $rule->validate('product_id', 1, function () use (&$failClosureCalled) {
        $failClosureCalled = true;
    });

    expect($failClosureCalled)->toBeFalse();
});

test('validation is skipped when both product_id and user_id are missing', function () {
    $rule = new UniqueReviewRule();
    $rule->setData([]);

    $failClosureCalled = false;

    $rule->validate('product_id', 1, function () use (&$failClosureCalled) {
        $failClosureCalled = true;
    });

    expect($failClosureCalled)->toBeFalse();
});

test('validation passes when no review exists for product and user combination', function () {
    $product = Product::factory()->create();
    $user = User::factory()->create();

    $rule = new UniqueReviewRule();
    $rule->setData([
        'product_id' => $product->id,
        'user_id' => $user->id,
    ]);

    $failClosureCalled = false;

    $rule->validate('product_id', $product->id, function () use (&$failClosureCalled) {
        $failClosureCalled = true;
    });

    expect($failClosureCalled)->toBeFalse();
});

test('validation fails when review already exists for product and user combination', function () {
    $product = Product::factory()->create();
    $user = User::factory()->create();
    Review::factory()->create([
        'product_id' => $product->id,
        'user_id' => $user->id,
    ]);

    $rule = new UniqueReviewRule();
    $rule->setData([
        'product_id' => $product->id,
        'user_id' => $user->id,
    ]);

    $failClosureCalled = false;

    $rule->validate('product_id', $product->id, function () use (&$failClosureCalled) {
        $failClosureCalled = true;
    });

    expect($failClosureCalled)->toBeTrue();
});

test('validation passes when review exists for same product but different user', function () {
    $product = Product::factory()->create();
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    Review::factory()->create([
        'product_id' => $product->id,
        'user_id' => $user1->id,
    ]);

    $rule = new UniqueReviewRule();
    $rule->setData([
        'product_id' => $product->id,
        'user_id' => $user2->id,
    ]);

    $failClosureCalled = false;

    $rule->validate('product_id', $product->id, function () use (&$failClosureCalled) {
        $failClosureCalled = true;
    });

    expect($failClosureCalled)->toBeFalse();
});

test('validation passes when review exists for same user but different product', function () {
    $product1 = Product::factory()->create();
    $product2 = Product::factory()->create();
    $user = User::factory()->create();

    Review::factory()->create([
        'product_id' => $product1->id,
        'user_id' => $user->id,
    ]);

    $rule = new UniqueReviewRule();
    $rule->setData([
        'product_id' => $product2->id,
        'user_id' => $user->id,
    ]);

    $failClosureCalled = false;

    $rule->validate('product_id', $product2->id, function () use (&$failClosureCalled) {
        $failClosureCalled = true;
    });

    expect($failClosureCalled)->toBeFalse();
});

test('validation handles string product_id and user_id values', function () {
    $product = Product::factory()->create();
    $user = User::factory()->create();
    Review::factory()->create([
        'product_id' => $product->id,
        'user_id' => $user->id,
    ]);

    $rule = new UniqueReviewRule();
    $rule->setData([
        'product_id' => (string) $product->id,
        'user_id' => (string) $user->id,
    ]);

    $failClosureCalled = false;

    $rule->validate('product_id', (string) $product->id, function () use (&$failClosureCalled) {
        $failClosureCalled = true;
    });

    expect($failClosureCalled)->toBeTrue();
});
