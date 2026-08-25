<?php

declare(strict_types=1);

use App\Enums\ReviewStatus;
use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use App\Queries\ProductReviewsQuery;

covers(ProductReviewsQuery::class);

uses()->group('storefront', 'product', 'review');

test('paginates approved reviews with a lean shape', function () {
    $product = Product::factory()->available()->create();
    $author = User::factory()->create(['name' => 'Ada Lovelace']);
    Review::factory()->for($product)->create([
        'user_id' => $author->id,
        'rating' => 5,
        'title' => 'Great',
        'content' => 'Loved it.',
        'status' => ReviewStatus::Approved,
    ]);
    Review::factory()->for($product)->create(['status' => ReviewStatus::Pending]);

    $result = app(ProductReviewsQuery::class)->execute($product, 10);

    expect($result['data'])->toHaveCount(1)
        ->and($result['data'][0])->toHaveKeys(['id', 'rating', 'title', 'content', 'created_at', 'user'])
        ->and($result['data'][0]['user']['name'])->toBe('Ada Lovelace')
        ->and($result['per_page'])->toBe(10);
});

test('excludes reviews for other products', function () {
    $product = Product::factory()->available()->create();
    $other = Product::factory()->available()->create();
    Review::factory()->for($other)->create(['status' => ReviewStatus::Approved]);

    expect(app(ProductReviewsQuery::class)->execute($product, 10)['data'])->toBeEmpty();
});
