<?php

declare(strict_types=1);

use App\Actions\UpdateAnnouncementAction;
use App\Enums\Permission;
use App\Enums\Role as RoleEnum;
use App\Http\Controllers\Admin\AnnouncementController;
use App\Http\Requests\Admin\UpdateAnnouncementRequest;
use App\Models\Announcement;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\get;
use function Pest\Laravel\patch;

covers(
    AnnouncementController::class,
    UpdateAnnouncementRequest::class,
    UpdateAnnouncementAction::class
);

uses()->group('announcement');

test('shows edit form', function () {
    $announcement = Announcement::factory()->create();

    $response = actingAsSuperAdmin()->get(route('admin.storefront.announcements.edit', $announcement));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/storefront/announcements/edit')
            ->has('announcement')
            ->where('announcement.id', $announcement->id));
});

test('requires authentication for edit form', function () {
    $announcement = Announcement::factory()->create();

    $response = get(route('admin.storefront.announcements.edit', $announcement));

    $response->assertRedirect(route('admin.login'));
});

test('requires storefront update permission for edit form', function () {
    $role = Role::query()->where(['name' => RoleEnum::Admin])->firstOrFail();
    $role->revokePermissionTo(Permission::StorefrontUpdate);

    $announcement = Announcement::factory()->create();

    $response = actingAsAdmin()->get(route('admin.storefront.announcements.edit', $announcement));

    $response->assertForbidden();
});

test('updates announcement successfully', function () {
    $announcement = Announcement::factory()->create([
        'content' => 'Old content',
        'url' => 'https://old.com',
    ]);

    $response = actingAsSuperAdmin()
        ->from(route('admin.storefront.announcements.edit', $announcement))
        ->patch(route('admin.storefront.announcements.update', $announcement), [
            'content' => 'Updated content',
            'url' => 'https://new.com',
            'is_active' => false,
        ]);

    $response->assertRedirect(route('admin.storefront.announcements.edit', $announcement))
        ->assertSessionHasNoErrors();

    $announcement->refresh();

    expect($announcement)
        ->content->toBe('Updated content')
        ->url->toBe('https://new.com')
        ->is_active->toBeFalse();
});

test('updates only provided fields', function () {
    $announcement = Announcement::factory()->create([
        'content' => 'Original content',
        'url' => 'https://original.com',
        'is_active' => true,
    ]);

    $response = actingAsSuperAdmin()
        ->from(route('admin.storefront.announcements.edit', $announcement))
        ->patch(route('admin.storefront.announcements.update', $announcement), [
            'content' => 'Updated content',
        ]);

    $response->assertRedirect(route('admin.storefront.announcements.edit', $announcement))
        ->assertSessionHasNoErrors();

    $announcement->refresh();

    expect($announcement)
        ->content->toBe('Updated content')
        ->url->toBe('https://original.com')
        ->is_active->toBeTrue();
});

test('requires authentication for update', function () {
    $announcement = Announcement::factory()->create();

    $response = patch(route('admin.storefront.announcements.update', $announcement), [
        'content' => 'Updated',
    ]);

    $response->assertRedirect(route('admin.login'));
});

test('requires storefront update permission for update', function () {
    $role = Role::query()->where(['name' => RoleEnum::Admin])->firstOrFail();
    $role->revokePermissionTo(Permission::StorefrontUpdate);

    $announcement = Announcement::factory()->create();

    $response = actingAsAdmin()->patch(route('admin.storefront.announcements.update', $announcement), [
        'content' => 'Updated',
    ]);

    $response->assertForbidden();
});
