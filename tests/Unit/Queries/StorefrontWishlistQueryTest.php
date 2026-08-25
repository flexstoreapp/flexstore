<?php

declare(strict_types=1);

use App\Models\Product;
use App\Models\Wishlist;
use App\Models\WishlistItem;
use App\Queries\StorefrontWishlistQuery;

covers(StorefrontWishlistQuery::class);

uses()->group('queries', 'storefront');

test('returns wishlist product ids', function () {
    $wishlist = Wishlist::factory()->create();
    $product = Product::factory()->create();
    WishlistItem::factory()->for($wishlist)->create(['product_id' => $product->id]);

    $result = app(StorefrontWishlistQuery::class)->execute($wishlist->load('items'));
    $productIds = $result['product_ids'];

    expect($result['id'])->toBe($wishlist->id)
        ->and($productIds)->toBeArray()->toContain($product->id);
});

test('returns empty array when wishlist has no items', function () {
    $wishlist = Wishlist::factory()->create();

    $result = app(StorefrontWishlistQuery::class)->execute($wishlist->load('items'));
    $productIds = $result['product_ids'];

    expect($productIds)->toBeArray()->toBeEmpty();
});

test('returns multiple product ids', function () {
    $wishlist = Wishlist::factory()->create();
    $products = Product::factory()->count(3)->create();

    foreach ($products as $product) {
        WishlistItem::factory()->for($wishlist)->create(['product_id' => $product->id]);
    }

    $result = app(StorefrontWishlistQuery::class)->execute($wishlist->load('items'));
    $productIds = $result['product_ids'];

    expect($productIds)->toHaveCount(3)
        ->and($productIds)->toContain($products[0]->id, $products[1]->id, $products[2]->id);
});
