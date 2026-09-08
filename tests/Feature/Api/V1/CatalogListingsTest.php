<?php

declare(strict_types=1);

use App\Enums\ProductRelationType;
use App\Http\Controllers\Api\V1\BrandController;
use App\Http\Controllers\Api\V1\CartCrossSellController;
use App\Http\Controllers\Api\V1\RelatedProductController;
use App\Http\Controllers\Api\V1\SearchSuggestionController;
use App\Http\Controllers\Api\V1\UpSellProductController;
use App\Models\Brand;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Product;
use App\Models\Setting;

use function Pest\Laravel\getJson;

covers(
    BrandController::class,
    SearchSuggestionController::class,
    RelatedProductController::class,
    UpSellProductController::class,
    CartCrossSellController::class,
);

uses()->group('api');

test('active brands are listed with the name as a plain string', function (): void {
    $brand = Brand::factory()->create(['is_active' => true, 'name' => 'Northwind']);
    Brand::factory()->create(['is_active' => false]);

    getJson(route('api.v1.brands.index'))
        ->assertOk()
        ->assertJsonCount(1)
        ->assertJsonPath('0.id', $brand->id)
        ->assertJsonPath('0.name', 'Northwind');
});

test('search suggestions match on the product title', function (): void {
    $product = Product::factory()->available()->create(['title' => 'Merino crew knit']);

    $response = getJson(route('api.v1.search.suggestions', ['query' => 'merino']));

    $response->assertOk()
        ->assertJsonPath('data.0.id', $product->id)
        ->assertJsonPath('meta.query', 'merino');

    expect($response->json('data.0.title'))->toBe('Merino crew knit');
});

test('a query shorter than two characters returns nothing', function (): void {
    Product::factory()->available()->create();

    getJson(route('api.v1.search.suggestions', ['query' => 'm']))
        ->assertOk()
        ->assertJsonCount(0, 'data')
        ->assertJsonPath('meta.total', 0);
});

test('related products come from the same category', function (): void {
    $category = Category::factory()->create(['is_active' => true]);
    $product = Product::factory()->available()->create(['category_id' => $category->id]);
    $sibling = Product::factory()->available()->create(['category_id' => $category->id]);

    getJson(route('api.v1.products.related', $product->url_handle))
        ->assertOk()
        ->assertJsonPath('0.id', $sibling->id);
});

test('up-sells are the products the merchant picked', function (): void {
    $product = Product::factory()->available()->create();
    $upSell = Product::factory()->available()->create();
    Product::factory()->available()->create();

    $product->relatedProductsOfType(ProductRelationType::UpSell)
        ->attach($upSell->id, ['relation_type' => ProductRelationType::UpSell->value, 'sort_order' => 0]);

    getJson(route('api.v1.products.up-sells', $product->url_handle))
        ->assertOk()
        ->assertJsonCount(1)
        ->assertJsonPath('0.id', $upSell->id);
});

test('cross-sells are returned for what is already in the cart', function (): void {
    Setting::setValue('storefront_cart_show_cross_sells', true);

    $cart = Cart::factory()->create();
    $product = Product::factory()->available()->create();
    $crossSell = Product::factory()->available()->create();

    CartItem::factory()->create(['cart_id' => $cart->id, 'product_id' => $product->id]);
    $product->relatedProductsOfType(ProductRelationType::CrossSell)
        ->attach($crossSell->id, ['relation_type' => ProductRelationType::CrossSell->value, 'sort_order' => 0]);

    getJson(route('api.v1.cart.cross-sells'), ['X-Cart-Token' => $cart->id])
        ->assertOk()
        ->assertJsonPath('0.id', $crossSell->id);
});
