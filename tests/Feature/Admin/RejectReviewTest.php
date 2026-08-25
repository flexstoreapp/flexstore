<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Review;

use App\Actions\BulkRejectReviewAction;
use App\Enums\Permission;
use App\Enums\ReviewStatus;
use App\Enums\Role as RoleEnum;
use App\Http\Controllers\Admin\ReviewRejectController;
use App\Http\Requests\Admin\BulkRejectReviewRequest;
use App\Models\Review;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\post;

covers(ReviewRejectController::class, BulkRejectReviewRequest::class, BulkRejectReviewAction::class);

uses()->group('review');

test('bulk rejects reviews', function () {
    $reviews = Review::factory()->pending()->count(3)->create();
    $ids = $reviews->pluck('id')->all();

    $response = actingAsSuperAdmin()->post(route('admin.reviews.reject'), [
        'ids' => $ids,
    ]);

    $response->assertRedirectBack()
        ->assertSessionHasNoErrors();

    foreach ($ids as $id) {
        assertDatabaseHas('reviews', [
            'id' => $id,
            'status' => ReviewStatus::Rejected->value,
        ]);
    }
});

test('requires at least one review id', function () {
    $response = actingAsSuperAdmin()->post(route('admin.reviews.reject'), [
        'ids' => [],
    ]);

    $response->assertRedirectBack()
        ->assertInvalid('ids');
});

test('validates that ids exist', function () {
    $review = Review::factory()->create();
    $nonExistentId = 9999;

    $response = actingAsSuperAdmin()->post(route('admin.reviews.reject'), [
        'ids' => [$review->id, $nonExistentId],
    ]);

    $response->assertRedirectBack()
        ->assertInvalid('ids');
});

test('requires authentication', function () {
    $reviews = Review::factory()->count(2)->create();

    $response = post(route('admin.reviews.reject'), [
        'ids' => $reviews->pluck('id')->all(),
    ]);

    $response->assertRedirect(route('admin.login'));
});

test('requires reviews.update permission', function () {
    $role = Role::query()->where(['name' => RoleEnum::Admin])->firstOrFail();
    $reviews = Review::factory()->pending()->count(2)->create();
    $ids = $reviews->pluck('id')->all();

    $response = actingAsAdmin()->post(route('admin.reviews.reject'), [
        'ids' => $ids,
    ]);

    $response->assertRedirectBack()
        ->assertSessionHasNoErrors();

    foreach ($reviews as $review) {
        assertDatabaseHas('reviews', [
            'id' => $review->id,
            'status' => ReviewStatus::Rejected->value,
        ]);
    }

    $role->revokePermissionTo(Permission::ReviewsManage);

    $moreReviews = Review::factory()->pending()->count(2)->create();

    $response = actingAsAdmin()->post(route('admin.reviews.reject'), [
        'ids' => $moreReviews->pluck('id')->all(),
    ]);

    $response->assertForbidden();

    foreach ($moreReviews as $review) {
        assertDatabaseHas('reviews', [
            'id' => $review->id,
            'status' => ReviewStatus::Pending->value,
        ]);
    }

    $role->givePermissionTo(Permission::ReviewsManage);
});
