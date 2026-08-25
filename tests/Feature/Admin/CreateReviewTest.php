<?php

declare(strict_types=1);

use App\Actions\StoreReviewAction;
use App\Enums\Permission;
use App\Enums\ReviewStatus;
use App\Enums\Role as RoleEnum;
use App\Http\Controllers\Admin\ReviewController;
use App\Http\Requests\Admin\StoreReviewRequest;
use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use App\Queries\ReviewListQuery;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Laravel\get;
use function Pest\Laravel\post;

covers([
    ReviewController::class,
    StoreReviewRequest::class,
    StoreReviewAction::class,
    ReviewListQuery::class,
]);

uses()->group('review');

test('displays reviews list', function () {
    $response = actingAsSuperAdmin()->get(route('admin.reviews.index'));

    $response->assertOk()
        ->assertInertia(
            fn ($page) => $page
                ->component('admin/reviews/list')
        );
});

test('creates new review successfully', function () {
    $product = Product::factory()->create();
    $user = User::factory()->create();

    $reviewData = [
        'product_id' => $product->id,
        'user_id' => $user->id,
        'rating' => 5,
        'title' => 'Great Product',
        'content' => 'This is an excellent product. Highly recommended!',
    ];

    $response = actingAsSuperAdmin()->post(route('admin.reviews.store'), $reviewData);

    $response->assertRedirectBack()
        ->assertSessionHasNoErrors();

    assertDatabaseHas('reviews', [
        'product_id' => $product->id,
        'user_id' => $user->id,
        'rating' => 5,
        'title' => 'Great Product',
        'content' => 'This is an excellent product. Highly recommended!',
        'status' => ReviewStatus::Pending->value,
    ]);
});

test('creates review without optional fields', function () {
    $product = Product::factory()->create();
    $user = User::factory()->create();

    $reviewData = [
        'product_id' => $product->id,
        'user_id' => $user->id,
        'rating' => 4,
        'content' => 'Good product overall.',
    ];

    $response = actingAsSuperAdmin()->post(route('admin.reviews.store'), $reviewData);

    $response->assertSessionHasNoErrors();

    assertDatabaseHas('reviews', [
        'product_id' => $product->id,
        'user_id' => $user->id,
        'rating' => 4,
        'title' => null,
        'content' => 'Good product overall.',
        'status' => ReviewStatus::Pending->value,
    ]);
});

test('validates required fields', function () {
    $response = actingAsSuperAdmin()->post(route('admin.reviews.store'), []);

    $response->assertRedirectBack()
        ->assertInvalid(['product_id', 'user_id', 'rating', 'content']);
});

test('validates rating range', function () {
    $product = Product::factory()->create();
    $user = User::factory()->create();

    $response = actingAsSuperAdmin()->post(route('admin.reviews.store'), [
        'product_id' => $product->id,
        'user_id' => $user->id,
        'rating' => 6,
        'content' => 'Invalid rating',
    ]);

    $response->assertRedirectBack()
        ->assertInvalid('rating');

    $response = actingAsSuperAdmin()->post(route('admin.reviews.store'), [
        'product_id' => $product->id,
        'user_id' => $user->id,
        'rating' => 0,
        'content' => 'Invalid rating',
    ]);

    $response->assertRedirectBack()
        ->assertInvalid('rating');
});

test('validates product exists', function () {
    $user = User::factory()->create();

    $response = actingAsSuperAdmin()->post(route('admin.reviews.store'), [
        'product_id' => 99999,
        'user_id' => $user->id,
        'rating' => 5,
        'content' => 'Review content',
    ]);

    $response->assertRedirectBack()
        ->assertInvalid('product_id');
});

test('validates user exists', function () {
    $product = Product::factory()->create();

    $response = actingAsSuperAdmin()->post(route('admin.reviews.store'), [
        'product_id' => $product->id,
        'user_id' => 99999,
        'rating' => 5,
        'content' => 'Review content',
    ]);

    $response->assertRedirectBack()
        ->assertInvalid('user_id');
});

test('validates unique review per user per product', function () {
    $product = Product::factory()->create();
    $user = User::factory()->create();

    Review::factory()->create([
        'product_id' => $product->id,
        'user_id' => $user->id,
    ]);

    $reviewData = [
        'product_id' => $product->id,
        'user_id' => $user->id,
        'rating' => 5,
        'content' => 'Duplicate review',
    ];

    $response = actingAsSuperAdmin()->post(route('admin.reviews.store'), $reviewData);

    $response->assertRedirectBack()
        ->assertInvalid('product_id');

    expect(Review::query()->where('product_id', $product->id)
        ->where('user_id', $user->id)
        ->count())->toBe(1);
});

test('validates content max length', function () {
    $product = Product::factory()->create();
    $user = User::factory()->create();

    $response = actingAsSuperAdmin()->post(route('admin.reviews.store'), [
        'product_id' => $product->id,
        'user_id' => $user->id,
        'rating' => 5,
        'content' => str_repeat('a', 5001),
    ]);

    $response->assertRedirectBack()
        ->assertInvalid('content');
});

test('validates title max length', function () {
    $product = Product::factory()->create();
    $user = User::factory()->create();

    $response = actingAsSuperAdmin()->post(route('admin.reviews.store'), [
        'product_id' => $product->id,
        'user_id' => $user->id,
        'rating' => 5,
        'title' => str_repeat('a', 256),
        'content' => 'Review content',
    ]);

    $response->assertRedirectBack()
        ->assertInvalid('title');
});

test('requires authentication', function () {
    $response = get(route('admin.reviews.index'));

    $response->assertRedirect(route('admin.login'));

    $product = Product::factory()->create();
    $user = User::factory()->create();

    $response = post(route('admin.reviews.store'), [
        'product_id' => $product->id,
        'user_id' => $user->id,
        'rating' => 5,
        'content' => 'Review content',
    ]);

    $response->assertRedirect(route('admin.login'));
});

test('requires reviews.create permission', function () {
    $role = Role::query()->where(['name' => RoleEnum::Admin])->firstOrFail();
    $role->givePermissionTo(Permission::ReviewsManage);

    $product = Product::factory()->create();
    $user = User::factory()->create();

    $reviewData = [
        'product_id' => $product->id,
        'user_id' => $user->id,
        'rating' => 5,
        'content' => 'Review content',
    ];

    $response = actingAsAdmin()->post(route('admin.reviews.store'), $reviewData);

    $response->assertRedirectBack()
        ->assertSessionHasNoErrors();

    assertDatabaseHas('reviews', [
        'product_id' => $product->id,
        'user_id' => $user->id,
    ]);

    $role->revokePermissionTo(Permission::ReviewsManage);

    $product2 = Product::factory()->create();
    $user2 = User::factory()->create();

    $response = actingAsAdmin()->post(route('admin.reviews.store'), [
        'product_id' => $product2->id,
        'user_id' => $user2->id,
        'rating' => 5,
        'content' => 'Forbidden review',
    ]);

    $response->assertForbidden();

    assertDatabaseMissing('reviews', [
        'product_id' => $product2->id,
        'user_id' => $user2->id,
    ]);
});
