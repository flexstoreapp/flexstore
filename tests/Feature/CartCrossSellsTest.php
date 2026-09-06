<?php

declare(strict_types=1);

use App\Actions\SyncProductRelationsAction;
use App\Enums\ProductRelationType;
use App\Http\Controllers\Storefront\CartController;
use App\Http\Controllers\Storefront\CartCrossSellController;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\Setting;
use App\Queries\CartCrossSellsQuery;

use function Pest\Laravel\withUnencryptedCookie;

covers([
    CartController::class,
    CartCrossSellController::class,
    CartCrossSellsQuery::class,
]);

uses()->group('cart', 'product');

function cartWithProduct(Product $product): Cart
{
    $cart = Cart::factory()->create();

    CartItem::factory()->for($cart)->create([
        'product_id' => $product->id,
        'quantity' => 1,
        'unit_price' => '25.0000',
        'total_price' => '25.0000',
    ]);

    return $cart;
}

test('the cross-sell endpoint returns the cross-sells of the products in the cart', function () {
    $product = Product::factory()->available()->create();
    $crossSell = Product::factory()->available()->create();

    app(SyncProductRelationsAction::class)->handle($product, ProductRelationType::CrossSell, [$crossSell->id]);

    withUnencryptedCookie('cart_id', cartWithProduct($product)->id)
        ->get(route('cart.cross-sells'))
        ->assertOk()
        ->assertJsonCount(1)
        ->assertJsonPath('0.id', $crossSell->id);
});

test('products already in the cart are not suggested', function () {
    $product = Product::factory()->available()->create();
    $crossSell = Product::factory()->available()->create();

    app(SyncProductRelationsAction::class)->handle($product, ProductRelationType::CrossSell, [$crossSell->id]);

    $cart = cartWithProduct($product);
    CartItem::factory()->for($cart)->create([
        'product_id' => $crossSell->id,
        'quantity' => 1,
        'unit_price' => '25.0000',
        'total_price' => '25.0000',
    ]);

    withUnencryptedCookie('cart_id', $cart->id)
        ->get(route('cart.cross-sells'))
        ->assertOk()
        ->assertJsonCount(0);
});

test('inactive cross-sells do not use up the suggestion limit', function () {
    $product = Product::factory()->available()->create();

    $inactive = Product::factory()->available()->count(3)->create(['is_active' => false]);
    $active = Product::factory()->available()->create();

    app(SyncProductRelationsAction::class)->handle(
        $product,
        ProductRelationType::CrossSell,
        [...$inactive->pluck('id')->all(), $active->id],
    );

    withUnencryptedCookie('cart_id', cartWithProduct($product)->id)
        ->get(route('cart.cross-sells'))
        ->assertOk()
        ->assertJsonCount(1)
        ->assertJsonPath('0.id', $active->id);
});

test('up-sells are not suggested in the cart', function () {
    $product = Product::factory()->available()->create();
    $upSell = Product::factory()->available()->create();

    app(SyncProductRelationsAction::class)->handle($product, ProductRelationType::UpSell, [$upSell->id]);

    withUnencryptedCookie('cart_id', cartWithProduct($product)->id)
        ->get(route('cart.cross-sells'))
        ->assertOk()
        ->assertJsonCount(0);
});

test('an empty cart suggests nothing', function () {
    withUnencryptedCookie('cart_id', Cart::factory()->create()->id)
        ->get(route('cart.cross-sells'))
        ->assertOk()
        ->assertJsonCount(0);
});

test('deferred cart cross-sells resolve on a partial reload', function () {
    $product = Product::factory()->available()->create();
    $crossSell = Product::factory()->available()->create();

    app(SyncProductRelationsAction::class)->handle($product, ProductRelationType::CrossSell, [$crossSell->id]);

    $cart = cartWithProduct($product);

    withUnencryptedCookie('cart_id', $cart->id)->get(route('cart.show'))->assertOk();

    withUnencryptedCookie('cart_id', $cart->id)
        ->withHeaders([
            'X-Inertia' => 'true',
            'X-Inertia-Version' => app(Inertia\ResponseFactory::class)->getVersion(),
            'X-Inertia-Partial-Component' => 'storefront/cart/show',
            'X-Inertia-Partial-Data' => 'crossSellProducts',
        ])
        ->get(route('cart.show'))
        ->assertOk()
        ->assertJsonCount(1, 'props.crossSellProducts')
        ->assertJsonPath('props.crossSellProducts.0.id', $crossSell->id);
});

test('the cart suggests nothing when the cross-sell section is disabled', function () {
    Setting::setValue('storefront_cart_show_cross_sells', false);

    $product = Product::factory()->available()->create();
    $crossSell = Product::factory()->available()->create();

    app(SyncProductRelationsAction::class)->handle($product, ProductRelationType::CrossSell, [$crossSell->id]);

    $cart = cartWithProduct($product);

    withUnencryptedCookie('cart_id', $cart->id)
        ->get(route('cart.cross-sells'))
        ->assertOk()
        ->assertJsonCount(0);

    withUnencryptedCookie('cart_id', $cart->id)
        ->get(route('cart.show'))
        ->assertInertia(fn (Inertia\Testing\AssertableInertia $page) => $page->missing('crossSellProducts'));
});
