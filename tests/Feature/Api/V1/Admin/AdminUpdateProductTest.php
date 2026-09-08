<?php

declare(strict_types=1);

use App\Actions\UpdateProductAction;
use App\Enums\Permission;
use App\Http\Controllers\Api\V1\Admin\ProductController;
use App\Http\Requests\Admin\UpdateProductRequest;
use App\Models\Product;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\patchJson;

covers(ProductController::class, UpdateProductRequest::class, UpdateProductAction::class);

uses()->group('api', 'admin-api');

test('a product is partially updated', function (): void {
    actingAsApiAdmin(userWithPermissions([Permission::ProductsManage]));

    $product = Product::factory()->create([
        'title' => 'Old Title',
        'url_handle' => 'old-title',
        'is_active' => true,
    ]);

    patchJson('/api/v1/admin/products/' . $product->id, ['title' => 'New Title'])
        ->assertOk()
        ->assertJsonPath('title', 'New Title')
        ->assertJsonPath('url_handle', 'old-title');

    assertDatabaseHas(Product::class, [
        'id' => $product->id,
        'url_handle' => 'old-title',
        'is_active' => true,
    ]);
});

test('an update rejects a duplicate url handle', function (): void {
    actingAsApiAdmin(userWithPermissions([Permission::ProductsManage]));

    Product::factory()->create(['url_handle' => 'taken-handle']);
    $product = Product::factory()->create();

    patchJson('/api/v1/admin/products/' . $product->id, ['url_handle' => 'taken-handle'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('url_handle');
});

test('updating a product requires the products manage permission', function (): void {
    actingAsApiAdmin(userWithPermissions([Permission::ProductsView]));

    $product = Product::factory()->create();

    patchJson('/api/v1/admin/products/' . $product->id, ['title' => 'Nope'])->assertForbidden();
});
