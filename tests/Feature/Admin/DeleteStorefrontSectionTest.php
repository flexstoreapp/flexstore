<?php

declare(strict_types=1);

use App\Actions\DestroyStorefrontSectionAction;
use App\Enums\Permission;
use App\Enums\Role as RoleEnum;
use App\Http\Controllers\Admin\StorefrontSectionController;
use App\Models\StorefrontSection;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Laravel\delete;

covers(StorefrontSectionController::class, DestroyStorefrontSectionAction::class);

uses()->group('storefront');

test('deletes a storefront section', function () {
    $section = StorefrontSection::factory()->create();

    $response = actingAsSuperAdmin()->delete(route('admin.storefront.homepage.sections.destroy', $section));

    $response->assertRedirectBack();

    assertDatabaseMissing('storefront_sections', [
        'id' => $section->id,
    ]);
});

test('requires authentication', function () {
    $section = StorefrontSection::factory()->create();

    $response = delete(route('admin.storefront.homepage.sections.destroy', $section));

    $response->assertRedirect(route('admin.login'));

    assertDatabaseHas('storefront_sections', [
        'id' => $section->id,
    ]);
});

test('requires storefront.update permission', function () {
    $role = Role::query()->where(['name' => RoleEnum::Admin])->firstOrFail();
    $role->givePermissionTo(Permission::StorefrontUpdate);

    $section1 = StorefrontSection::factory()->create();
    $section2 = StorefrontSection::factory()->create();

    $response = actingAsAdmin()->delete(route('admin.storefront.homepage.sections.destroy', $section1));

    $response->assertRedirectBack();

    assertDatabaseMissing('storefront_sections', [
        'id' => $section1->id,
    ]);

    $role->revokePermissionTo(Permission::StorefrontUpdate);

    $response = actingAsAdmin()->delete(route('admin.storefront.homepage.sections.destroy', $section2));

    $response->assertForbidden();

    assertDatabaseHas('storefront_sections', [
        'id' => $section2->id,
    ]);
});
