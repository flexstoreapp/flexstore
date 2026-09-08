<?php

declare(strict_types=1);

use App\Enums\Permission;
use App\Enums\ReviewStatus;
use App\Http\Controllers\Api\V1\Admin\BulkReviewController;
use App\Http\Controllers\Api\V1\Admin\ReviewApproveController;
use App\Http\Controllers\Api\V1\Admin\ReviewController;
use App\Http\Controllers\Api\V1\Admin\ReviewRejectController;
use App\Http\Resources\Api\V1\Admin\ReviewResource;
use App\Models\Product;
use App\Models\Review;
use App\Models\User;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Laravel\deleteJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\patchJson;
use function Pest\Laravel\postJson;

covers(
    ReviewController::class,
    BulkReviewController::class,
    ReviewApproveController::class,
    ReviewRejectController::class,
    ReviewResource::class,
);

uses()->group('api', 'admin-api');

test('the review index is paginated and filterable', function (): void {
    actingAsApiAdmin(userWithPermissions([Permission::ReviewsView]));

    Review::factory()->pending()->create(['content' => 'Sturdy and light', 'rating' => 5]);
    Review::factory()->approved()->create(['content' => 'Arrived damaged', 'rating' => 2]);

    getJson('/api/v1/admin/reviews?per_page=1')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('meta.per_page', 1)
        ->assertJsonPath('meta.total', 2)
        ->assertJsonStructure([
            'data' => [['id', 'rating', 'title', 'content', 'status', 'product' => ['id', 'title'], 'user' => ['id', 'name', 'email']]],
        ]);

    getJson('/api/v1/admin/reviews?status=' . ReviewStatus::Pending->value)
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.status', ReviewStatus::Pending->value);

    getJson('/api/v1/admin/reviews?rating=2')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.rating', 2);
});

test('a staff member without the view permission cannot list reviews', function (): void {
    actingAsApiAdmin(userWithPermissions([Permission::DashboardView]));

    getJson('/api/v1/admin/reviews')->assertForbidden();
});

test('a single review is shown with its product and customer', function (): void {
    actingAsApiAdmin(userWithPermissions([Permission::ReviewsView]));

    $product = Product::factory()->create();
    $customer = User::factory()->create();
    $review = Review::factory()->approved()->create([
        'product_id' => $product->id,
        'user_id' => $customer->id,
        'rating' => 5,
        'title' => 'Excellent',
        'content' => 'Exactly as described.',
    ]);

    getJson('/api/v1/admin/reviews/' . $review->id)
        ->assertOk()
        ->assertJsonPath('id', $review->id)
        ->assertJsonPath('rating', 5)
        ->assertJsonPath('title', 'Excellent')
        ->assertJsonPath('content', 'Exactly as described.')
        ->assertJsonPath('status', ReviewStatus::Approved->value)
        ->assertJsonPath('product.id', $product->id)
        ->assertJsonPath('user.id', $customer->id)
        ->assertJsonStructure(['id', 'rating', 'title', 'content', 'status', 'product' => ['id', 'title'], 'user' => ['id', 'name', 'email']]);
});

test('a staff member without the view permission cannot show a review', function (): void {
    actingAsApiAdmin(userWithPermissions([Permission::DashboardView]));

    $review = Review::factory()->create();

    getJson('/api/v1/admin/reviews/' . $review->id)->assertForbidden();
});

test('showing a missing review returns not found', function (): void {
    actingAsApiAdmin(userWithPermissions([Permission::ReviewsView]));

    getJson('/api/v1/admin/reviews/999999')->assertNotFound();
});

test('a review is created for a customer', function (): void {
    actingAsApiAdmin(userWithPermissions([Permission::ReviewsManage]));

    $product = Product::factory()->create();
    $customer = User::factory()->create();

    postJson('/api/v1/admin/reviews', [
        'product_id' => $product->id,
        'user_id' => $customer->id,
        'rating' => 4,
        'title' => 'Solid',
        'content' => 'Does the job well.',
    ])
        ->assertCreated()
        ->assertJsonPath('rating', 4)
        ->assertJsonPath('status', ReviewStatus::Pending->value)
        ->assertJsonPath('product.id', $product->id)
        ->assertJsonPath('user.id', $customer->id);

    assertDatabaseHas('reviews', [
        'product_id' => $product->id,
        'user_id' => $customer->id,
        'rating' => 4,
    ]);
});

test('creating a review validates the payload', function (): void {
    actingAsApiAdmin(userWithPermissions([Permission::ReviewsManage]));

    postJson('/api/v1/admin/reviews', [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['product_id', 'user_id', 'rating', 'content']);
});

test('a review is updated with patch semantics', function (): void {
    actingAsApiAdmin(userWithPermissions([Permission::ReviewsManage]));

    $review = Review::factory()->approved()->create(['rating' => 3, 'title' => 'Keep me', 'content' => 'Old body']);

    patchJson('/api/v1/admin/reviews/' . $review->id, ['content' => 'New body'])
        ->assertOk()
        ->assertJsonPath('content', 'New body')
        ->assertJsonPath('title', 'Keep me')
        ->assertJsonPath('rating', 3);

    expect($review->refresh()->content)->toBe('New body');
});

test('a review is deleted', function (): void {
    actingAsApiAdmin(userWithPermissions([Permission::ReviewsDelete]));

    $review = Review::factory()->create();

    deleteJson('/api/v1/admin/reviews/' . $review->id)->assertNoContent();

    assertDatabaseMissing('reviews', ['id' => $review->id]);
});

test('reviews are bulk deleted', function (): void {
    actingAsApiAdmin(userWithPermissions([Permission::ReviewsDelete]));

    $reviews = Review::factory(2)->create();

    deleteJson('/api/v1/admin/reviews/bulk', ['ids' => $reviews->pluck('id')->all()])
        ->assertNoContent();

    foreach ($reviews as $review) {
        assertDatabaseMissing('reviews', ['id' => $review->id]);
    }
});

test('reviews are bulk approved', function (): void {
    actingAsApiAdmin(userWithPermissions([Permission::ReviewsManage]));

    $reviews = Review::factory(2)->pending()->create();

    postJson('/api/v1/admin/reviews/approve', ['ids' => $reviews->pluck('id')->all()])
        ->assertOk()
        ->assertJsonPath('updated', 2);

    foreach ($reviews as $review) {
        expect($review->refresh()->status)->toBe(ReviewStatus::Approved);
    }
});

test('reviews are bulk rejected', function (): void {
    actingAsApiAdmin(userWithPermissions([Permission::ReviewsManage]));

    $reviews = Review::factory(2)->pending()->create();

    postJson('/api/v1/admin/reviews/reject', ['ids' => $reviews->pluck('id')->all()])
        ->assertOk()
        ->assertJsonPath('updated', 2);

    foreach ($reviews as $review) {
        expect($review->refresh()->status)->toBe(ReviewStatus::Rejected);
    }
});

test('bulk approve rejects unknown review ids', function (): void {
    actingAsApiAdmin(userWithPermissions([Permission::ReviewsManage]));

    postJson('/api/v1/admin/reviews/approve', ['ids' => [99999]])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('ids');
});
