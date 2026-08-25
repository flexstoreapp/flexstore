<?php

declare(strict_types=1);

use App\Enums\Permission;
use App\Enums\Role as RoleEnum;
use App\Http\Controllers\Admin\StorefrontController;
use App\Models\StorefrontSection;
use Inertia\Testing\AssertableInertia;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\get;

covers(StorefrontController::class);

uses()->group('storefront');

test('displays storefront builder page', function () {
    StorefrontSection::factory(3)->create();

    $response = actingAsSuperAdmin()->get(route('admin.storefront.builder.index'));

    $response->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('admin/storefront/builder')
        );
});

test('requires authentication', function () {
    StorefrontSection::factory()->count(2)->create();

    $response = get(route('admin.storefront.builder.index'));

    $response->assertRedirect(route('admin.login'));
});

test('requires storefront.view permission', function () {
    $role = Role::query()->where(['name' => RoleEnum::Admin])->firstOrFail();
    $role->givePermissionTo(Permission::StorefrontView);

    StorefrontSection::factory()->count(2)->create();

    $response = actingAsAdmin()->get(route('admin.storefront.builder.index'));

    $response->assertOk();

    $role->revokePermissionTo(Permission::StorefrontView);

    $response = actingAsAdmin()->get(route('admin.storefront.builder.index'));

    $response->assertForbidden();
});
