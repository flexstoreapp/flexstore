<?php

declare(strict_types=1);

use App\Actions\AddWishlistItemAction;
use App\Http\Controllers\Storefront\WishlistItemController;
use App\Models\Product;
use App\Models\User;
use App\Models\Wishlist;
use App\Models\WishlistItem;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\put;
use function Pest\Laravel\withUnencryptedCookie;

covers(WishlistItemController::class, AddWishlistItemAction::class);

uses()->group('wishlist');

test('adds product to wishlist', function () {
    $wishlist = Wishlist::factory()->create();
    $product = Product::factory()->active()->create();

    withUnencryptedCookie('wishlist_id', $wishlist->id)
        ->put(route('wishlist.items.update', $product))
        ->assertRedirectBack()
        ->assertSessionHasNoErrors();

    assertDatabaseHas('wishlist_items', ['wishlist_id' => $wishlist->id, 'product_id' => $product->id]);
});

test('adding an already present product is idempotent', function () {
    $wishlist = Wishlist::factory()->create();
    $product = Product::factory()->active()->create();
    WishlistItem::factory()->create(['wishlist_id' => $wishlist->id, 'product_id' => $product->id]);

    withUnencryptedCookie('wishlist_id', $wishlist->id)
        ->put(route('wishlist.items.update', $product))
        ->assertRedirectBack()
        ->assertSessionHasNoErrors();

    expect(WishlistItem::query()->where('wishlist_id', $wishlist->id)->where('product_id', $product->id)->count())->toBe(1);
});

test('creates new wishlist when adding item without cookie', function () {
    $product = Product::factory()->active()->create();

    put(route('wishlist.items.update', $product))->assertRedirectBack();

    expect(Wishlist::query()->count())->toBe(1);
    expect(WishlistItem::query()->count())->toBe(1);
});

test('returns 404 when adding an inactive product', function () {
    $product = Product::factory()->inactive()->create();

    put(route('wishlist.items.update', $product))->assertNotFound();
});

test('returns 404 when adding a non-existent product', function () {
    put(route('wishlist.items.update', 99999))->assertNotFound();
});

test('authenticated user can add to wishlist', function () {
    $user = User::factory()->create();
    $product = Product::factory()->active()->create();

    actingAs($user)->put(route('wishlist.items.update', $product))
        ->assertRedirectBack()
        ->assertSessionHasNoErrors();

    $wishlist = Wishlist::query()->where('customer_id', $user->id)->first();
    expect($wishlist)->not->toBeNull();
    assertDatabaseHas('wishlist_items', ['wishlist_id' => $wishlist->id, 'product_id' => $product->id]);
});

test('associates guest wishlist with user on add', function () {
    $user = User::factory()->create();
    $wishlist = Wishlist::factory()->create(['customer_id' => null]);
    $product = Product::factory()->active()->create();

    actingAs($user)
        ->withUnencryptedCookie('wishlist_id', $wishlist->id)
        ->put(route('wishlist.items.update', $product))
        ->assertRedirectBack();

    expect($wishlist->refresh()->customer_id)->toBe($user->id);
});

test('sets wishlist_id cookie on response', function () {
    $product = Product::factory()->active()->create();

    put(route('wishlist.items.update', $product))->assertCookie('wishlist_id');
});

test('can add multiple different products to wishlist', function () {
    $wishlist = Wishlist::factory()->create();
    $product1 = Product::factory()->active()->create();
    $product2 = Product::factory()->active()->create();

    withUnencryptedCookie('wishlist_id', $wishlist->id)->put(route('wishlist.items.update', $product1));
    withUnencryptedCookie('wishlist_id', $wishlist->id)->put(route('wishlist.items.update', $product2));

    expect(WishlistItem::query()->where('wishlist_id', $wishlist->id)->count())->toBe(2);
});
