<?php

declare(strict_types=1);

use App\Models\Product;
use App\Models\Review;
use App\Queries\ReviewListQuery;
use Illuminate\Pagination\LengthAwarePaginator;

covers(ReviewListQuery::class);

uses()->group('queries', 'review');

test('returns paginated reviews', function () {
    Review::factory()->count(20)->create();

    $query = app(ReviewListQuery::class);
    $result = $query->execute(perPage: 15);

    expect($result)->toBeInstanceOf(LengthAwarePaginator::class)
        ->and($result)->toHaveCount(15)
        ->and($result->total())->toBe(20);
});

test('returns empty paginator when no reviews exist', function () {
    $query = app(ReviewListQuery::class);
    $result = $query->execute();

    expect($result)->toBeEmpty();
});

test('loads product relationship with featured media', function () {
    $product = Product::factory()->withMedia(1)->create();
    Review::factory()->create(['product_id' => $product->id]);

    $query = app(ReviewListQuery::class);
    $result = $query->execute();

    $review = $result->first();

    expect($review->relationLoaded('product'))->toBeTrue()
        ->and($review->product->featured_media)->not->toBeNull();
});

test('hides product media from results', function () {
    Review::factory()->create();

    $query = app(ReviewListQuery::class);
    $result = $query->execute();

    $productArray = $result->first()->product->toArray();

    expect($productArray)->not->toHaveKey('media_gallery');
});

test('loads user relationship', function () {
    Review::factory()->create();

    $query = app(ReviewListQuery::class);
    $result = $query->execute();

    expect($result->first()->relationLoaded('user'))->toBeTrue();
});

test('respects per page parameter', function () {
    Review::factory()->count(10)->create();

    $query = app(ReviewListQuery::class);
    $result = $query->execute(perPage: 5);

    expect($result)->toHaveCount(5)
        ->and($result->total())->toBe(10);
});
