<?php

declare(strict_types=1);

use App\Enums\Permission;
use App\Enums\Role as RoleEnum;
use App\Http\Controllers\Admin\BrandController;
use App\Http\Requests\Admin\IndexBrandRequest;
use App\Models\Brand;
use App\Queries\BrandListQuery;
use Inertia\Testing\AssertableInertia;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\get;

covers(BrandController::class, BrandListQuery::class, IndexBrandRequest::class);

uses()->group('brand');

test('displays brand list page', function () {
    Brand::factory(3)->create();

    $response = actingAsSuperAdmin()->get(route('admin.brands.index'));

    $response->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('admin/brands/list')
                ->has('brands.data', 3)
                ->where('filters.query', null)
                ->where('filters.is_active', null)
                ->where('filters.sort', 'created_at')
                ->where('filters.direction', 'desc')
        );
});

test('displays brands with product count', function () {
    $brand = Brand::factory()->hasProducts(5)->create();

    $response = actingAsSuperAdmin()->get(route('admin.brands.index'));

    $response->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('admin/brands/list')
                ->where('brands.data.0.products_count', 5)
        );
});

test('sorts brands by products count', function () {
    $brandWithFewProducts = Brand::factory()->hasProducts(2)->create(['name' => 'Brand A']);
    $brandWithManyProducts = Brand::factory()->hasProducts(10)->create(['name' => 'Brand B']);
    $brandWithNoProducts = Brand::factory()->create(['name' => 'Brand C']);

    $response = actingAsSuperAdmin()->get(route('admin.brands.index', ['sort' => 'products_count', 'direction' => 'desc']));

    $response->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('admin/brands/list')
                ->where('brands.data.0.id', $brandWithManyProducts->id)
                ->where('brands.data.0.products_count', 10)
                ->where('brands.data.1.id', $brandWithFewProducts->id)
                ->where('brands.data.1.products_count', 2)
                ->where('brands.data.2.id', $brandWithNoProducts->id)
                ->where('brands.data.2.products_count', 0)
        );
});

test('sorts brands by products count ascending', function () {
    $brandWithFewProducts = Brand::factory()->hasProducts(2)->create(['name' => 'Brand A']);
    $brandWithManyProducts = Brand::factory()->hasProducts(10)->create(['name' => 'Brand B']);
    $brandWithNoProducts = Brand::factory()->create(['name' => 'Brand C']);

    $response = actingAsSuperAdmin()->get(route('admin.brands.index', ['sort' => 'products_count', 'direction' => 'asc']));

    $response->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('admin/brands/list')
                ->where('brands.data.0.id', $brandWithNoProducts->id)
                ->where('brands.data.0.products_count', 0)
                ->where('brands.data.1.id', $brandWithFewProducts->id)
                ->where('brands.data.1.products_count', 2)
                ->where('brands.data.2.id', $brandWithManyProducts->id)
                ->where('brands.data.2.products_count', 10)
        );
});

test('can filter brands by query term', function () {
    Brand::factory()->create(['name' => 'Nike']);
    Brand::factory()->create(['name' => 'Adidas']);

    $response = actingAsSuperAdmin()->get(route('admin.brands.index', ['query' => 'Nike']));

    $response->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('admin/brands/list')
                ->has('brands.data', 1)
        );
});

test('can filter brands by URL handle', function () {
    Brand::factory()->create(['name' => 'Nike', 'url_handle' => 'nike']);
    Brand::factory()->create(['name' => 'Adidas', 'url_handle' => 'adidas']);

    $response = actingAsSuperAdmin()->get(route('admin.brands.index', ['query' => 'nike']));

    $response->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('admin/brands/list')
                ->has('brands.data', 1)
        );
});

test('requires authentication', function () {
    Brand::factory()->count(2)->create();

    $response = get(route('admin.brands.index'));

    $response->assertRedirect(route('admin.login'));
});

test('requires brands.view permission', function () {
    $role = Role::query()->where(['name' => RoleEnum::Admin])->firstOrFail();
    Brand::factory()->count(2)->create();

    $response = actingAsAdmin()->get(route('admin.brands.index'));

    $response->assertOk();

    $role->revokePermissionTo(Permission::BrandsView);

    $response = actingAsAdmin()->get(route('admin.brands.index'));

    $response->assertForbidden();
});
