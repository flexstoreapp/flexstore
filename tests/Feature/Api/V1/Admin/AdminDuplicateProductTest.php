<?php

declare(strict_types=1);

use App\Actions\DuplicateProductAction;
use App\Enums\Permission;
use App\Http\Controllers\Api\V1\Admin\DuplicateProductController;
use App\Http\Requests\Admin\DuplicateProductRequest;
use App\Models\Product;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\postJson;

covers(DuplicateProductController::class, DuplicateProductRequest::class, DuplicateProductAction::class);

uses()->group('api', 'admin-api');

test('a product is duplicated', function (): void {
    actingAsApiAdmin(userWithPermissions([Permission::ProductsManage]));

    $product = Product::factory()->create([
        'title' => 'Original Product',
        'url_handle' => 'original-product',
        'sku' => 'ORIGINAL-SKU',
    ]);

    $response = postJson('/api/v1/admin/products/' . $product->id . '/duplicate', [
        'title' => 'Copied Product',
        'is_active' => false,
        'duplicate_skus' => true,
    ])
        ->assertCreated()
        ->assertJsonPath('title', 'Copied Product')
        ->assertJsonPath('is_active', false);

    expect($response->json('id'))->not->toBe($product->id);

    assertDatabaseHas(Product::class, ['url_handle' => 'copied-product']);
});

test('duplicating a product validates the title', function (): void {
    actingAsApiAdmin(userWithPermissions([Permission::ProductsManage]));

    $product = Product::factory()->create();

    postJson('/api/v1/admin/products/' . $product->id . '/duplicate', [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('title');
});

test('duplicating a product requires the products manage permission', function (): void {
    actingAsApiAdmin(userWithPermissions([Permission::ProductsView]));

    $product = Product::factory()->create();

    postJson('/api/v1/admin/products/' . $product->id . '/duplicate', ['title' => 'Copy'])
        ->assertForbidden();
});
