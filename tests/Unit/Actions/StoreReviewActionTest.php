<?php

declare(strict_types=1);

use App\Actions\StoreReviewAction;
use App\DTOs\StoreReviewInput;
use App\Enums\ReviewStatus;
use App\Models\Product;
use App\Models\Review;
use App\Models\User;

use function Pest\Laravel\assertDatabaseHas;

covers(StoreReviewAction::class, StoreReviewInput::class);

uses()->group('actions', 'review');

test('creates a review with all fields provided', function () {
    $product = Product::factory()->create();
    $user = User::factory()->create();

    $data = [
        'product_id' => $product->id,
        'user_id' => $user->id,
        'rating' => 5,
        'title' => 'Great Product',
        'content' => 'This is an excellent product. Highly recommended!',
    ];

    $action = app(StoreReviewAction::class);
    $result = $action->handle(StoreReviewInput::fromArray($data));

    expect($result)->toBeInstanceOf(Review::class)
        ->and($result->product_id)->toBe($product->id)
        ->and($result->user_id)->toBe($user->id)
        ->and($result->rating)->toBe(5)
        ->and($result->title)->toBe('Great Product')
        ->and($result->content)->toBe('This is an excellent product. Highly recommended!')
        ->and($result->status)->toBe(ReviewStatus::Pending)
        ->and(Review::query()->count())->toBe(1);

    assertDatabaseHas('reviews', [
        'product_id' => $product->id,
        'user_id' => $user->id,
        'rating' => 5,
        'title' => 'Great Product',
        'content' => 'This is an excellent product. Highly recommended!',
        'status' => ReviewStatus::Pending->value,
    ]);
});

test('creates a review without optional fields', function () {
    $product = Product::factory()->create();
    $user = User::factory()->create();

    $data = [
        'product_id' => $product->id,
        'user_id' => $user->id,
        'rating' => 4,
        'content' => 'Good product overall.',
    ];

    $action = app(StoreReviewAction::class);
    $result = $action->handle(StoreReviewInput::fromArray($data));

    expect($result->product_id)->toBe($product->id)
        ->and($result->user_id)->toBe($user->id)
        ->and($result->rating)->toBe(4)
        ->and($result->title)->toBeNull()
        ->and($result->content)->toBe('Good product overall.')
        ->and($result->status)->toBe(ReviewStatus::Pending);

    assertDatabaseHas('reviews', [
        'product_id' => $product->id,
        'user_id' => $user->id,
        'rating' => 4,
        'title' => null,
        'content' => 'Good product overall.',
        'status' => ReviewStatus::Pending->value,
    ]);
});

test('creates a review with minimum rating', function () {
    $product = Product::factory()->create();
    $user = User::factory()->create();

    $data = [
        'product_id' => $product->id,
        'user_id' => $user->id,
        'rating' => 1,
        'content' => 'Not satisfied.',
    ];

    $action = app(StoreReviewAction::class);
    $result = $action->handle(StoreReviewInput::fromArray($data));

    expect($result->rating)->toBe(1)
        ->and($result->status)->toBe(ReviewStatus::Pending);
});

test('creates a review with maximum rating', function () {
    $product = Product::factory()->create();
    $user = User::factory()->create();

    $data = [
        'product_id' => $product->id,
        'user_id' => $user->id,
        'rating' => 5,
        'content' => 'Excellent!',
    ];

    $action = app(StoreReviewAction::class);
    $result = $action->handle(StoreReviewInput::fromArray($data));

    expect($result->rating)->toBe(5)
        ->and($result->status)->toBe(ReviewStatus::Pending);
});

test('creates multiple reviews for different products', function () {
    $product1 = Product::factory()->create();
    $product2 = Product::factory()->create();
    $user = User::factory()->create();

    $data1 = [
        'product_id' => $product1->id,
        'user_id' => $user->id,
        'rating' => 5,
        'content' => 'Great product 1',
    ];

    $data2 = [
        'product_id' => $product2->id,
        'user_id' => $user->id,
        'rating' => 4,
        'content' => 'Good product 2',
    ];

    $action = app(StoreReviewAction::class);
    $review1 = $action->handle(StoreReviewInput::fromArray($data1));
    $review2 = $action->handle(StoreReviewInput::fromArray($data2));

    expect($review1->id)->not->toBe($review2->id)
        ->and($review1->product_id)->toBe($product1->id)
        ->and($review2->product_id)->toBe($product2->id)
        ->and($review1->user_id)->toBe($user->id)
        ->and($review2->user_id)->toBe($user->id)
        ->and(Review::query()->count())->toBe(2);
});

test('creates reviews for same product by different users', function () {
    $product = Product::factory()->create();
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    $data1 = [
        'product_id' => $product->id,
        'user_id' => $user1->id,
        'rating' => 5,
        'content' => 'Review from user 1',
    ];

    $data2 = [
        'product_id' => $product->id,
        'user_id' => $user2->id,
        'rating' => 4,
        'content' => 'Review from user 2',
    ];

    $action = app(StoreReviewAction::class);
    $review1 = $action->handle(StoreReviewInput::fromArray($data1));
    $review2 = $action->handle(StoreReviewInput::fromArray($data2));

    expect($review1->id)->not->toBe($review2->id)
        ->and($review1->product_id)->toBe($product->id)
        ->and($review2->product_id)->toBe($product->id)
        ->and($review1->user_id)->toBe($user1->id)
        ->and($review2->user_id)->toBe($user2->id)
        ->and(Review::query()->count())->toBe(2);
});
