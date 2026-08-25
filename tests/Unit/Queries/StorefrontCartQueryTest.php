<?php

declare(strict_types=1);

use App\Actions\SyncMediaAction;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Media;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Queries\StorefrontCartQuery;

covers(StorefrontCartQuery::class);

uses()->group('queries', 'storefront');

test('returns the given cart', function () {
    $cart = Cart::factory()->create();

    $result = app(StorefrontCartQuery::class)->execute($cart);

    expect($result)->toBeInstanceOf(Cart::class)
        ->and($result->id)->toBe($cart->id);
});

test('loads cart items with product relationships', function () {
    $cart = Cart::factory()->create();
    $product = Product::factory()->create();
    CartItem::factory()->for($cart)->create([
        'product_id' => $product->id,
    ]);

    $query = app(StorefrontCartQuery::class);
    $result = $query->execute($cart);

    expect($result->items)->toHaveCount(1)
        ->and($result->items->first()->product)->not->toBeNull();
});

test('cart item products include featured media attribute', function () {
    $cart = Cart::factory()->create();
    $media = Media::factory()->uploaded()->create();
    $product = Product::factory()->create();
    (new SyncMediaAction())->handle($product, [$media->id]);
    CartItem::factory()->for($cart)->create([
        'product_id' => $product->id,
    ]);

    $query = app(StorefrontCartQuery::class);
    $result = $query->execute($cart);

    $cartProduct = $result->items->first()->product;

    expect($cartProduct->toArray())->toHaveKey('featured_media')
        ->and($cartProduct->featured_media?->id)->toBe($media->id)
        ->and($cartProduct->featured_media?->thumbnail_url)->toBe($media->thumbnail_url);
});

test('cart item products handle missing images gracefully', function () {
    $cart = Cart::factory()->create();
    $product = Product::factory()->create();
    CartItem::factory()->for($cart)->create([
        'product_id' => $product->id,
    ]);

    $query = app(StorefrontCartQuery::class);
    $result = $query->execute($cart);

    $cartProduct = $result->items->first()->product;

    expect($cartProduct->toArray())->toHaveKey('featured_media')
        ->and($cartProduct->featured_media)->toBeNull();
});

test('cart lines without a variant use the product media', function () {
    $cart = Cart::factory()->create();
    $media = Media::factory()->uploaded()->create();
    $product = Product::factory()->create();
    (new SyncMediaAction())->handle($product, [$media->id]);
    $variant = ProductVariant::factory()->for($product)->create([
        'is_default' => true,
    ]);
    $variant->update(['media_id' => Media::factory()->uploaded()->create()->id]);
    CartItem::factory()->for($cart)->create([
        'product_id' => $product->id,
    ]);

    $query = app(StorefrontCartQuery::class);
    $result = $query->execute($cart);

    $cartProduct = $result->items->first()->product;

    expect($cartProduct->featured_media?->id)->toBe($media->id)
        ->and($cartProduct->featured_media?->thumbnail_url)->toBe($media->thumbnail_url);
});

test('cart lines expose the variant media when the line has a variant', function () {
    $cart = Cart::factory()->create();
    $productMedia = Media::factory()->uploaded()->create();
    $variantMedia = Media::factory()->uploaded()->create();
    $product = Product::factory()->create();
    (new SyncMediaAction())->handle($product, [$productMedia->id]);
    $variant = ProductVariant::factory()->for($product)->create(['is_default' => true]);
    $variant->update(['media_id' => $variantMedia->id]);
    CartItem::factory()->for($cart)->create([
        'product_id' => $product->id,
        'product_variant_id' => $variant->id,
    ]);

    $result = app(StorefrontCartQuery::class)->execute($cart);

    $item = $result->items->first();

    expect($item->productVariant?->media?->id)->toBe($variantMedia->id)
        ->and($item->productVariant?->media?->thumbnail_url)->toBe($variantMedia->thumbnail_url)
        ->and($item->product?->featured_media?->id)->toBe($productMedia->id);
});

test('cart items only include required fields for storefront', function () {
    $cart = Cart::factory()->create();
    $product = Product::factory()->create();
    ProductVariant::factory()->for($product)->create(['is_default' => true]);
    CartItem::factory()->for($cart)->create([
        'product_id' => $product->id,
        'unit_price' => '25.9900',
        'total_price' => '25.9900',
    ]);

    $result = app(StorefrontCartQuery::class)->execute($cart->load('items'));

    $cartItemArray = $result->items->first()->toArray();
    $productArray = $cartItemArray['product'];

    expect($cartItemArray)->toHaveKeys(['id', 'product_id', 'quantity', 'unit_price', 'total_price', 'variant_title', 'product'])
        ->and($cartItemArray['unit_price'])->toBe('25.9900');

    expect($productArray)->toHaveKeys(['id', 'url_handle', 'title', 'featured_media'])
        ->and($productArray)->not->toHaveKey('media_gallery')
        ->and($productArray)->not->toHaveKey('description')
        ->and($productArray)->not->toHaveKey('price');
});

test('cart accessors remain readable after the query restricts relation columns', function () {
    $cart = Cart::factory()->create();
    $product = Product::factory()->create(['weight' => '2.00', 'weight_unit' => 'kg']);
    $variant = ProductVariant::factory()->for($product)->create(['weight' => '1.50', 'weight_unit' => 'kg']);

    CartItem::factory()->for($cart)->create([
        'product_id' => $product->id,
        'product_variant_id' => $variant->id,
        'quantity' => 2,
    ]);

    $result = app(StorefrontCartQuery::class)->execute($cart);

    expect($result->weight_in_grams)->toBe('3000.00')
        ->and($result->requiresShipping())->toBeTrue();
});
