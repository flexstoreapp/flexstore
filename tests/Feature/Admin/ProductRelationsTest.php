<?php

declare(strict_types=1);

use App\Actions\StoreProductAction;
use App\Actions\SyncProductRelationsAction;
use App\Actions\UpdateProductAction;
use App\Enums\ProductRelationType;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Requests\Admin\StoreProductRequest;
use App\Http\Requests\Admin\UpdateProductRequest;
use App\Models\Product;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;

covers([
    ProductController::class,
    StoreProductRequest::class,
    UpdateProductRequest::class,
    StoreProductAction::class,
    UpdateProductAction::class,
    SyncProductRelationsAction::class,
]);

uses()->group('product');

test('cross-sells and up-sells are stored with a new product', function () {
    $crossSell = Product::factory()->create();
    $upSell = Product::factory()->create();

    actingAsSuperAdmin()
        ->post(route('admin.products.store'), [
            'title' => 'Product with relations',
            'type' => 'physical',
            'price' => '19.9900',
            'track_stock' => false,
            'in_stock' => true,
            'is_tax_exempt' => true,
            'is_active' => true,
            'cross_sells' => [$crossSell->id],
            'up_sells' => [$upSell->id],
        ])
        ->assertSessionHasNoErrors();

    $product = Product::query()->where('url_handle', 'product-with-relations')->sole();

    assertDatabaseHas('product_relations', [
        'product_id' => $product->id,
        'related_product_id' => $crossSell->id,
        'relation_type' => ProductRelationType::CrossSell->value,
        'sort_order' => 0,
    ]);

    assertDatabaseHas('product_relations', [
        'product_id' => $product->id,
        'related_product_id' => $upSell->id,
        'relation_type' => ProductRelationType::UpSell->value,
    ]);
});

test('cross-sells are replaced on update and keep their order', function () {
    $product = Product::factory()->create();
    $first = Product::factory()->create();
    $second = Product::factory()->create();
    $stale = Product::factory()->create();

    app(SyncProductRelationsAction::class)->handle($product, ProductRelationType::CrossSell, [$stale->id]);

    actingAsSuperAdmin()
        ->patch(route('admin.products.update', $product), [
            'cross_sells' => [$second->id, $first->id],
        ])
        ->assertSessionHasNoErrors();

    assertDatabaseMissing('product_relations', ['related_product_id' => $stale->id]);

    expect($product->refresh()->crossSells->pluck('id')->all())->toBe([$second->id, $first->id]);
});

test('submitting an empty array clears the up-sells', function () {
    $product = Product::factory()->create();
    $upSell = Product::factory()->create();

    app(SyncProductRelationsAction::class)->handle($product, ProductRelationType::UpSell, [$upSell->id]);

    actingAsSuperAdmin()
        ->patch(route('admin.products.update', $product), ['up_sells' => []])
        ->assertSessionHasNoErrors();

    expect($product->refresh()->upSells)->toBeEmpty();
});

test('relations are left untouched when the keys are omitted', function () {
    $product = Product::factory()->create();
    $crossSell = Product::factory()->create();

    app(SyncProductRelationsAction::class)->handle($product, ProductRelationType::CrossSell, [$crossSell->id]);

    actingAsSuperAdmin()
        ->patch(route('admin.products.update', $product), ['title' => 'Renamed'])
        ->assertSessionHasNoErrors();

    expect($product->refresh()->crossSells->pluck('id')->all())->toBe([$crossSell->id]);
});

test('a product cannot cross-sell itself', function () {
    $product = Product::factory()->create();

    actingAsSuperAdmin()
        ->patch(route('admin.products.update', $product), ['cross_sells' => [$product->id]])
        ->assertSessionHasErrors('cross_sells.0');
});

test('validates that related products exist', function () {
    $product = Product::factory()->create();

    actingAsSuperAdmin()
        ->patch(route('admin.products.update', $product), ['up_sells' => [999999]])
        ->assertSessionHasErrors('up_sells.0');
});

test('the edit page exposes the selected relations', function () {
    $product = Product::factory()->create();
    $crossSell = Product::factory()->create();
    $upSell = Product::factory()->create();

    app(SyncProductRelationsAction::class)->handle($product, ProductRelationType::CrossSell, [$crossSell->id]);
    app(SyncProductRelationsAction::class)->handle($product, ProductRelationType::UpSell, [$upSell->id]);

    actingAsSuperAdmin()
        ->get(route('admin.products.edit', $product))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('admin/products/edit')
            ->has('product.cross_sells', 1)
            ->where('product.cross_sells.0.id', $crossSell->id)
            ->has('product.up_sells', 1)
            ->where('product.up_sells.0.id', $upSell->id));
});
