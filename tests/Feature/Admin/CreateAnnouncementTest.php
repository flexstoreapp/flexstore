<?php

declare(strict_types=1);

use App\Actions\StoreAnnouncementAction;
use App\Enums\Permission;
use App\Enums\Role as RoleEnum;
use App\Http\Controllers\Admin\AnnouncementController;
use App\Http\Requests\Admin\StoreAnnouncementRequest;
use App\Models\Announcement;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\post;

covers(
    AnnouncementController::class,
    StoreAnnouncementRequest::class,
    StoreAnnouncementAction::class
);

uses()->group('announcement');

test('shows announcements index page', function () {
    Announcement::factory()->count(3)->create();

    $response = actingAsSuperAdmin()->get(route('admin.storefront.announcements.index'));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/storefront/announcements')
            ->has('announcements', 3));
});

test('shows announcements ordered by sort_order', function () {
    $announcement1 = Announcement::factory()->create(['sort_order' => 2]);
    $announcement2 = Announcement::factory()->create(['sort_order' => 0]);
    $announcement3 = Announcement::factory()->create(['sort_order' => 1]);

    $response = actingAsSuperAdmin()->get(route('admin.storefront.announcements.index'));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/storefront/announcements')
            ->where('announcements.0.id', $announcement2->id)
            ->where('announcements.1.id', $announcement3->id)
            ->where('announcements.2.id', $announcement1->id));
});

test('requires authentication for index', function () {
    $response = get(route('admin.storefront.announcements.index'));

    $response->assertRedirect(route('admin.login'));
});

test('requires storefront view permission for index', function () {
    $role = Role::query()->where(['name' => RoleEnum::Admin])->firstOrFail();
    $role->revokePermissionTo(Permission::StorefrontView);

    $response = actingAsAdmin()->get(route('admin.storefront.announcements.index'));

    $response->assertForbidden();
});

test('index is accessible with view-only permission', function () {
    actingAs(userWithPermissions([Permission::StorefrontView]))
        ->get(route('admin.storefront.announcements.index'))
        ->assertOk();
});

test('shows create form', function () {
    $response = actingAsSuperAdmin()->get(route('admin.storefront.announcements.create'));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/storefront/announcements/create'));
});

test('requires authentication for create form', function () {
    $response = get(route('admin.storefront.announcements.create'));

    $response->assertRedirect(route('admin.login'));
});

test('requires storefront update permission for create form', function () {
    $role = Role::query()->where(['name' => RoleEnum::Admin])->firstOrFail();
    $role->revokePermissionTo(Permission::StorefrontUpdate);

    $response = actingAsAdmin()->get(route('admin.storefront.announcements.create'));

    $response->assertForbidden();
});

test('creates announcement successfully', function () {
    $response = actingAsSuperAdmin()->post(route('admin.storefront.announcements.store'), [
        'content' => 'Free shipping on orders over $50!',
        'url' => 'https://example.com/promo',
        'is_active' => true,
        'starts_at' => '2026-01-01 00:00:00',
        'ends_at' => '2026-12-31 23:59:59',
    ]);

    $response->assertSessionHasNoErrors();

    expect(Announcement::query()->count())->toBe(1);

    $announcement = Announcement::query()->first();

    $response->assertRedirect(route('admin.storefront.announcements.edit', $announcement));

    expect($announcement)
        ->content->toBe('Free shipping on orders over $50!')
        ->url->toBe('https://example.com/promo')
        ->is_active->toBeTrue();
});

test('creates announcement with minimal fields', function () {
    $response = actingAsSuperAdmin()->post(route('admin.storefront.announcements.store'), [
        'content' => 'Welcome to our store!',
    ]);

    $response->assertSessionHasNoErrors();

    expect(Announcement::query()->count())->toBe(1);

    $announcement = Announcement::query()->first();

    $response->assertRedirect(route('admin.storefront.announcements.edit', $announcement));

    expect($announcement)
        ->content->toBe('Welcome to our store!')
        ->url->toBeNull()
        ->is_active->toBeTrue();
});

test('validates content is required', function () {
    $response = actingAsSuperAdmin()->post(route('admin.storefront.announcements.store'), [
        'url' => 'https://example.com',
    ]);

    $response->assertRedirect()
        ->assertInvalid('content');
});

test('validates content maximum length', function () {
    $response = actingAsSuperAdmin()->post(route('admin.storefront.announcements.store'), [
        'content' => str_repeat('a', 256),
    ]);

    $response->assertRedirect()
        ->assertInvalid('content');
});

test('validates url maximum length', function () {
    $response = actingAsSuperAdmin()->post(route('admin.storefront.announcements.store'), [
        'content' => 'Test',
        'url' => str_repeat('a', 256),
    ]);

    $response->assertRedirect()
        ->assertInvalid('url');
});

test('validates ends_at must be after starts_at', function () {
    $response = actingAsSuperAdmin()->post(route('admin.storefront.announcements.store'), [
        'content' => 'Test',
        'starts_at' => '2026-12-31 23:59:59',
        'ends_at' => '2026-01-01 00:00:00',
    ]);

    $response->assertRedirect()
        ->assertInvalid('ends_at');
});

test('requires authentication for store', function () {
    $response = post(route('admin.storefront.announcements.store'), [
        'content' => 'Test',
    ]);

    $response->assertRedirect(route('admin.login'));
});

test('requires storefront update permission for store', function () {
    $role = Role::query()->where(['name' => RoleEnum::Admin])->firstOrFail();
    $role->givePermissionTo(Permission::StorefrontUpdate);

    $response = actingAsAdmin()->post(route('admin.storefront.announcements.store'), [
        'content' => 'Test',
    ]);

    $announcement = Announcement::query()->first();
    $response->assertRedirect(route('admin.storefront.announcements.edit', $announcement));

    $role->revokePermissionTo(Permission::StorefrontUpdate);

    $response = actingAsAdmin()->post(route('admin.storefront.announcements.store'), [
        'content' => 'Another Test',
    ]);

    $response->assertForbidden();
});
