<?php

declare(strict_types=1);

use App\Actions\ReorderAnnouncementsAction;
use App\Enums\Permission;
use App\Enums\Role as RoleEnum;
use App\Http\Controllers\Admin\AnnouncementReorderController;
use App\Http\Requests\Admin\ReorderAnnouncementsRequest;
use App\Models\Announcement;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\patch;

covers(
    AnnouncementReorderController::class,
    ReorderAnnouncementsRequest::class,
    ReorderAnnouncementsAction::class
);

uses()->group('announcement');

test('reorders announcements successfully', function () {
    $announcement1 = Announcement::factory()->create(['sort_order' => 0]);
    $announcement2 = Announcement::factory()->create(['sort_order' => 1]);
    $announcement3 = Announcement::factory()->create(['sort_order' => 2]);

    $response = actingAsSuperAdmin()->patch(route('admin.storefront.announcements.reorder'), [
        'ordered_ids' => [$announcement3->id, $announcement1->id, $announcement2->id],
    ]);

    $response->assertRedirect()
        ->assertSessionHasNoErrors();

    expect(Announcement::query()->find($announcement3->id)->sort_order)->toBe(0);
    expect(Announcement::query()->find($announcement1->id)->sort_order)->toBe(1);
    expect(Announcement::query()->find($announcement2->id)->sort_order)->toBe(2);
});

test('validates ordered_ids is required', function () {
    $response = actingAsSuperAdmin()->patch(route('admin.storefront.announcements.reorder'), []);

    $response->assertRedirect()
        ->assertInvalid('ordered_ids');
});

test('validates ordered_ids must be array', function () {
    $response = actingAsSuperAdmin()->patch(route('admin.storefront.announcements.reorder'), [
        'ordered_ids' => 'not-an-array',
    ]);

    $response->assertRedirect()
        ->assertInvalid('ordered_ids');
});

test('validates each id must exist', function () {
    $announcement = Announcement::factory()->create();

    $response = actingAsSuperAdmin()->patch(route('admin.storefront.announcements.reorder'), [
        'ordered_ids' => [$announcement->id, 99999],
    ]);

    $response->assertRedirect()
        ->assertInvalid('ordered_ids.1');
});

test('requires authentication', function () {
    $announcement = Announcement::factory()->create();

    $response = patch(route('admin.storefront.announcements.reorder'), [
        'ordered_ids' => [$announcement->id],
    ]);

    $response->assertRedirect(route('admin.login'));
});

test('requires storefront update permission', function () {
    $role = Role::query()->where(['name' => RoleEnum::Admin])->firstOrFail();
    $role->revokePermissionTo(Permission::StorefrontUpdate);

    $announcement = Announcement::factory()->create();

    $response = actingAsAdmin()->patch(route('admin.storefront.announcements.reorder'), [
        'ordered_ids' => [$announcement->id],
    ]);

    $response->assertForbidden();
});

test('updates sort order in correct sequence', function () {
    $announcement1 = Announcement::factory()->create(['sort_order' => 5]);
    $announcement2 = Announcement::factory()->create(['sort_order' => 10]);
    $announcement3 = Announcement::factory()->create(['sort_order' => 15]);
    $announcement4 = Announcement::factory()->create(['sort_order' => 20]);

    $response = actingAsSuperAdmin()->patch(route('admin.storefront.announcements.reorder'), [
        'ordered_ids' => [$announcement4->id, $announcement2->id, $announcement1->id, $announcement3->id],
    ]);

    $response->assertRedirect()
        ->assertSessionHasNoErrors();

    expect(Announcement::query()->find($announcement4->id)->sort_order)->toBe(0);
    expect(Announcement::query()->find($announcement2->id)->sort_order)->toBe(1);
    expect(Announcement::query()->find($announcement1->id)->sort_order)->toBe(2);
    expect(Announcement::query()->find($announcement3->id)->sort_order)->toBe(3);
});

test('handles single announcement', function () {
    $announcement = Announcement::factory()->create(['sort_order' => 5]);

    $response = actingAsSuperAdmin()->patch(route('admin.storefront.announcements.reorder'), [
        'ordered_ids' => [$announcement->id],
    ]);

    $response->assertRedirect()
        ->assertSessionHasNoErrors();

    expect(Announcement::query()->find($announcement->id)->sort_order)->toBe(0);
});
