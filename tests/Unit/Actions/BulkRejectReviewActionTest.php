<?php

declare(strict_types=1);

namespace Tests\Unit\Actions\Review;

use App\Actions\BulkRejectReviewAction;
use App\Enums\ReviewStatus;
use App\Models\Review;

use function Pest\Laravel\assertDatabaseHas;

covers(BulkRejectReviewAction::class);

uses()->group('actions', 'review');

test('rejects multiple reviews by their ids', function () {
    $pendingReview1 = Review::factory()->pending()->create();
    $pendingReview2 = Review::factory()->pending()->create();
    $pendingReview3 = Review::factory()->pending()->create();

    $ids = [$pendingReview1->id, $pendingReview2->id, $pendingReview3->id];

    $action = new BulkRejectReviewAction();
    $updatedCount = $action->handle($ids);

    expect($updatedCount)->toBe(3);

    foreach ($ids as $id) {
        assertDatabaseHas('reviews', [
            'id' => $id,
            'status' => ReviewStatus::Rejected->value,
        ]);
    }
});

test('updates only existing reviews when mixed with non-existent ids', function () {
    $pendingReview1 = Review::factory()->pending()->create();
    $pendingReview2 = Review::factory()->pending()->create();
    $mixedIds = [$pendingReview1->id, $pendingReview2->id, 9999, 10000];

    $action = new BulkRejectReviewAction();
    $updatedCount = $action->handle($mixedIds);

    expect($updatedCount)->toBe(2);

    assertDatabaseHas('reviews', [
        'id' => $pendingReview1->id,
        'status' => ReviewStatus::Rejected->value,
    ]);
    assertDatabaseHas('reviews', [
        'id' => $pendingReview2->id,
        'status' => ReviewStatus::Rejected->value,
    ]);
});

test('handles empty array gracefully', function () {
    Review::factory()->pending()->count(3)->create();

    $action = new BulkRejectReviewAction();
    $updatedCount = $action->handle([]);

    expect($updatedCount)->toBe(0);
});

test('returns zero when no reviews found', function () {
    $action = new BulkRejectReviewAction();
    $updatedCount = $action->handle([999, 1000]);

    expect($updatedCount)->toBe(0);
});

test('rejects reviews with different initial statuses', function () {
    $pendingReview = Review::factory()->pending()->create();
    $approvedReview = Review::factory()->approved()->create();
    $rejectedReview = Review::factory()->rejected()->create();

    $ids = [$pendingReview->id, $approvedReview->id, $rejectedReview->id];

    $action = new BulkRejectReviewAction();
    $updatedCount = $action->handle($ids);

    expect($updatedCount)->toBe(3);

    foreach ($ids as $id) {
        assertDatabaseHas('reviews', [
            'id' => $id,
            'status' => ReviewStatus::Rejected->value,
        ]);
    }
});
