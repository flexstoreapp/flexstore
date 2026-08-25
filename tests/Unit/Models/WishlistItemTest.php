<?php

declare(strict_types=1);

use App\Models\Product;
use App\Models\Wishlist;
use App\Models\WishlistItem;
use Illuminate\Database\Eloquent\Factories\Factory;

use function Pest\Laravel\travel;

covers(WishlistItem::class);

uses()->group('models', 'wishlist');

test('has factory', function () {
    expect(WishlistItem::factory())->toBeInstanceOf(Factory::class);
});

test('touches wishlist relationship', function () {
    expect((new WishlistItem())->getTouchedRelations())->toBe(['wishlist']);
});

test('belongs to wishlist', function () {
    $wishlist = Wishlist::factory()->create();
    $item = WishlistItem::factory()->create(['wishlist_id' => $wishlist->id]);

    expect($item->wishlist)->toBeInstanceOf(Wishlist::class)
        ->and($item->wishlist->id)->toBe($wishlist->id);
});

test('belongs to product', function () {
    $product = Product::factory()->create();
    $item = WishlistItem::factory()->create(['product_id' => $product->id]);

    expect($item->product)->toBeInstanceOf(Product::class)
        ->and($item->product->id)->toBe($product->id);
});

test('touching wishlist updates its timestamp when item is saved', function () {
    $wishlist = Wishlist::factory()->create();
    $originalUpdatedAt = $wishlist->updated_at;

    travel(2)->seconds();

    WishlistItem::factory()->create(['wishlist_id' => $wishlist->id]);

    expect($wishlist->fresh()->updated_at->gt($originalUpdatedAt))->toBeTrue();
});
