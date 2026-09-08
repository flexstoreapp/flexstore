<?php

declare(strict_types=1);

use App\Enums\Permission;
use App\Enums\ProductType;
use App\Http\Controllers\Api\V1\Admin\ProductController;
use App\Http\Requests\Admin\StoreProductRequest;
use App\Http\Resources\Api\V1\Admin\AdminProductResource;
use App\Models\Category;
use App\Models\Product;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;

covers(ProductController::class, StoreProductRequest::class, AdminProductResource::class);

uses()->group('api', 'admin-api');

test('a product is created', function (): void {
    actingAsApiAdmin(userWithPermissions([Permission::ProductsManage]));

    $category = Category::factory()->create();

    postJson('/api/v1/admin/products', [
        'title' => 'Api Product',
        'url_handle' => 'api-product',
        'type' => ProductType::Physical->value,
        'category_id' => $category->id,
        'is_tax_exempt' => true,
        'price' => '19.99',
        'track_stock' => true,
        'stock' => 10,
        'in_stock' => true,
        'is_active' => true,
    ])
        ->assertCreated()
        ->assertJsonPath('title', 'Api Product')
        ->assertJsonPath('url_handle', 'api-product')
        ->assertJsonPath('category.id', $category->id)
        ->assertJsonPath('is_active', true);

    assertDatabaseHas(Product::class, ['url_handle' => 'api-product']);
});

test('creating a product validates the payload', function (): void {
    actingAsApiAdmin(userWithPermissions([Permission::ProductsManage]));

    postJson('/api/v1/admin/products', [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['title', 'type', 'is_tax_exempt', 'is_active']);
});

test('the created product round-trips the cost per item', function (): void {
    actingAsApiAdmin(userWithPermissions([Permission::ProductsManage]));

    $response = postJson('/api/v1/admin/products', [
        'title' => 'Costed Product',
        'type' => ProductType::Physical->value,
        'is_tax_exempt' => true,
        'price' => '19.99',
        'cost_per_item' => '5.00',
        'track_stock' => false,
        'in_stock' => true,
        'is_active' => true,
    ])->assertCreated();

    expect($response->json('cost_per_item'))->toBe('5.0000');
});

test('creating a product requires the products manage permission', function (): void {
    actingAsApiAdmin(userWithPermissions([Permission::ProductsView]));

    postJson('/api/v1/admin/products', [])->assertForbidden();
});

test('a product is shown with its options, variants and downloads', function (): void {
    actingAsApiAdmin(userWithPermissions([Permission::ProductsView]));

    $product = Product::factory()->withVariants()->create(['title' => 'Shown Product']);

    $response = getJson('/api/v1/admin/products/' . $product->id)
        ->assertOk()
        ->assertJsonPath('id', $product->id)
        ->assertJsonPath('title', 'Shown Product')
        ->assertJsonStructure(['id', 'type', 'url_handle', 'title', 'options', 'variants', 'downloads', 'cross_sells', 'up_sells']);

    expect($response->json('variants'))->not->toBeEmpty()
        ->and($response->json('options.0.values'))->not->toBeEmpty()
        ->and($response->json('variants.0'))->toHaveKey('cost_per_item');
});

test('a read-only staff member can open a product', function (): void {
    actingAsApiAdmin(userWithPermissions([Permission::ProductsView]));

    $product = Product::factory()->create();

    getJson('/api/v1/admin/products/' . $product->id)->assertOk();
});
