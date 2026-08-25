<?php

declare(strict_types=1);

use App\Actions\UpdateReviewAction;
use App\Enums\Permission;
use App\Enums\Role as RoleEnum;
use App\Http\Controllers\Admin\ReviewController;
use App\Http\Requests\Admin\UpdateReviewRequest;
use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Laravel\patch;

covers(ReviewController::class, UpdateReviewRequest::class, UpdateReviewAction::class);

uses()->group('review');

test('updates review successfully', function () {
    $product = Product::factory()->create();
    $user = User::factory()->create();
    $review = Review::factory()->create([
        'product_id' => $product->id,
        'user_id' => $user->id,
        'rating' => 3,
        'title' => 'Original Title',
        'content' => 'Original content',
    ]);

    $updateData = [
        'rating' => 5,
        'title' => 'Updated Title',
        'content' => 'Updated content',
    ];

    $response = actingAsSuperAdmin()->patch(route('admin.reviews.update', $review), $updateData);

    $response->assertRedirectBack()
        ->assertSessionHasNoErrors();

    $review->refresh();
    expect($review->rating)->toBe(5)
        ->and($review->title)->toBe('Updated Title')
        ->and($review->content)->toBe('Updated content');

    assertDatabaseHas('reviews', [
        'id' => $review->id,
        'rating' => 5,
        'title' => 'Updated Title',
        'content' => 'Updated content',
    ]);
});

test('updates only rating', function () {
    $product = Product::factory()->create();
    $user = User::factory()->create();
    $review = Review::factory()->create([
        'product_id' => $product->id,
        'user_id' => $user->id,
        'rating' => 3,
        'title' => 'Original Title',
        'content' => 'Original content',
    ]);

    $updateData = [
        'rating' => 5,
    ];

    $response = actingAsSuperAdmin()->patch(route('admin.reviews.update', $review), $updateData);

    $response->assertRedirectBack()
        ->assertSessionHasNoErrors();

    $review->refresh();
    expect($review->rating)->toBe(5)
        ->and($review->title)->toBe('Original Title')
        ->and($review->content)->toBe('Original content');
});

test('updates only content', function () {
    $product = Product::factory()->create();
    $user = User::factory()->create();
    $review = Review::factory()->create([
        'product_id' => $product->id,
        'user_id' => $user->id,
        'rating' => 4,
        'title' => 'Original Title',
        'content' => 'Original content',
    ]);

    $updateData = [
        'content' => 'New content',
    ];

    $response = actingAsSuperAdmin()->patch(route('admin.reviews.update', $review), $updateData);

    $response->assertRedirectBack()
        ->assertSessionHasNoErrors();

    $review->refresh();
    expect($review->content)->toBe('New content')
        ->and($review->rating)->toBe(4)
        ->and($review->title)->toBe('Original Title');
});

test('validates rating range', function () {
    $product = Product::factory()->create();
    $user = User::factory()->create();
    $review = Review::factory()->create([
        'product_id' => $product->id,
        'user_id' => $user->id,
    ]);

    $response = actingAsSuperAdmin()->patch(route('admin.reviews.update', $review), [
        'rating' => 6,
    ]);

    $response->assertRedirectBack()
        ->assertInvalid('rating');

    $response = actingAsSuperAdmin()->patch(route('admin.reviews.update', $review), [
        'rating' => 0,
    ]);

    $response->assertRedirectBack()
        ->assertInvalid('rating');
});

test('validates content max length', function () {
    $product = Product::factory()->create();
    $user = User::factory()->create();
    $review = Review::factory()->create([
        'product_id' => $product->id,
        'user_id' => $user->id,
    ]);

    $response = actingAsSuperAdmin()->patch(route('admin.reviews.update', $review), [
        'content' => str_repeat('a', 5001),
    ]);

    $response->assertRedirectBack()
        ->assertInvalid('content');
});

test('validates title max length', function () {
    $product = Product::factory()->create();
    $user = User::factory()->create();
    $review = Review::factory()->create([
        'product_id' => $product->id,
        'user_id' => $user->id,
    ]);

    $response = actingAsSuperAdmin()->patch(route('admin.reviews.update', $review), [
        'title' => str_repeat('a', 256),
    ]);

    $response->assertRedirectBack()
        ->assertInvalid('title');
});

test('validates content is required when provided', function () {
    $product = Product::factory()->create();
    $user = User::factory()->create();
    $review = Review::factory()->create([
        'product_id' => $product->id,
        'user_id' => $user->id,
    ]);

    $response = actingAsSuperAdmin()->patch(route('admin.reviews.update', $review), [
        'content' => '',
    ]);

    $response->assertRedirectBack()
        ->assertInvalid('content');
});

test('requires authentication', function () {
    $product = Product::factory()->create();
    $user = User::factory()->create();
    $review = Review::factory()->create([
        'product_id' => $product->id,
        'user_id' => $user->id,
    ]);

    $response = patch(route('admin.reviews.update', $review), [
        'rating' => 5,
        'content' => 'Updated content',
    ]);

    $response->assertRedirect(route('admin.login'));
});

test('requires reviews.update permission', function () {
    $role = Role::query()->where(['name' => RoleEnum::Admin])->firstOrFail();
    $product = Product::factory()->create();
    $user = User::factory()->create();
    $review = Review::factory()->create([
        'product_id' => $product->id,
        'user_id' => $user->id,
        'rating' => 3,
        'content' => 'Original content',
    ]);

    $response = actingAsAdmin()->patch(route('admin.reviews.update', $review), [
        'rating' => 5,
        'content' => 'Updated content',
    ]);

    $response->assertRedirectBack();

    assertDatabaseHas('reviews', [
        'id' => $review->id,
        'rating' => 5,
        'content' => 'Updated content',
    ]);

    $role->revokePermissionTo(Permission::ReviewsManage);

    $response = actingAsAdmin()->patch(route('admin.reviews.update', $review), [
        'rating' => 4,
        'content' => 'Forbidden update',
    ]);

    $response->assertForbidden();

    assertDatabaseMissing('reviews', [
        'id' => $review->id,
        'content' => 'Forbidden update',
    ]);
});
