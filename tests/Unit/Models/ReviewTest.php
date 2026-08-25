<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Enums\ReviewStatus;
use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

covers(Review::class);

uses()->group('models', 'review');

test('has factory', function () {
    expect(Review::factory())->toBeInstanceOf(Factory::class);
});

test('touches product relationship', function () {
    $review = new Review();

    expect($review->getTouchedRelations())->toBe(['product']);
});

test('casts attributes correctly', function () {
    $review = new Review();
    $casts = $review->casts();

    expect($casts)
        ->toBeArray()
        ->toHaveKey('status', ReviewStatus::class);
});

test('has product relationship', function () {
    $product = Product::factory()->create();
    $review = Review::factory()->create([
        'product_id' => $product->id,
    ]);

    expect($review->product)->toBeInstanceOf(Product::class)
        ->and($review->product->id)->toBe($product->id);
});

test('has user relationship', function () {
    $user = User::factory()->create();
    $review = Review::factory()->create([
        'user_id' => $user->id,
    ]);

    expect($review->user)->toBeInstanceOf(User::class)
        ->and($review->user->id)->toBe($user->id);
});

test('casts status enum correctly', function (ReviewStatus $status) {
    $review = Review::factory()->create(['status' => $status]);

    expect($review->status)->toBeInstanceOf(ReviewStatus::class)
        ->and($review->status)->toBe($status);
})->with([
    ReviewStatus::Pending,
    ReviewStatus::Approved,
    ReviewStatus::Rejected,
]);
