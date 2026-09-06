<?php

declare(strict_types=1);

use App\Actions\SyncProductRelationsAction;
use App\Enums\ProductRelationType;
use App\Models\Product;
use App\Queries\ProductRelationsQuery;

covers(ProductRelationsQuery::class);

uses()->group('storefront', 'product');

test('returns the cross-sells in the order they were picked', function () {
    $product = Product::factory()->available()->create();
    $first = Product::factory()->available()->create();
    $second = Product::factory()->available()->create();

    app(SyncProductRelationsAction::class)->handle($product, ProductRelationType::CrossSell, [$second->id, $first->id]);

    $cards = app(ProductRelationsQuery::class)->execute($product, ProductRelationType::CrossSell);

    expect(collect($cards)->pluck('id')->all())->toBe([$second->id, $first->id]);
});

test('returns only the requested relation type', function () {
    $product = Product::factory()->available()->create();
    $crossSell = Product::factory()->available()->create();
    $upSell = Product::factory()->available()->create();

    $action = app(SyncProductRelationsAction::class);
    $action->handle($product, ProductRelationType::CrossSell, [$crossSell->id]);
    $action->handle($product, ProductRelationType::UpSell, [$upSell->id]);

    $cards = app(ProductRelationsQuery::class)->execute($product, ProductRelationType::UpSell);

    expect(collect($cards)->pluck('id')->all())->toBe([$upSell->id]);
});

test('skips inactive related products', function () {
    $product = Product::factory()->available()->create();
    $active = Product::factory()->available()->create();
    $inactive = Product::factory()->inactive()->create();

    app(SyncProductRelationsAction::class)->handle($product, ProductRelationType::CrossSell, [$inactive->id, $active->id]);

    $cards = app(ProductRelationsQuery::class)->execute($product, ProductRelationType::CrossSell);

    expect(collect($cards)->pluck('id')->all())->toBe([$active->id]);
});

test('returns an empty list when nothing is picked', function () {
    $product = Product::factory()->available()->create();

    expect(app(ProductRelationsQuery::class)->execute($product, ProductRelationType::CrossSell))->toBe([]);
});
