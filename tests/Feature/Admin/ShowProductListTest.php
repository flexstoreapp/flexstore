<?php

declare(strict_types=1);

use App\Actions\SyncMediaAction;
use App\Enums\Permission;
use App\Enums\Role as RoleEnum;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Requests\Admin\IndexAdminProductRequest;
use App\Models\Category;
use App\Models\Media;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Queries\ProductListQuery;
use Inertia\Testing\AssertableInertia;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\get;

covers(ProductController::class, ProductListQuery::class, IndexAdminProductRequest::class);

uses()->group('product');

test('displays product list page', function () {
    Product::factory(3)->create();

    $response = actingAsSuperAdmin()->get(route('admin.products.index'));

    $response->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('admin/products/list')
                ->has('products.data', 3)
                ->where('filters.query', null)
                ->where('filters.category', null)
                ->where('filters.category_name', null)
                ->where('filters.in_stock', null)
                ->where('filters.is_active', null)
                ->where('filters.sort', 'created_at')
                ->where('filters.direction', 'desc')
        );
});

test('can filter products by query term', function () {
    Product::factory()->create(['title' => 'Test Product']);
    Product::factory()->create(['title' => 'Another Product']);

    $response = actingAsSuperAdmin()->get(route('admin.products.index', [
        'query' => 'Test',
    ]));

    $response->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('admin/products/list')
                ->has('products.data', 1)
                ->where('products.data.0.title', castAsTranslatableArray('Test Product'))
        );
});

test('can filter products by URL handle', function () {
    Product::factory()->create(['title' => 'Test Product', 'url_handle' => 'test-product']);
    Product::factory()->create(['title' => 'Another Product', 'url_handle' => 'another-product']);

    $response = actingAsSuperAdmin()->get(route('admin.products.index', [
        'query' => 'test-product',
    ]));

    $response->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('admin/products/list')
                ->has('products.data', 1)
                ->where('products.data.0.title', castAsTranslatableArray('Test Product'))
        );
});

test('can filter products by category', function () {
    $category = Category::factory()->create(['name' => 'Electronics']);

    Product::factory()->create([
        'title' => 'Phone',
        'category_id' => $category->id,
    ]);

    Product::factory(2)->create();

    $response = actingAsSuperAdmin()->get(route('admin.products.index', ['category' => $category->id]));

    $response->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('admin/products/list')
                ->has('products.data', 1)
                ->where('products.data.0.title', castAsTranslatableArray('Phone'))
        );
});

test('can filter products by active status', function () {
    Product::factory()->create(['title' => 'Active Product', 'is_active' => true]);
    Product::factory()->create(['title' => 'Inactive Product', 'is_active' => false]);

    $response = actingAsSuperAdmin()->get(route('admin.products.index', [
        'is_active' => true,
    ]));

    $response->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('admin/products/list')
                ->has('products.data', 1)
                ->where('products.data.0.title', castAsTranslatableArray('Active Product'))
                ->where('products.data.0.is_active', true)
        );
});

test('can filter products by stock status', function () {
    Product::factory()->create(['title' => 'In Stock Product', 'in_stock' => true]);
    Product::factory()->create(['title' => 'Out of Stock Product', 'in_stock' => false]);

    $response = actingAsSuperAdmin()->get(route('admin.products.index', [
        'in_stock' => true,
    ]));

    $response->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('admin/products/list')
                ->has('products.data', 1)
                ->where('products.data.0.title', castAsTranslatableArray('In Stock Product'))
        );
});

test('can sort products', function () {
    Product::factory()->create(['title' => 'A Product', 'price' => '10.3500']);
    Product::factory()->create(['title' => 'B Product', 'price' => '20.3500']);

    $response = actingAsSuperAdmin()->get(route('admin.products.index', [
        'sort' => 'price',
        'direction' => 'desc',
    ]));

    $response->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('admin/products/list')
                ->has('products.data', 2)
                ->where('products.data.0.title', castAsTranslatableArray('B Product'))
                ->where('products.data.0.price', '20.3500')
        );
});

test('requires authentication', function () {
    Product::factory()->count(2)->create();

    $response = get(route('admin.products.index'));

    $response->assertRedirect(route('admin.login'));
});

test('requires products.view permission', function () {
    $role = Role::query()->where(['name' => RoleEnum::Admin])->firstOrFail();
    Product::factory()->count(2)->create();

    $response = actingAsAdmin()->get(route('admin.products.index'));

    $response->assertOk();

    $role->revokePermissionTo(Permission::ProductsView);

    $response = actingAsAdmin()->get(route('admin.products.index'));

    $response->assertForbidden();
});

test('product thumbnail uses product media even when a variant has its own image', function () {
    $product = Product::factory()->create([
        'title' => 'Product With Variant',
    ]);
    $productMedia = Media::factory()->create([
        'path' => 'images/products/product-image.jpg',
        'thumbnail_path' => 'thumbnails/products/product-image.jpg',
        'external_url' => null,
    ]);
    (new SyncMediaAction())->handle($product, [$productMedia->id]);

    $variant = ProductVariant::factory()->for($product)->create([
        'is_default' => true,
    ]);
    $variantMedia = Media::factory()->create([
        'path' => 'images/variants/variant-image.jpg',
        'thumbnail_path' => 'thumbnails/variants/variant-image.jpg',
        'external_url' => null,
    ]);
    $variant->update(['media_id' => $variantMedia->id]);

    $response = actingAsSuperAdmin()->get(route('admin.products.index'));

    $response->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('admin/products/list')
                ->has('products.data', 1)
                ->where('products.data.0.featured_media.thumbnail_url', fn ($thumbnail) => str_contains($thumbnail, 'thumbnails/products/product-image.jpg'))
        );
});

test('product thumbnail uses own image when the product has no variants', function () {
    $product = Product::factory()->create([
        'title' => 'Product Without Variant',
    ]);
    $media = Media::factory()->create([
        'path' => 'images/products/own-image.jpg',
        'thumbnail_path' => 'thumbnails/products/own-image.jpg',
        'external_url' => null,
    ]);
    (new SyncMediaAction())->handle($product, [$media->id]);

    $response = actingAsSuperAdmin()->get(route('admin.products.index'));

    $response->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('admin/products/list')
                ->has('products.data', 1)
                ->where('products.data.0.featured_media.thumbnail_url', fn ($thumbnail) => str_contains($thumbnail, 'thumbnails/products/own-image.jpg'))
        );
});

test('product list does not include hidden fields', function () {
    $product = Product::factory()->withMedia(1)->create();
    ProductVariant::factory()->for($product)->create(['is_default' => true]);

    $response = actingAsSuperAdmin()->get(route('admin.products.index'));

    $response->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('admin/products/list')
                ->has('products.data', 1)
                ->has('products.data.0.featured_media')
                ->has('products.data.0.category')
                ->missing('products.data.0.media')
                ->missing('products.data.0.stock')
        );
});
