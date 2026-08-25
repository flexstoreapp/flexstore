<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Review;

use App\Actions\BulkDestroyReviewAction;
use App\Enums\Permission;
use App\Enums\Role as RoleEnum;
use App\Http\Controllers\Admin\BulkReviewController;
use App\Http\Requests\Admin\BulkDestroyReviewRequest;
use App\Models\Review;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Laravel\delete;

covers(BulkReviewController::class, BulkDestroyReviewRequest::class, BulkDestroyReviewAction::class);

uses()->group('review');

test('bulk deletes reviews', function () {
    $reviews = Review::factory()->count(3)->create();
    $ids = $reviews->pluck('id')->all();

    $response = actingAsSuperAdmin()->delete(route('admin.reviews.bulk.destroy'), [
        'ids' => $ids,
    ]);

    $response->assertRedirectBack()
        ->assertSessionHasNoErrors();

    foreach ($ids as $id) {
        assertDatabaseMissing('reviews', ['id' => $id]);
    }
});

test('requires at least one review id', function () {
    $response = actingAsSuperAdmin()->delete(route('admin.reviews.bulk.destroy'), [
        'ids' => [],
    ]);

    $response->assertRedirectBack()
        ->assertInvalid('ids');
});

test('validates that ids exist', function () {
    $review = Review::factory()->create();
    $nonExistentId = 9999;

    $response = actingAsSuperAdmin()->delete(route('admin.reviews.bulk.destroy'), [
        'ids' => [$review->id, $nonExistentId],
    ]);

    $response->assertRedirectBack()
        ->assertInvalid('ids');
});

test('requires authentication', function () {
    $reviews = Review::factory()->count(2)->create();

    $response = delete(route('admin.reviews.bulk.destroy'), [
        'ids' => $reviews->pluck('id')->all(),
    ]);

    $response->assertRedirect(route('admin.login'));
});

test('requires reviews.delete permission', function () {
    $role = Role::query()->where(['name' => RoleEnum::Admin])->firstOrFail();
    $reviews = Review::factory()->count(2)->create();
    $ids = $reviews->pluck('id')->all();

    $response = actingAsAdmin()->delete(route('admin.reviews.bulk.destroy'), [
        'ids' => $ids,
    ]);

    $response->assertRedirectBack()
        ->assertSessionHasNoErrors();

    foreach ($reviews as $review) {
        assertDatabaseMissing('reviews', [
            'id' => $review->id,
        ]);
    }

    $role->revokePermissionTo(Permission::ReviewsDelete);

    $moreReviews = Review::factory()->count(2)->create();

    $response = actingAsAdmin()->delete(route('admin.reviews.bulk.destroy'), [
        'ids' => $moreReviews->pluck('id')->all(),
    ]);

    $response->assertForbidden();

    foreach ($moreReviews as $review) {
        assertDatabaseHas('reviews', [
            'id' => $review->id,
        ]);
    }

    $role->givePermissionTo(Permission::ReviewsDelete);
});
