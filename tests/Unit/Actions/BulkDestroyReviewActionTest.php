<?php

declare(strict_types=1);

namespace Tests\Unit\Actions\Review;

use App\Actions\BulkDestroyReviewAction;
use App\Models\Review;

use function Pest\Laravel\assertDatabaseMissing;

covers(BulkDestroyReviewAction::class);

uses()->group('actions', 'review');

test('deletes multiple reviews by their ids', function () {
    Review::factory()->count(5)->create();
    $reviewIds = Review::query()->limit(3)->pluck('id')->all();

    $action = new BulkDestroyReviewAction();
    $result = $action->handle($reviewIds);

    expect($result)->toBe(3)
        ->and(Review::query()->count())->toBe(2)
        ->and(Review::query()->whereIn('id', $reviewIds)->count())->toBe(0);
});

test('returns zero when no reviews found for deletion', function () {
    Review::factory()->count(5)->create();
    $nonExistingIds = [999, 1000];

    $action = new BulkDestroyReviewAction();
    $result = $action->handle($nonExistingIds);

    expect($result)->toBe(0)
        ->and(Review::query()->count())->toBe(5);
});

test('deletes only existing reviews when mixed with non-existent ids', function () {
    $reviews = Review::factory()->count(2)->create();
    $existingIds = $reviews->pluck('id')->all();
    $mixedIds = array_merge($existingIds, [9999, 10000]);

    $action = new BulkDestroyReviewAction();
    $deletedCount = $action->handle($mixedIds);

    expect($deletedCount)->toBe(2);

    foreach ($existingIds as $id) {
        assertDatabaseMissing('reviews', ['id' => $id]);
    }
});

test('handles empty array gracefully', function () {
    Review::factory()->count(3)->create();

    $action = new BulkDestroyReviewAction();
    $deletedCount = $action->handle([]);

    expect($deletedCount)->toBe(0)
        ->and(Review::query()->count())->toBe(3);
});

test('deletes reviews with different statuses', function () {
    $pendingReview = Review::factory()->pending()->create();
    $approvedReview = Review::factory()->approved()->create();
    $rejectedReview = Review::factory()->rejected()->create();

    $ids = [$pendingReview->id, $approvedReview->id, $rejectedReview->id];

    $action = new BulkDestroyReviewAction();
    $deletedCount = $action->handle($ids);

    expect($deletedCount)->toBe(3);

    foreach ($ids as $id) {
        assertDatabaseMissing('reviews', ['id' => $id]);
    }
});
