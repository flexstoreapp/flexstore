<?php

declare(strict_types=1);

use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use App\Rules\HasNotReviewedProduct;

covers(HasNotReviewedProduct::class);

test('passes when user has not reviewed the product', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create();

    $rule = new HasNotReviewedProduct($product, $user);
    $failed = false;

    $rule->validate('rating', 5, function () use (&$failed) {
        $failed = true;
    });

    expect($failed)->toBeFalse();
});

test('passes when user has reviewed a different product', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create();
    $otherProduct = Product::factory()->create();

    Review::factory()->create([
        'product_id' => $otherProduct->id,
        'user_id' => $user->id,
    ]);

    $rule = new HasNotReviewedProduct($product, $user);
    $failed = false;

    $rule->validate('rating', 5, function () use (&$failed) {
        $failed = true;
    });

    expect($failed)->toBeFalse();
});

test('passes when different user has reviewed the product', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $product = Product::factory()->create();

    Review::factory()->create([
        'product_id' => $product->id,
        'user_id' => $otherUser->id,
    ]);

    $rule = new HasNotReviewedProduct($product, $user);
    $failed = false;

    $rule->validate('rating', 5, function () use (&$failed) {
        $failed = true;
    });

    expect($failed)->toBeFalse();
});

test('passes when user is null', function () {
    $product = Product::factory()->create();

    $rule = new HasNotReviewedProduct($product, null);
    $failed = false;

    $rule->validate('rating', 5, function () use (&$failed) {
        $failed = true;
    });

    expect($failed)->toBeFalse();
});

test('fails when user has already reviewed the product', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create();

    Review::factory()->create([
        'product_id' => $product->id,
        'user_id' => $user->id,
    ]);

    $rule = new HasNotReviewedProduct($product, $user);
    $message = null;

    $rule->validate('rating', 5, function ($msg) use (&$message) {
        $message = $msg;
    });

    expect($message)->toBe(__('You have already reviewed this product.'));
});
