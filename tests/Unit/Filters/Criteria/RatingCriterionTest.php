<?php

declare(strict_types=1);

namespace Tests\Unit\Filters\Criteria;

use App\Filters\Criteria\RatingCriterion;
use App\Models\Product;
use App\Models\Review;

covers(RatingCriterion::class);

uses()->group('filters');

test('filters products with an average approved rating at or above the threshold', function () {
    $highlyRated = Product::factory()->create();
    $poorlyRated = Product::factory()->create();

    Review::factory()->approved()->create(['product_id' => $highlyRated->id, 'rating' => 5]);
    Review::factory()->approved()->create(['product_id' => $highlyRated->id, 'rating' => 4]);
    Review::factory()->approved()->create(['product_id' => $poorlyRated->id, 'rating' => 2]);

    $criterion = new RatingCriterion();
    $result = $criterion->apply(Product::query(), 4)->get();

    expect($result)->toHaveCount(1)
        ->and($result->first()->id)->toBe($highlyRated->id);
});

test('ignores non-approved reviews when averaging', function () {
    $product = Product::factory()->create();
    Review::factory()->pending()->create(['product_id' => $product->id, 'rating' => 5]);

    $criterion = new RatingCriterion();

    expect($criterion->apply(Product::query(), 4)->count())->toBe(0);
});

test('supports fractional thresholds', function () {
    $product = Product::factory()->create();
    Review::factory()->approved()->create(['product_id' => $product->id, 'rating' => 5]);
    Review::factory()->approved()->create(['product_id' => $product->id, 'rating' => 4]);

    $criterion = new RatingCriterion();

    expect($criterion->apply(Product::query(), 4.5)->count())->toBe(1)
        ->and($criterion->apply(Product::query(), 4.6)->count())->toBe(0);
});

test('canApply returns true only for positive numeric values', function () {
    $criterion = new RatingCriterion();

    expect($criterion->canApply(4))->toBeTrue()
        ->and($criterion->canApply('3.5'))->toBeTrue();
});

test('canApply returns false for zero, null, empty or non-numeric values', function () {
    $criterion = new RatingCriterion();

    expect($criterion->canApply(0))->toBeFalse()
        ->and($criterion->canApply(null))->toBeFalse()
        ->and($criterion->canApply(''))->toBeFalse()
        ->and($criterion->canApply('abc'))->toBeFalse();
});
