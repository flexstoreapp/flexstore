<?php

declare(strict_types=1);

use App\Actions\BulkDestroyProductAction;
use App\Enums\Permission;
use App\Http\Controllers\Api\V1\Admin\BulkProductController;
use App\Http\Controllers\Api\V1\Admin\ProductController;
use App\Http\Requests\Admin\BulkDestroyProductRequest;
use App\Models\Product;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Laravel\deleteJson;

covers(
    ProductController::class,
    BulkProductController::class,
    BulkDestroyProductRequest::class,
    BulkDestroyProductAction::class,
);

uses()->group('api', 'admin-api');

test('a product is deleted', function (): void {
    actingAsApiAdmin(userWithPermissions([Permission::ProductsDelete]));

    $product = Product::factory()->create();

    deleteJson('/api/v1/admin/products/' . $product->id)->assertNoContent();

    assertDatabaseMissing(Product::class, ['id' => $product->id]);
});

test('products are deleted in bulk', function (): void {
    actingAsApiAdmin(userWithPermissions([Permission::ProductsDelete]));

    $first = Product::factory()->create();
    $second = Product::factory()->create();
    $kept = Product::factory()->create();

    deleteJson('/api/v1/admin/products/bulk', ['ids' => [$first->id, $second->id]])
        ->assertNoContent();

    assertDatabaseMissing(Product::class, ['id' => $first->id]);
    assertDatabaseMissing(Product::class, ['id' => $second->id]);
    assertDatabaseHas(Product::class, ['id' => $kept->id]);
});

test('a bulk delete validates the ids', function (): void {
    actingAsApiAdmin(userWithPermissions([Permission::ProductsDelete]));

    deleteJson('/api/v1/admin/products/bulk', ['ids' => [999999]])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('ids');
});

test('deleting a product requires the products delete permission', function (): void {
    actingAsApiAdmin(userWithPermissions([Permission::ProductsManage]));

    $product = Product::factory()->create();

    deleteJson('/api/v1/admin/products/' . $product->id)->assertForbidden();
    deleteJson('/api/v1/admin/products/bulk', ['ids' => [$product->id]])->assertForbidden();
});
