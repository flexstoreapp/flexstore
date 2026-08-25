<?php

declare(strict_types=1);

use App\Actions\StoreReviewAction;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Storefront\ProductReviewController;
use App\Http\Requests\Storefront\StoreProductReviewRequest;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Review;
use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\postJson;

covers(ProductReviewController::class, StoreProductReviewRequest::class, StoreReviewAction::class);

uses()->group('storefront', 'review');

function createPaidOrderForProduct(User $user, Product $product): Order
{
    $order = Order::factory()->create([
        'customer_id' => $user->id,
        'payment_status' => PaymentStatus::Paid,
    ]);

    OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
    ]);

    return $order;
}

test('customer who purchased product can submit a review', function () {
    $user = User::factory()->create();
    $product = Product::factory()->available()->create();

    createPaidOrderForProduct($user, $product);

    actingAs($user)
        ->postJson(route('products.reviews.store', $product), [
            'rating' => 5,
            'title' => 'Great product',
            'content' => 'This is an amazing product that exceeded my expectations.',
        ])
        ->assertOk()
        ->assertJson(['message' => 'Thank you for your review!']);

    expect(Review::query()->where('product_id', $product->id)->where('user_id', $user->id)->exists())->toBeTrue();
});

test('user who has not purchased product cannot submit a review', function () {
    $user = User::factory()->create();
    $product = Product::factory()->available()->create();

    actingAs($user)
        ->postJson(route('products.reviews.store', $product), [
            'rating' => 5,
            'title' => 'Great product',
            'content' => 'This is an amazing product that exceeded my expectations.',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['rating' => 'You can only review products you have purchased.']);
});

test('guest cannot submit a review', function () {
    $product = Product::factory()->available()->create();

    postJson(route('products.reviews.store', $product), [
        'rating' => 5,
        'title' => 'Great product',
        'content' => 'This is an amazing product that exceeded my expectations.',
    ])->assertUnauthorized();
});

test('user cannot submit multiple reviews for the same product', function () {
    $user = User::factory()->create();
    $product = Product::factory()->available()->create();

    createPaidOrderForProduct($user, $product);

    Review::factory()->create([
        'product_id' => $product->id,
        'user_id' => $user->id,
    ]);

    actingAs($user)
        ->postJson(route('products.reviews.store', $product), [
            'rating' => 4,
            'title' => 'Another review',
            'content' => 'Trying to submit another review for the same product.',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('rating');

    expect(Review::query()->where('product_id', $product->id)->where('user_id', $user->id)->count())->toBe(1);
});

test('user can review different products they purchased', function () {
    $user = User::factory()->create();
    $product1 = Product::factory()->available()->create();
    $product2 = Product::factory()->available()->create();

    createPaidOrderForProduct($user, $product1);
    createPaidOrderForProduct($user, $product2);

    Review::factory()->create([
        'product_id' => $product1->id,
        'user_id' => $user->id,
    ]);

    actingAs($user)
        ->postJson(route('products.reviews.store', $product2), [
            'rating' => 4,
            'title' => 'Another product review',
            'content' => 'This is a review for a different product.',
        ])
        ->assertOk();

    expect(Review::query()->where('user_id', $user->id)->count())->toBe(2);
});

test('rating is required', function () {
    $user = User::factory()->create();
    $product = Product::factory()->available()->create();

    createPaidOrderForProduct($user, $product);

    actingAs($user)
        ->postJson(route('products.reviews.store', $product), [
            'title' => 'Great product',
            'content' => 'This is an amazing product.',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('rating');
});

test('rating must be between 1 and 5', function (int $rating) {
    $user = User::factory()->create();
    $product = Product::factory()->available()->create();

    createPaidOrderForProduct($user, $product);

    actingAs($user)
        ->postJson(route('products.reviews.store', $product), [
            'rating' => $rating,
            'content' => 'This is my review content.',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('rating');
})->with([0, 6, -1, 100]);

test('content is required', function () {
    $user = User::factory()->create();
    $product = Product::factory()->available()->create();

    createPaidOrderForProduct($user, $product);

    actingAs($user)
        ->postJson(route('products.reviews.store', $product), [
            'rating' => 5,
            'title' => 'Great product',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('content');
});

test('content must be at least 5 characters', function () {
    $user = User::factory()->create();
    $product = Product::factory()->available()->create();

    createPaidOrderForProduct($user, $product);

    actingAs($user)
        ->postJson(route('products.reviews.store', $product), [
            'rating' => 5,
            'content' => 'Hi',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('content');
});

test('content must not exceed 1000 characters', function () {
    $user = User::factory()->create();
    $product = Product::factory()->available()->create();

    createPaidOrderForProduct($user, $product);

    actingAs($user)
        ->postJson(route('products.reviews.store', $product), [
            'rating' => 5,
            'content' => str_repeat('a', 1001),
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('content');
});

test('title is optional', function () {
    $user = User::factory()->create();
    $product = Product::factory()->available()->create();

    createPaidOrderForProduct($user, $product);

    actingAs($user)
        ->postJson(route('products.reviews.store', $product), [
            'rating' => 5,
            'content' => 'This is an amazing product that exceeded my expectations.',
        ])
        ->assertOk();
});

test('user with unpaid order cannot review', function () {
    $user = User::factory()->create();
    $product = Product::factory()->available()->create();

    $order = Order::factory()->create([
        'customer_id' => $user->id,
        'payment_status' => PaymentStatus::Unpaid,
    ]);

    OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
    ]);

    actingAs($user)
        ->postJson(route('products.reviews.store', $product), [
            'rating' => 5,
            'content' => 'This is my review content.',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['rating' => 'You can only review products you have purchased.']);
});

test('user with failed order cannot review', function () {
    $user = User::factory()->create();
    $product = Product::factory()->available()->create();

    $order = Order::factory()->create([
        'customer_id' => $user->id,
        'payment_status' => PaymentStatus::Failed,
    ]);

    OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
    ]);

    actingAs($user)
        ->postJson(route('products.reviews.store', $product), [
            'rating' => 5,
            'content' => 'This is my review content.',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['rating' => 'You can only review products you have purchased.']);
});
