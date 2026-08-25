<?php

declare(strict_types=1);

use App\Actions\RemoveWishlistItemAction;
use App\Http\Controllers\Storefront\WishlistItemController;
use App\Models\Product;
use App\Models\Wishlist;
use App\Models\WishlistItem;

use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Laravel\withUnencryptedCookie;

covers(WishlistItemController::class, RemoveWishlistItemAction::class);

uses()->group('wishlist');

test('removes product from wishlist', function () {
    $wishlist = Wishlist::factory()->create();
    $product = Product::factory()->active()->create();
    WishlistItem::factory()->create(['wishlist_id' => $wishlist->id, 'product_id' => $product->id]);

    withUnencryptedCookie('wishlist_id', $wishlist->id)
        ->delete(route('wishlist.items.destroy', $product))
        ->assertRedirectBack()
        ->assertSessionHasNoErrors();

    assertDatabaseMissing('wishlist_items', ['wishlist_id' => $wishlist->id, 'product_id' => $product->id]);
});

test('removing an absent product is idempotent', function () {
    $wishlist = Wishlist::factory()->create();
    $product = Product::factory()->active()->create();

    withUnencryptedCookie('wishlist_id', $wishlist->id)
        ->delete(route('wishlist.items.destroy', $product))
        ->assertRedirectBack()
        ->assertSessionHasNoErrors();

    expect(WishlistItem::query()->count())->toBe(0);
});

test('leaves other wishlist items intact', function () {
    $wishlist = Wishlist::factory()->create();
    $kept = Product::factory()->active()->create();
    $removed = Product::factory()->active()->create();
    WishlistItem::factory()->create(['wishlist_id' => $wishlist->id, 'product_id' => $kept->id]);
    WishlistItem::factory()->create(['wishlist_id' => $wishlist->id, 'product_id' => $removed->id]);

    withUnencryptedCookie('wishlist_id', $wishlist->id)
        ->delete(route('wishlist.items.destroy', $removed))
        ->assertRedirectBack();

    assertDatabaseMissing('wishlist_items', ['wishlist_id' => $wishlist->id, 'product_id' => $removed->id]);
    expect(WishlistItem::query()->where('wishlist_id', $wishlist->id)->pluck('product_id')->all())->toBe([$kept->id]);
});
