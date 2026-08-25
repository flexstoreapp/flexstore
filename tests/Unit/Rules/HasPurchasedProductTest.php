<?php

declare(strict_types=1);

use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Rules\HasPurchasedProduct;

covers(HasPurchasedProduct::class);

test('passes when user has paid order with product', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create();

    $order = Order::factory()->create([
        'customer_id' => $user->id,
        'payment_status' => PaymentStatus::Paid,
    ]);

    OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
    ]);

    $rule = new HasPurchasedProduct($product, $user);
    $failed = false;

    $rule->validate('rating', 5, function () use (&$failed) {
        $failed = true;
    });

    expect($failed)->toBeFalse();
});

test('passes when user has partially refunded order with product', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create();

    $order = Order::factory()->create([
        'customer_id' => $user->id,
        'payment_status' => PaymentStatus::PartiallyRefunded,
    ]);

    OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
    ]);

    $rule = new HasPurchasedProduct($product, $user);
    $failed = false;

    $rule->validate('rating', 5, function () use (&$failed) {
        $failed = true;
    });

    expect($failed)->toBeFalse();
});

test('fails when user has no orders', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create();

    $rule = new HasPurchasedProduct($product, $user);
    $message = null;

    $rule->validate('rating', 5, function ($msg) use (&$message) {
        $message = $msg;
    });

    expect($message)->toBe(__('You can only review products you have purchased.'));
});

test('fails when user has order but not for this product', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create();
    $otherProduct = Product::factory()->create();

    $order = Order::factory()->create([
        'customer_id' => $user->id,
        'payment_status' => PaymentStatus::Paid,
    ]);

    OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $otherProduct->id,
    ]);

    $rule = new HasPurchasedProduct($product, $user);
    $message = null;

    $rule->validate('rating', 5, function ($msg) use (&$message) {
        $message = $msg;
    });

    expect($message)->toBe(__('You can only review products you have purchased.'));
});

test('fails when order is unpaid', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create();

    $order = Order::factory()->create([
        'customer_id' => $user->id,
        'payment_status' => PaymentStatus::Unpaid,
    ]);

    OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
    ]);

    $rule = new HasPurchasedProduct($product, $user);
    $message = null;

    $rule->validate('rating', 5, function ($msg) use (&$message) {
        $message = $msg;
    });

    expect($message)->toBe(__('You can only review products you have purchased.'));
});

test('fails when order is failed', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create();

    $order = Order::factory()->create([
        'customer_id' => $user->id,
        'payment_status' => PaymentStatus::Failed,
    ]);

    OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
    ]);

    $rule = new HasPurchasedProduct($product, $user);
    $message = null;

    $rule->validate('rating', 5, function ($msg) use (&$message) {
        $message = $msg;
    });

    expect($message)->toBe(__('You can only review products you have purchased.'));
});

test('fails when order is canceled', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create();

    $order = Order::factory()->create([
        'customer_id' => $user->id,
        'payment_status' => PaymentStatus::Canceled,
    ]);

    OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
    ]);

    $rule = new HasPurchasedProduct($product, $user);
    $message = null;

    $rule->validate('rating', 5, function ($msg) use (&$message) {
        $message = $msg;
    });

    expect($message)->toBe(__('You can only review products you have purchased.'));
});

test('fails when order is fully refunded', function () {
    $user = User::factory()->create();
    $product = Product::factory()->create();

    $order = Order::factory()->create([
        'customer_id' => $user->id,
        'payment_status' => PaymentStatus::Refunded,
    ]);

    OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
    ]);

    $rule = new HasPurchasedProduct($product, $user);
    $message = null;

    $rule->validate('rating', 5, function ($msg) use (&$message) {
        $message = $msg;
    });

    expect($message)->toBe(__('You can only review products you have purchased.'));
});

test('fails when user is null', function () {
    $product = Product::factory()->create();

    $rule = new HasPurchasedProduct($product, null);
    $message = null;

    $rule->validate('rating', 5, function ($msg) use (&$message) {
        $message = $msg;
    });

    expect($message)->toBe(__('You must be logged in to review a product.'));
});

test('fails when order belongs to different user', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $product = Product::factory()->create();

    $order = Order::factory()->create([
        'customer_id' => $otherUser->id,
        'payment_status' => PaymentStatus::Paid,
    ]);

    OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
    ]);

    $rule = new HasPurchasedProduct($product, $user);
    $message = null;

    $rule->validate('rating', 5, function ($msg) use (&$message) {
        $message = $msg;
    });

    expect($message)->toBe(__('You can only review products you have purchased.'));
});
