<?php

declare(strict_types=1);

use App\Actions\DestroyAnnouncementAction;
use App\Enums\Permission;
use App\Enums\Role as RoleEnum;
use App\Http\Controllers\Admin\AnnouncementController;
use App\Models\Announcement;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\delete;

covers(
    AnnouncementController::class,
    DestroyAnnouncementAction::class
);

uses()->group('announcement');

test('deletes announcement successfully', function () {
    $announcement = Announcement::factory()->create();

    $response = actingAsSuperAdmin()->delete(route('admin.storefront.announcements.destroy', $announcement));

    $response->assertRedirect()
        ->assertSessionHasNoErrors();

    expect(Announcement::query()->find($announcement->id))->toBeNull();
});

test('requires authentication for destroy', function () {
    $announcement = Announcement::factory()->create();

    $response = delete(route('admin.storefront.announcements.destroy', $announcement));

    $response->assertRedirect(route('admin.login'));
});

test('requires storefront update permission for destroy', function () {
    $role = Role::query()->where(['name' => RoleEnum::Admin])->firstOrFail();
    $role->revokePermissionTo(Permission::StorefrontUpdate);

    $announcement = Announcement::factory()->create();

    $response = actingAsAdmin()->delete(route('admin.storefront.announcements.destroy', $announcement));

    $response->assertForbidden();
});
