<?php

declare(strict_types=1);

use App\Actions\UpdateBrandAction;
use App\Enums\Permission;
use App\Enums\Role as RoleEnum;
use App\Http\Controllers\Admin\BrandController;
use App\Http\Requests\Admin\UpdateBrandRequest;
use App\Models\Brand;
use App\Models\Media;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Laravel\patch;

covers(BrandController::class, UpdateBrandRequest::class, UpdateBrandAction::class);

uses()->group('brand');

test('auto-generates URL handle from name when URL handle is not provided', function () {
    $brand = Brand::factory()->create(['name' => 'Old', 'url_handle' => 'old']);

    actingAsSuperAdmin()->patch(route('admin.brands.update', $brand), [
        'name' => 'Brand New Name',
        'is_active' => true,
    ])->assertRedirectBack()->assertSessionHasNoErrors();

    assertDatabaseHas('brands', [
        'id' => $brand->id,
        'url_handle' => 'brand-new-name',
    ]);
});

test('updates brand SEO fields', function () {
    $brand = Brand::factory()->create([
        'seo_title' => 'Old SEO Title',
        'seo_description' => 'Old SEO description',
    ]);

    $response = actingAsSuperAdmin()->patch(route('admin.brands.update', $brand), [
        'seo_title' => 'New SEO Title',
        'seo_description' => 'New SEO description',
    ]);

    $response->assertRedirectBack()
        ->assertSessionHasNoErrors();

    assertDatabaseHas('brands', [
        'id' => $brand->id,
        'seo_title' => castAsTranslatableJson('New SEO Title'),
        'seo_description' => castAsTranslatableJson('New SEO description'),
    ]);
});

test('updates an existing brand', function () {
    $brand = Brand::factory()->create([
        'name' => 'Old Name',
        'url_handle' => 'old-url-handle',
        'is_active' => false,
    ]);

    $media = Media::factory()->create();

    $response = actingAsSuperAdmin()->patch(route('admin.brands.update', $brand), [
        'name' => 'Updated Name',
        'url_handle' => 'updated-url-handle',
        'description' => 'Updated description',
        'image_id' => $media->id,
        'is_active' => true,
    ]);

    $response->assertRedirectBack()
        ->assertSessionHasNoErrors();

    assertDatabaseHas('brands', [
        'id' => $brand->id,
        'name' => castAsTranslatableJson('Updated Name'),
        'url_handle' => 'updated-url-handle',
        'description' => castAsTranslatableJson('Updated description'),
        'is_active' => true,
    ]);

    expect($brand->refresh()->image_id)->toBe($media->id)
        ->and($brand->image?->id)->toBe($media->id);
});

test('validates unique URL handle when updating a brand', function () {
    $brandToUpdate = Brand::factory()->create();
    Brand::factory()->create(['url_handle' => 'existing-url-handle']);

    $response = actingAsSuperAdmin()->patch(route('admin.brands.update', $brandToUpdate), [
        'name' => 'Updated Name',
        'url_handle' => 'existing-url-handle',
        'description' => 'Updated description',
        'is_active' => true,
    ]);

    $response->assertRedirectBack()
        ->assertInvalid('url_handle');

    assertDatabaseMissing('brands', [
        'id' => $brandToUpdate->id,
        'url_handle' => 'existing-url-handle',
    ]);
});

test('allows same URL handle when updating the same brand', function () {
    $brand = Brand::factory()->create(['url_handle' => 'original-url-handle']);

    $response = actingAsSuperAdmin()->patch(route('admin.brands.update', $brand), [
        'name' => 'Updated Name',
        'url_handle' => 'original-url-handle',
        'is_active' => true,
    ]);

    $response->assertRedirectBack()
        ->assertSessionHasNoErrors();

    assertDatabaseHas('brands', [
        'id' => $brand->id,
        'url_handle' => 'original-url-handle',
    ]);
});

test('validates name field is required when provided', function () {
    $brand = Brand::factory()->create();

    $response = actingAsSuperAdmin()->patch(route('admin.brands.update', $brand), [
        'name' => '',
        'url_handle' => 'updated-url-handle',
        'is_active' => true,
    ]);

    $response->assertRedirectBack()
        ->assertInvalid('name');
});

test('validates is_active field is required when provided', function () {
    $brand = Brand::factory()->create();

    $response = actingAsSuperAdmin()->patch(route('admin.brands.update', $brand), [
        'name' => 'Updated Name',
        'url_handle' => 'updated-url-handle',
        'is_active' => '',
    ]);

    $response->assertRedirectBack()
        ->assertInvalid('is_active');
});

test('requires authentication', function () {
    $brand = Brand::factory()->create();

    $response = patch(route('admin.brands.update', $brand), [
        'name' => 'Updated Name',
        'url_handle' => 'updated-url-handle',
        'is_active' => true,
    ]);

    $response->assertRedirect(route('admin.login'));
});

test('requires brands.update permission', function () {
    $role = Role::query()->where(['name' => RoleEnum::Admin])->firstOrFail();
    $brand = Brand::factory()->create();

    $response = actingAsAdmin()->patch(route('admin.brands.update', $brand), [
        'name' => 'Updated Name',
        'url_handle' => 'updated-url-handle',
        'is_active' => true,
    ]);

    $response->assertRedirectBack()
        ->assertSessionHasNoErrors();

    assertDatabaseHas('brands', [
        'url_handle' => 'updated-url-handle',
    ]);

    $role->revokePermissionTo(Permission::BrandsManage);

    $anotherBrand = Brand::factory()->create();

    $response = actingAsAdmin()->patch(route('admin.brands.update', $anotherBrand), [
        'name' => 'Another Update',
        'url_handle' => 'another-update',
        'is_active' => true,
    ]);

    $response->assertForbidden();

    assertDatabaseMissing('brands', [
        'url_handle' => 'another-update',
    ]);
});

test('rejects a brand URL handle that is not a valid slug', function () {
    $brand = Brand::factory()->create(['url_handle' => 'valid-handle']);

    $response = actingAsSuperAdmin()->patch(route('admin.brands.update', $brand), [
        'url_handle' => 'Invalid Handle',
    ]);

    $response->assertRedirectBack()
        ->assertInvalid('url_handle');

    assertDatabaseHas('brands', [
        'id' => $brand->id,
        'url_handle' => 'valid-handle',
    ]);
});

test('leaves an existing invalid brand URL handle untouched when it is not submitted', function () {
    $brand = Brand::factory()->create(['url_handle' => 'Legacy Handle']);

    actingAsSuperAdmin()->patch(route('admin.brands.update', $brand), [
        'seo_title' => 'New SEO Title',
    ])->assertRedirectBack()->assertSessionHasNoErrors();

    assertDatabaseHas('brands', [
        'id' => $brand->id,
        'url_handle' => 'Legacy Handle',
    ]);
});
