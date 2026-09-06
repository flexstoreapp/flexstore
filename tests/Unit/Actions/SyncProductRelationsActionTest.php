<?php

declare(strict_types=1);

use App\Actions\SyncProductRelationsAction;
use App\Enums\ProductRelationType;
use App\Models\Product;

use function Pest\Laravel\assertDatabaseCount;

covers(SyncProductRelationsAction::class);

uses()->group('actions', 'product');

test('attaches the related products in the given order', function () {
    $product = Product::factory()->create();
    $first = Product::factory()->create();
    $second = Product::factory()->create();

    app(SyncProductRelationsAction::class)->handle($product, ProductRelationType::CrossSell, [$second->id, $first->id]);

    expect($product->crossSells->pluck('id')->all())->toBe([$second->id, $first->id])
        ->and($product->crossSells->pluck('pivot.sort_order')->all())->toBe([0, 1]);
});

test('detaches the relations that are no longer submitted', function () {
    $product = Product::factory()->create();
    $kept = Product::factory()->create();
    $dropped = Product::factory()->create();

    $action = app(SyncProductRelationsAction::class);
    $action->handle($product, ProductRelationType::CrossSell, [$kept->id, $dropped->id]);
    $action->handle($product, ProductRelationType::CrossSell, [$kept->id]);

    expect($product->load('crossSells')->crossSells->pluck('id')->all())->toBe([$kept->id]);
});

test('leaves the other relation type untouched', function () {
    $product = Product::factory()->create();
    $upSell = Product::factory()->create();
    $crossSell = Product::factory()->create();

    $action = app(SyncProductRelationsAction::class);
    $action->handle($product, ProductRelationType::UpSell, [$upSell->id]);
    $action->handle($product, ProductRelationType::CrossSell, [$crossSell->id]);

    expect($product->upSells->pluck('id')->all())->toBe([$upSell->id])
        ->and($product->crossSells->pluck('id')->all())->toBe([$crossSell->id]);
});

test('ignores the product itself and duplicate ids', function () {
    $product = Product::factory()->create();
    $related = Product::factory()->create();

    app(SyncProductRelationsAction::class)->handle(
        $product,
        ProductRelationType::UpSell,
        [$product->id, $related->id, $related->id],
    );

    expect($product->upSells->pluck('id')->all())->toBe([$related->id]);
    assertDatabaseCount('product_relations', 1);
});

test('an empty list clears the relations', function () {
    $product = Product::factory()->create();
    $related = Product::factory()->create();

    $action = app(SyncProductRelationsAction::class);
    $action->handle($product, ProductRelationType::CrossSell, [$related->id]);
    $action->handle($product, ProductRelationType::CrossSell, []);

    expect($product->load('crossSells')->crossSells)->toBeEmpty();
    assertDatabaseCount('product_relations', 0);
});

test('deleting a product removes the relations pointing at it', function () {
    $product = Product::factory()->create();
    $related = Product::factory()->create();

    app(SyncProductRelationsAction::class)->handle($product, ProductRelationType::CrossSell, [$related->id]);

    $related->delete();

    assertDatabaseCount('product_relations', 0);
});
