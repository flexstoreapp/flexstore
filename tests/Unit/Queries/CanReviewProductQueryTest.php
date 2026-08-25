<?php

declare(strict_types=1);

use App\Enums\PaymentStatus;
use App\Enums\ReviewStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use App\Queries\CanReviewProductQuery;

covers(CanReviewProductQuery::class);

uses()->group('storefront', 'product', 'review');

function purchase(User $user, Product $product): void
{
    $order = Order::factory()->create([
        'customer_id' => $user->id,
        'payment_status' => PaymentStatus::Paid,
    ]);

    OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
    ]);
}

test('a guest cannot review', function () {
    $product = Product::factory()->available()->create();

    expect(app(CanReviewProductQuery::class)->execute($product, null))->toBeFalse();
});

test('a purchaser who has not reviewed can review', function () {
    $user = User::factory()->create();
    $product = Product::factory()->available()->create();
    purchase($user, $product);

    expect(app(CanReviewProductQuery::class)->execute($product, $user))->toBeTrue();
});

test('a customer who did not purchase cannot review', function () {
    $user = User::factory()->create();
    $product = Product::factory()->available()->create();

    expect(app(CanReviewProductQuery::class)->execute($product, $user))->toBeFalse();
});

test('a purchaser who already reviewed cannot review again', function () {
    $user = User::factory()->create();
    $product = Product::factory()->available()->create();
    purchase($user, $product);
    Review::factory()->for($product)->create(['user_id' => $user->id, 'status' => ReviewStatus::Approved]);

    expect(app(CanReviewProductQuery::class)->execute($product, $user))->toBeFalse();
});
