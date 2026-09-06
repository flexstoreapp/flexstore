<?php

declare(strict_types=1);

use App\Actions\SyncProductRelationsAction;
use App\Enums\ProductRelationType;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Queries\CartCrossSellsQuery;

covers(CartCrossSellsQuery::class);

uses()->group('storefront', 'cart', 'product');

function cartContaining(Product ...$products): Cart
{
    $cart = Cart::factory()->create();

    foreach ($products as $product) {
        CartItem::factory()->for($cart)->create([
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price' => '25.0000',
            'total_price' => '25.0000',
        ]);
    }

    return $cart;
}

test('collects the cross-sells of every product in the cart', function () {
    $first = Product::factory()->available()->create();
    $second = Product::factory()->available()->create();
    $firstCrossSell = Product::factory()->available()->create();
    $secondCrossSell = Product::factory()->available()->create();

    $action = app(SyncProductRelationsAction::class);
    $action->handle($first, ProductRelationType::CrossSell, [$firstCrossSell->id]);
    $action->handle($second, ProductRelationType::CrossSell, [$secondCrossSell->id]);

    $cards = app(CartCrossSellsQuery::class)->execute(cartContaining($first, $second), 4);

    expect(collect($cards)->pluck('id')->all())
        ->toEqualCanonicalizing([$firstCrossSell->id, $secondCrossSell->id]);
});

test('suggests a shared cross-sell only once', function () {
    $first = Product::factory()->available()->create();
    $second = Product::factory()->available()->create();
    $shared = Product::factory()->available()->create();

    $action = app(SyncProductRelationsAction::class);
    $action->handle($first, ProductRelationType::CrossSell, [$shared->id]);
    $action->handle($second, ProductRelationType::CrossSell, [$shared->id]);

    $cards = app(CartCrossSellsQuery::class)->execute(cartContaining($first, $second), 4);

    expect(collect($cards)->pluck('id')->all())->toBe([$shared->id]);
});

test('fills the limit with active cross-sells when the first picks are inactive', function () {
    $product = Product::factory()->available()->create();
    $inactive = Product::factory()->inactive()->create();
    $active = Product::factory()->available()->create();

    app(SyncProductRelationsAction::class)->handle($product, ProductRelationType::CrossSell, [$inactive->id, $active->id]);

    $cards = app(CartCrossSellsQuery::class)->execute(cartContaining($product), 1);

    expect(collect($cards)->pluck('id')->all())->toBe([$active->id]);
});

test('respects the requested limit', function () {
    $product = Product::factory()->available()->create();
    $crossSells = Product::factory()->available()->count(3)->create();

    app(SyncProductRelationsAction::class)->handle($product, ProductRelationType::CrossSell, $crossSells->pluck('id')->all());

    expect(app(CartCrossSellsQuery::class)->execute(cartContaining($product), 2))->toHaveCount(2);
});

test('returns an empty list for an empty cart', function () {
    expect(app(CartCrossSellsQuery::class)->execute(Cart::factory()->create(), 4))->toBe([]);
});
