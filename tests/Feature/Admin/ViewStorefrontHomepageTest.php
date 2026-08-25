<?php

declare(strict_types=1);

use App\Enums\Permission;
use App\Enums\Role as RoleEnum;
use App\Http\Controllers\Admin\StorefrontHomepageController;
use App\Models\StorefrontSection;
use App\Queries\StorefrontSectionListQuery;
use Inertia\Testing\AssertableInertia;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\get;

covers(StorefrontHomepageController::class, StorefrontSectionListQuery::class);

uses()->group('storefront');

test('displays homepage with sections ordered by sort_order', function () {
    StorefrontSection::factory()->create(['title' => 'Section C', 'sort_order' => 2]);
    StorefrontSection::factory()->create(['title' => 'Section A', 'sort_order' => 0]);
    StorefrontSection::factory()->create(['title' => 'Section B', 'sort_order' => 1]);

    $response = actingAsSuperAdmin()->get(route('admin.storefront.homepage.edit'));

    $response->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('admin/storefront/homepage')
                ->has('sections', 3)
                ->where('sections.0.title', castAsTranslatableArray('Section A'))
                ->where('sections.1.title', castAsTranslatableArray('Section B'))
                ->where('sections.2.title', castAsTranslatableArray('Section C'))
        );
});

test('displays empty sections array when no sections exist', function () {
    $response = actingAsSuperAdmin()->get(route('admin.storefront.homepage.edit'));

    $response->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('admin/storefront/homepage')
                ->has('sections', 0)
        );
});

test('requires authentication', function () {
    StorefrontSection::factory()->count(2)->create();

    $response = get(route('admin.storefront.homepage.edit'));

    $response->assertRedirect(route('admin.login'));
});

test('requires storefront.view permission', function () {
    $role = Role::query()->where(['name' => RoleEnum::Admin])->firstOrFail();
    $role->givePermissionTo(Permission::StorefrontView);

    StorefrontSection::factory()->count(2)->create();

    $response = actingAsAdmin()->get(route('admin.storefront.homepage.edit'));

    $response->assertOk();

    $role->revokePermissionTo(Permission::StorefrontView);

    $response = actingAsAdmin()->get(route('admin.storefront.homepage.edit'));

    $response->assertForbidden();
});
