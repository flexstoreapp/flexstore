<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Review;

use App\Enums\Permission;
use App\Enums\ReviewStatus;
use App\Enums\Role as RoleEnum;
use App\Http\Controllers\Admin\ReviewController;
use App\Http\Requests\Admin\IndexReviewRequest;
use App\Models\Product;
use App\Models\Review;
use App\Queries\ReviewListQuery;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\get;

covers(ReviewController::class, ReviewListQuery::class, IndexReviewRequest::class);

uses()->group('review');

test('displays reviews list page', function () {
    Review::factory()->count(3)->create();

    $response = actingAsSuperAdmin()->get(route('admin.reviews.index'));

    $response->assertOk()
        ->assertInertia(
            fn ($page) => $page
                ->component('admin/reviews/list')
                ->has('reviews.data', 3)
                ->where('filters.query', null)
                ->where('filters.product', null)
                ->where('filters.status', null)
                ->where('filters.rating', null)
                ->where('filters.sort', 'created_at')
                ->where('filters.direction', 'desc')
        );
});

test('filters reviews by query term', function () {
    $product1 = Product::factory()->create(['title' => 'Test Product']);
    $product2 = Product::factory()->create(['title' => 'Another Product']);

    $review1 = Review::factory()->create(['product_id' => $product1->id, 'content' => 'Great product']);
    $review2 = Review::factory()->create(['product_id' => $product2->id, 'content' => 'Not so good']);

    $response = actingAsSuperAdmin()->get(route('admin.reviews.index', ['query' => 'Great']));

    $response->assertOk()
        ->assertInertia(
            fn ($page) => $page
                ->component('admin/reviews/list')
                ->where('filters.query', 'Great')
        );
});

test('filters reviews by product', function () {
    $product1 = Product::factory()->create();
    $product2 = Product::factory()->create();

    Review::factory()->create(['product_id' => $product1->id]);
    Review::factory()->create(['product_id' => $product2->id]);

    $response = actingAsSuperAdmin()->get(route('admin.reviews.index', ['product' => (string) $product1->id]));

    $response->assertOk()
        ->assertInertia(
            fn ($page) => $page
                ->component('admin/reviews/list')
                ->where('filters.product', (string) $product1->id)
        );
});

test('filters reviews by status', function () {
    Review::factory()->pending()->create();
    Review::factory()->approved()->create();
    Review::factory()->rejected()->create();

    $response = actingAsSuperAdmin()->get(route('admin.reviews.index', ['status' => ReviewStatus::Approved->value]));

    $response->assertOk()
        ->assertInertia(
            fn ($page) => $page
                ->component('admin/reviews/list')
                ->where('filters.status', ReviewStatus::Approved->value)
        );
});

test('filters reviews by rating', function () {
    Review::factory()->create(['rating' => 5]);
    Review::factory()->create(['rating' => 3]);
    Review::factory()->create(['rating' => 1]);

    $response = actingAsSuperAdmin()->get(route('admin.reviews.index', ['rating' => '5']));

    $response->assertOk()
        ->assertInertia(
            fn ($page) => $page
                ->component('admin/reviews/list')
                ->where('filters.rating', '5')
        );
});

test('sorts reviews by created_at', function () {
    Review::factory()->create(['created_at' => now()->subDays(2)]);
    Review::factory()->create(['created_at' => now()->subDays(1)]);

    $response = actingAsSuperAdmin()->get(route('admin.reviews.index', [
        'sort' => 'created_at',
        'direction' => 'desc',
    ]));

    $response->assertOk()
        ->assertInertia(
            fn ($page) => $page
                ->component('admin/reviews/list')
                ->where('filters.sort', 'created_at')
                ->where('filters.direction', 'desc')
        );
});

test('paginates reviews with custom per_page', function () {
    Review::factory()->count(20)->create();

    $response = actingAsSuperAdmin()->get(route('admin.reviews.index', ['per_page' => 5]));

    $response->assertOk()
        ->assertInertia(
            fn ($page) => $page
                ->component('admin/reviews/list')
                ->has('reviews.data', 5)
        );
});

test('requires authentication', function () {
    $response = get(route('admin.reviews.index'));

    $response->assertRedirect(route('admin.login'));
});

test('requires reviews.view permission', function () {
    $role = Role::query()->where(['name' => RoleEnum::Admin])->firstOrFail();
    $role->revokePermissionTo(Permission::ReviewsView);

    $response = actingAsAdmin()->get(route('admin.reviews.index'));

    $response->assertForbidden();

    $role->givePermissionTo(Permission::ReviewsView);
});
