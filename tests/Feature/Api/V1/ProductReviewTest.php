<?php

declare(strict_types=1);

use App\Enums\PaymentStatus;
use App\Enums\ReviewStatus;
use App\Enums\TokenAbility;
use App\Http\Controllers\Api\V1\ProductReviewController;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;

covers(ProductReviewController::class);

uses()->group('api');

test('a customer who bought the product can review it', function (): void {
    $user = User::factory()->create();
    $product = Product::factory()->available()->create();

    $order = Order::factory()->create(['customer_id' => $user->id, 'payment_status' => PaymentStatus::Paid]);
    OrderItem::factory()->create(['order_id' => $order->id, 'product_id' => $product->id]);

    Sanctum::actingAs($user, [TokenAbility::Customer->value]);

    postJson(route('api.v1.products.reviews.store', $product->url_handle), [
        'rating' => 5,
        'title' => 'Great product',
        'content' => 'This is an amazing product that exceeded my expectations.',
    ])->assertCreated();

    expect(Review::query()->where('product_id', $product->id)->where('user_id', $user->id)->exists())->toBeTrue();
});

test('a customer who did not buy the product cannot review it', function (): void {
    $product = Product::factory()->available()->create();

    Sanctum::actingAs(User::factory()->create(), [TokenAbility::Customer->value]);

    postJson(route('api.v1.products.reviews.store', $product->url_handle), [
        'rating' => 5,
        'content' => 'This is an amazing product that exceeded my expectations.',
    ])->assertJsonValidationErrors('rating');
});

test('only approved reviews are listed', function (): void {
    $product = Product::factory()->available()->create();
    $approved = Review::factory()->create(['product_id' => $product->id, 'status' => ReviewStatus::Approved]);
    Review::factory()->create(['product_id' => $product->id, 'status' => ReviewStatus::Pending]);

    getJson(route('api.v1.products.reviews.index', $product->url_handle))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $approved->id);
});
