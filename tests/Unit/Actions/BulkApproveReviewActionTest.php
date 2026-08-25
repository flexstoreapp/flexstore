<?php

declare(strict_types=1);

namespace Tests\Unit\Actions\Review;

use App\Actions\BulkApproveReviewAction;
use App\Enums\ReviewStatus;
use App\Models\Review;

use function Pest\Laravel\assertDatabaseHas;

covers(BulkApproveReviewAction::class);

uses()->group('actions', 'review');

test('approves multiple reviews by their ids', function () {
    $pendingReview1 = Review::factory()->pending()->create();
    $pendingReview2 = Review::factory()->pending()->create();
    $pendingReview3 = Review::factory()->pending()->create();

    $ids = [$pendingReview1->id, $pendingReview2->id, $pendingReview3->id];

    $action = new BulkApproveReviewAction();
    $updatedCount = $action->handle($ids);

    expect($updatedCount)->toBe(3);

    foreach ($ids as $id) {
        assertDatabaseHas('reviews', [
            'id' => $id,
            'status' => ReviewStatus::Approved->value,
        ]);
    }
});

test('updates only existing reviews when mixed with non-existent ids', function () {
    $pendingReview1 = Review::factory()->pending()->create();
    $pendingReview2 = Review::factory()->pending()->create();
    $mixedIds = [$pendingReview1->id, $pendingReview2->id, 9999, 10000];

    $action = new BulkApproveReviewAction();
    $updatedCount = $action->handle($mixedIds);

    expect($updatedCount)->toBe(2);

    assertDatabaseHas('reviews', [
        'id' => $pendingReview1->id,
        'status' => ReviewStatus::Approved->value,
    ]);
    assertDatabaseHas('reviews', [
        'id' => $pendingReview2->id,
        'status' => ReviewStatus::Approved->value,
    ]);
});

test('handles empty array gracefully', function () {
    Review::factory()->pending()->count(3)->create();

    $action = new BulkApproveReviewAction();
    $updatedCount = $action->handle([]);

    expect($updatedCount)->toBe(0);
});

test('returns zero when no reviews found', function () {
    $action = new BulkApproveReviewAction();
    $updatedCount = $action->handle([999, 1000]);

    expect($updatedCount)->toBe(0);
});

test('approves reviews with different initial statuses', function () {
    $pendingReview = Review::factory()->pending()->create();
    $rejectedReview = Review::factory()->rejected()->create();
    $approvedReview = Review::factory()->approved()->create();

    $ids = [$pendingReview->id, $rejectedReview->id, $approvedReview->id];

    $action = new BulkApproveReviewAction();
    $updatedCount = $action->handle($ids);

    expect($updatedCount)->toBe(3);

    foreach ($ids as $id) {
        assertDatabaseHas('reviews', [
            'id' => $id,
            'status' => ReviewStatus::Approved->value,
        ]);
    }
});
