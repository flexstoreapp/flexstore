<?php

declare(strict_types=1);

use App\Enums\Permission;
use App\Http\Controllers\Api\V1\Admin\BrandController;
use App\Http\Controllers\Api\V1\Admin\BrandSearchController;
use App\Http\Controllers\Api\V1\Admin\BulkBrandController;
use App\Http\Requests\Api\V1\Admin\SearchTermRequest;
use App\Http\Resources\Api\V1\Admin\BrandOptionResource;
use App\Http\Resources\Api\V1\Admin\BrandResource;
use App\Models\Brand;
use App\Models\Media;
use App\Models\Product;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Laravel\deleteJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\patchJson;
use function Pest\Laravel\postJson;

covers(
    BrandController::class,
    BulkBrandController::class,
    BrandSearchController::class,
    SearchTermRequest::class,
    BrandResource::class,
    BrandOptionResource::class,
);

uses()->group('api', 'admin-api');

test('the brand index is paginated and filterable', function (): void {
    actingAsApiAdmin(userWithPermissions([Permission::BrandsView]));

    Brand::factory()->create(['name' => 'Acme', 'is_active' => true]);
    Brand::factory()->create(['name' => 'Umbrella', 'is_active' => true]);

    getJson('/api/v1/admin/brands?per_page=1')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('meta.per_page', 1)
        ->assertJsonPath('meta.total', 2)
        ->assertJsonStructure([
            'data' => [['id', 'name', 'url_handle', 'is_active', 'products_count', 'image']],
        ]);

    getJson('/api/v1/admin/brands?query=Umbrel')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Umbrella');
});

test('a staff member without the view permission cannot list brands', function (): void {
    actingAsApiAdmin(userWithPermissions([Permission::DashboardView]));

    getJson('/api/v1/admin/brands')->assertForbidden();
});

test('a brand is created', function (): void {
    actingAsApiAdmin(userWithPermissions([Permission::BrandsManage]));

    $media = Media::factory()->create();

    postJson('/api/v1/admin/brands', [
        'name' => 'Northwind',
        'description' => 'Wind powered goods',
        'image_id' => $media->id,
        'is_active' => true,
    ])
        ->assertCreated()
        ->assertJsonPath('name', 'Northwind')
        ->assertJsonPath('url_handle', 'northwind')
        ->assertJsonPath('image.id', $media->id)
        ->assertJsonPath('products_count', 0);

    assertDatabaseHas('brands', ['url_handle' => 'northwind']);
});

test('creating a brand validates the payload', function (): void {
    actingAsApiAdmin(userWithPermissions([Permission::BrandsManage]));

    postJson('/api/v1/admin/brands', [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['name', 'is_active']);
});

test('a single brand is fetched with the same fields as the listing', function (): void {
    actingAsApiAdmin(userWithPermissions([Permission::BrandsView]));

    $media = Media::factory()->create();
    $brand = Brand::factory()->create(['name' => 'Northmark', 'image_id' => $media->id]);
    Product::factory()->create(['brand_id' => $brand->id]);

    getJson('/api/v1/admin/brands/' . $brand->id)
        ->assertOk()
        ->assertJsonPath('id', $brand->id)
        ->assertJsonPath('name', 'Northmark')
        ->assertJsonPath('products_count', 1)
        ->assertJsonPath('image.id', $media->id)
        ->assertJsonStructure(['id', 'name', 'url_handle', 'is_active', 'products_count', 'image']);
});

test('a staff member without the view permission cannot fetch a brand', function (): void {
    actingAsApiAdmin(userWithPermissions([Permission::DashboardView]));

    $brand = Brand::factory()->create();

    getJson('/api/v1/admin/brands/' . $brand->id)->assertForbidden();
});

test('fetching a missing brand returns a not found response', function (): void {
    actingAsApiAdmin(userWithPermissions([Permission::BrandsView]));

    getJson('/api/v1/admin/brands/99999')->assertNotFound();
});

test('a brand is updated with patch semantics', function (): void {
    actingAsApiAdmin(userWithPermissions([Permission::BrandsManage]));

    $brand = Brand::factory()->create(['name' => 'Old Name', 'is_active' => true]);

    patchJson('/api/v1/admin/brands/' . $brand->id, ['name' => 'New Name'])
        ->assertOk()
        ->assertJsonPath('name', 'New Name')
        ->assertJsonPath('is_active', true);

    expect($brand->refresh()->name)->toBe('New Name');
});

test('a single brand is deleted and its products keep existing without a brand', function (): void {
    actingAsApiAdmin(userWithPermissions([Permission::BrandsDelete]));

    $brand = Brand::factory()->create();
    $product = Product::factory()->create(['brand_id' => $brand->id]);

    deleteJson('/api/v1/admin/brands/' . $brand->id)->assertNoContent();

    assertDatabaseMissing('brands', ['id' => $brand->id]);
    assertDatabaseHas('products', ['id' => $product->id, 'brand_id' => null]);
});

test('a staff member without the delete permission cannot delete a brand', function (): void {
    actingAsApiAdmin(userWithPermissions([Permission::BrandsView]));

    $brand = Brand::factory()->create();

    deleteJson('/api/v1/admin/brands/' . $brand->id)->assertForbidden();

    assertDatabaseHas('brands', ['id' => $brand->id]);
});

test('deleting a missing brand returns a not found response', function (): void {
    actingAsApiAdmin(userWithPermissions([Permission::BrandsDelete]));

    deleteJson('/api/v1/admin/brands/99999')->assertNotFound();
});

test('brands are bulk deleted', function (): void {
    actingAsApiAdmin(userWithPermissions([Permission::BrandsDelete]));

    $brands = Brand::factory(2)->create();

    deleteJson('/api/v1/admin/brands/bulk', ['ids' => $brands->pluck('id')->all()])
        ->assertNoContent();

    foreach ($brands as $brand) {
        assertDatabaseMissing('brands', ['id' => $brand->id]);
    }
});

test('bulk delete rejects unknown brand ids', function (): void {
    actingAsApiAdmin(userWithPermissions([Permission::BrandsDelete]));

    deleteJson('/api/v1/admin/brands/bulk', ['ids' => [99999]])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('ids');
});

test('the brand search returns matching active brands', function (): void {
    actingAsApiAdmin(userWithPermissions([Permission::BrandsReference]));

    $match = Brand::factory()->create(['name' => 'Zephyr', 'is_active' => true]);
    Brand::factory()->create(['name' => 'Anvil', 'is_active' => true]);

    getJson('/api/v1/admin/brands/search?query=Zeph')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $match->id)
        ->assertJsonPath('data.0.name', 'Zephyr');
});

test('an explicit null url handle is rejected rather than written', function (): void {
    actingAsApiAdmin(userWithPermissions([Permission::BrandsManage]));

    $brand = Brand::factory()->create(['url_handle' => 'northmark']);

    patchJson('/api/v1/admin/brands/' . $brand->id, ['url_handle' => null])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('url_handle');

    expect($brand->refresh()->url_handle)->toBe('northmark');
});
