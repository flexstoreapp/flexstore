<?php

declare(strict_types=1);

use App\Actions\BulkDestroyBrandAction;
use App\Enums\Permission;
use App\Enums\Role as RoleEnum;
use App\Http\Controllers\Admin\BulkBrandController;
use App\Http\Requests\Admin\BulkDestroyBrandRequest;
use App\Models\Brand;
use App\Models\Media;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Laravel\delete;

covers(BulkBrandController::class, BulkDestroyBrandRequest::class, BulkDestroyBrandAction::class);

uses()->group('brand');

test('bulk deletes brands', function () {
    $brands = Brand::factory(2)->create();
    $ids = $brands->pluck('id')->all();

    $response = actingAsSuperAdmin()->delete(route('admin.brands.bulk.destroy'), [
        'ids' => $ids,
    ]);

    $response->assertRedirect()
        ->assertSessionHasNoErrors();

    foreach ($ids as $id) {
        assertDatabaseMissing('brands', ['id' => $id]);
    }
});

test('bulk destroys brands that have an image', function () {
    $brands = Brand::factory(2)->create(['image_id' => fn (): int => Media::factory()->create()->id]);

    actingAsSuperAdmin()->delete(route('admin.brands.bulk.destroy'), [
        'ids' => $brands->pluck('id')->all(),
    ])->assertRedirect()->assertSessionHasNoErrors();

    expect(Brand::query()->whereIn('id', $brands->pluck('id'))->count())->toBe(0);
});

test('requires at least one brand id', function () {
    $response = actingAsSuperAdmin()->delete(route('admin.brands.bulk.destroy'), [
        'ids' => [],
    ]);

    $response->assertRedirectBack()
        ->assertInvalid('ids');
});

test('validates that ids exists', function () {
    $brand = Brand::factory()->create();
    $nonExistentId = 9999;

    $response = actingAsSuperAdmin()->delete(route('admin.brands.bulk.destroy'), [
        'ids' => [$brand->id, $nonExistentId],
    ]);

    $response->assertRedirectBack()
        ->assertInvalid('ids');
});

test('requires authentication', function () {
    $brands = Brand::factory()->count(2)->create();

    $response = delete(route('admin.brands.bulk.destroy'), [
        'ids' => $brands->pluck('id')->all(),
    ]);

    $response->assertRedirect(route('admin.login'));
});

test('requires brands.delete permission', function () {
    $role = Role::query()->where(['name' => RoleEnum::Admin])->firstOrFail();
    $brands = Brand::factory()->count(2)->create();

    $response = actingAsAdmin()->delete(route('admin.brands.bulk.destroy'), [
        'ids' => $brands->pluck('id')->all(),
    ]);

    $response->assertRedirectBack()
        ->assertSessionHasNoErrors();

    foreach ($brands as $brand) {
        assertDatabaseMissing('brands', [
            'id' => $brand->id,
        ]);
    }

    $role->revokePermissionTo(Permission::BrandsDelete);

    $moreBrands = Brand::factory()->count(2)->create();

    $response = actingAsAdmin()->delete(route('admin.brands.bulk.destroy'), [
        'ids' => $moreBrands->pluck('id')->all(),
    ]);

    $response->assertForbidden();

    foreach ($moreBrands as $brand) {
        assertDatabaseHas('brands', [
            'id' => $brand->id,
        ]);
    }
});
