<?php

declare(strict_types=1);

use App\Actions\UpdateReviewAction;
use App\DTOs\UpdateReviewInput;
use App\Models\Product;
use App\Models\Review;
use App\Models\User;

use function Pest\Laravel\assertDatabaseHas;

covers(UpdateReviewAction::class, UpdateReviewInput::class);

uses()->group('actions', 'review');

test('updates all review fields', function () {
    $product = Product::factory()->create();
    $user = User::factory()->create();
    $review = Review::factory()->create([
        'product_id' => $product->id,
        'user_id' => $user->id,
        'rating' => 3,
        'title' => 'Original Title',
        'content' => 'Original content',
    ]);

    $data = [
        'rating' => 5,
        'title' => 'Updated Title',
        'content' => 'Updated content',
    ];

    $action = new UpdateReviewAction();
    $result = $action->handle($review, UpdateReviewInput::fromArray($data));

    expect($result)->toBeInstanceOf(Review::class)
        ->and($result->id)->toBe($review->id)
        ->and($result->rating)->toBe(5)
        ->and($result->title)->toBe('Updated Title')
        ->and($result->content)->toBe('Updated content');

    assertDatabaseHas('reviews', [
        'id' => $review->id,
        'rating' => 5,
        'title' => 'Updated Title',
        'content' => 'Updated content',
    ]);
});

test('updates only rating', function () {
    $product = Product::factory()->create();
    $user = User::factory()->create();
    $review = Review::factory()->create([
        'product_id' => $product->id,
        'user_id' => $user->id,
        'rating' => 3,
        'title' => 'Original Title',
        'content' => 'Original content',
    ]);

    $data = [
        'rating' => 5,
    ];

    $action = new UpdateReviewAction();
    $result = $action->handle($review, UpdateReviewInput::fromArray($data));

    expect($result->rating)->toBe(5)
        ->and($result->title)->toBe('Original Title')
        ->and($result->content)->toBe('Original content');

    assertDatabaseHas('reviews', [
        'id' => $review->id,
        'rating' => 5,
        'title' => 'Original Title',
        'content' => 'Original content',
    ]);
});

test('updates only title', function () {
    $product = Product::factory()->create();
    $user = User::factory()->create();
    $review = Review::factory()->create([
        'product_id' => $product->id,
        'user_id' => $user->id,
        'rating' => 3,
        'title' => 'Original Title',
        'content' => 'Original content',
    ]);

    $data = [
        'title' => 'New Title',
    ];

    $action = new UpdateReviewAction();
    $result = $action->handle($review, UpdateReviewInput::fromArray($data));

    expect($result->title)->toBe('New Title')
        ->and($result->rating)->toBe(3)
        ->and($result->content)->toBe('Original content');

    assertDatabaseHas('reviews', [
        'id' => $review->id,
        'rating' => 3,
        'title' => 'New Title',
        'content' => 'Original content',
    ]);
});

test('updates only content', function () {
    $product = Product::factory()->create();
    $user = User::factory()->create();
    $review = Review::factory()->create([
        'product_id' => $product->id,
        'user_id' => $user->id,
        'rating' => 3,
        'title' => 'Original Title',
        'content' => 'Original content',
    ]);

    $data = [
        'content' => 'New content here',
    ];

    $action = new UpdateReviewAction();
    $result = $action->handle($review, UpdateReviewInput::fromArray($data));

    expect($result->content)->toBe('New content here')
        ->and($result->rating)->toBe(3)
        ->and($result->title)->toBe('Original Title');

    assertDatabaseHas('reviews', [
        'id' => $review->id,
        'rating' => 3,
        'title' => 'Original Title',
        'content' => 'New content here',
    ]);
});

test('updates title to null', function () {
    $product = Product::factory()->create();
    $user = User::factory()->create();
    $review = Review::factory()->create([
        'product_id' => $product->id,
        'user_id' => $user->id,
        'rating' => 3,
        'title' => 'Original Title',
        'content' => 'Original content',
    ]);

    $data = [
        'title' => null,
    ];

    $action = new UpdateReviewAction();
    $result = $action->handle($review, UpdateReviewInput::fromArray($data));

    expect($result->title)->toBeNull()
        ->and($result->rating)->toBe(3)
        ->and($result->content)->toBe('Original content');

    assertDatabaseHas('reviews', [
        'id' => $review->id,
        'title' => null,
    ]);
});

test('does not update fields when empty data provided', function () {
    $product = Product::factory()->create();
    $user = User::factory()->create();
    $review = Review::factory()->create([
        'product_id' => $product->id,
        'user_id' => $user->id,
        'rating' => 3,
        'title' => 'Original Title',
        'content' => 'Original content',
    ]);

    $data = [];

    $action = new UpdateReviewAction();
    $result = $action->handle($review, UpdateReviewInput::fromArray($data));

    expect($result->rating)->toBe(3)
        ->and($result->title)->toBe('Original Title')
        ->and($result->content)->toBe('Original content');

    assertDatabaseHas('reviews', [
        'id' => $review->id,
        'rating' => 3,
        'title' => 'Original Title',
        'content' => 'Original content',
    ]);
});

test('updates rating to minimum value', function () {
    $product = Product::factory()->create();
    $user = User::factory()->create();
    $review = Review::factory()->create([
        'product_id' => $product->id,
        'user_id' => $user->id,
        'rating' => 3,
        'title' => 'Original Title',
        'content' => 'Original content',
    ]);

    $data = [
        'rating' => 1,
    ];

    $action = new UpdateReviewAction();
    $result = $action->handle($review, UpdateReviewInput::fromArray($data));

    expect($result->rating)->toBe(1);
});

test('updates rating to maximum value', function () {
    $product = Product::factory()->create();
    $user = User::factory()->create();
    $review = Review::factory()->create([
        'product_id' => $product->id,
        'user_id' => $user->id,
        'rating' => 3,
        'title' => 'Original Title',
        'content' => 'Original content',
    ]);

    $data = [
        'rating' => 5,
    ];

    $action = new UpdateReviewAction();
    $result = $action->handle($review, UpdateReviewInput::fromArray($data));

    expect($result->rating)->toBe(5);
});

test('updates multiple reviews independently', function () {
    $product = Product::factory()->create();
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    $review1 = Review::factory()->create([
        'product_id' => $product->id,
        'user_id' => $user1->id,
        'rating' => 3,
        'title' => 'Original Title',
        'content' => 'Original content',
    ]);

    $review2 = Review::factory()->create([
        'product_id' => $product->id,
        'user_id' => $user2->id,
        'rating' => 2,
        'title' => 'Review 2 Title',
        'content' => 'Review 2 content',
    ]);

    $data1 = [
        'rating' => 4,
        'title' => 'Updated Review 1',
    ];

    $data2 = [
        'rating' => 5,
        'title' => 'Updated Review 2',
    ];

    $action = new UpdateReviewAction();
    $updated1 = $action->handle($review1, UpdateReviewInput::fromArray($data1));
    $updated2 = $action->handle($review2, UpdateReviewInput::fromArray($data2));

    expect($updated1->rating)->toBe(4)
        ->and($updated1->title)->toBe('Updated Review 1')
        ->and($updated2->rating)->toBe(5)
        ->and($updated2->title)->toBe('Updated Review 2');
});
