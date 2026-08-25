<?php

declare(strict_types=1);

use App\Actions\StoreBrandAction;
use App\Enums\Permission;
use App\Enums\Role as RoleEnum;
use App\Http\Controllers\Admin\BrandController;
use App\Http\Requests\Admin\StoreBrandRequest;
use App\Models\Brand;
use App\Models\Media;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Laravel\post;

covers(BrandController::class, StoreBrandRequest::class, StoreBrandAction::class);

uses()->group('brand');

test('creates a new brand', function () {
    $media = Media::factory()->create();

    $response = actingAsSuperAdmin()->post(route('admin.brands.store'), [
        'name' => 'New Brand',
        'url_handle' => 'new-brand',
        'description' => 'Test description',
        'image_id' => $media->id,
        'is_active' => true,
    ]);

    $response->assertRedirectBack()
        ->assertSessionHasNoErrors();

    assertDatabaseHas('brands', [
        'name' => castAsTranslatableJson('New Brand'),
        'url_handle' => 'new-brand',
        'description' => castAsTranslatableJson('Test description'),
        'is_active' => true,
    ]);

    $brand = Brand::query()->where('url_handle', 'new-brand')->firstOrFail();

    expect($brand->image_id)->toBe($media->id)
        ->and($brand->image?->id)->toBe($media->id);
});

test('creates a brand with SEO fields', function () {
    $response = actingAsSuperAdmin()->post(route('admin.brands.store'), [
        'name' => 'SEO Brand',
        'url_handle' => 'seo-brand',
        'seo_title' => 'Best SEO Brand',
        'seo_description' => 'Shop the best SEO brand products.',
        'is_active' => true,
    ]);

    $response->assertRedirectBack()
        ->assertSessionHasNoErrors();

    assertDatabaseHas('brands', [
        'url_handle' => 'seo-brand',
        'seo_title' => castAsTranslatableJson('Best SEO Brand'),
        'seo_description' => castAsTranslatableJson('Shop the best SEO brand products.'),
    ]);
});

test('validates unique URL handle when creating a brand', function () {
    Brand::factory()->create(['url_handle' => 'existing-url-handle']);

    $response = actingAsSuperAdmin()->post(route('admin.brands.store'), [
        'name' => 'New Brand',
        'url_handle' => 'existing-url-handle',
        'description' => 'Test description',
        'is_active' => true,
    ]);

    $response->assertRedirectBack()
        ->assertInvalid('url_handle');

    assertDatabaseMissing('brands', [
        'name' => castAsTranslatableJson('New Brand'),
    ]);
});

test('automatically generates URL handle from brand name if URL handle is not provided', function () {
    $response = actingAsSuperAdmin()->post(route('admin.brands.store'), [
        'name' => 'New Test Brand',
        'description' => 'Test description',
        'is_active' => true,
    ]);

    $response->assertRedirectBack()
        ->assertSessionHasNoErrors();

    assertDatabaseHas('brands', [
        'name' => castAsTranslatableJson('New Test Brand'),
        'url_handle' => 'new-test-brand',
    ]);
});

test('validates required name field', function () {
    $response = actingAsSuperAdmin()->post(route('admin.brands.store'), [
        'url_handle' => 'test-brand',
        'is_active' => true,
    ]);

    $response->assertRedirectBack()
        ->assertInvalid('name');
});

test('validates required is_active field', function () {
    $response = actingAsSuperAdmin()->post(route('admin.brands.store'), [
        'name' => 'Test Brand',
        'url_handle' => 'test-brand',
    ]);

    $response->assertRedirectBack()
        ->assertInvalid('is_active');
});

test('requires authentication', function () {
    $response = post(route('admin.brands.store'), [
        'name' => 'Test Brand',
        'url_handle' => 'test-brand',
        'description' => 'Test description',
        'is_active' => true,
    ]);

    $response->assertRedirect(route('admin.login'));

    $response = post(route('admin.brands.store'), [
        'name' => 'Another Brand',
        'url_handle' => 'another-brand',
        'description' => 'Test description',
        'is_active' => true,
    ]);

    $response->assertRedirect(route('admin.login'));
});

test('requires brands.create permission', function () {
    $role = Role::query()->where(['name' => RoleEnum::Admin])->firstOrFail();

    $response = actingAsAdmin()->post(route('admin.brands.store'), [
        'name' => 'Test Brand',
        'url_handle' => 'test-brand',
        'description' => 'Test description',
        'is_active' => true,
    ]);

    $response->assertRedirectBack()
        ->assertSessionHasNoErrors();

    assertDatabaseHas('brands', [
        'url_handle' => 'test-brand',
    ]);

    $role->revokePermissionTo(Permission::BrandsManage);

    $response = actingAsAdmin()->post(route('admin.brands.store'), [
        'name' => 'Another Brand',
        'url_handle' => 'another-brand',
        'description' => 'Test description',
        'is_active' => true,
    ]);

    $response->assertForbidden();

    assertDatabaseMissing('brands', [
        'url_handle' => 'another-brand',
    ]);
});

test('rejects a brand URL handle that is not a valid slug', function (string $urlHandle) {
    $response = actingAsSuperAdmin()->post(route('admin.brands.store'), [
        'name' => 'Sluggish Brand',
        'url_handle' => $urlHandle,
        'is_active' => true,
    ]);

    $response->assertRedirectBack()
        ->assertInvalid('url_handle');

    assertDatabaseMissing('brands', [
        'url_handle' => $urlHandle,
    ]);
})->with(['Sluggish Brand', 'sluggish brand', 'sluggish,brand', '-sluggish-brand', 'sluggish--brand']);

test('accepts a brand URL handle that is a valid slug', function () {
    $response = actingAsSuperAdmin()->post(route('admin.brands.store'), [
        'name' => 'Sluggish Brand',
        'url_handle' => 'sluggish-brand-2',
        'is_active' => true,
    ]);

    $response->assertRedirectBack()
        ->assertSessionHasNoErrors();

    assertDatabaseHas('brands', [
        'url_handle' => 'sluggish-brand-2',
    ]);
});
