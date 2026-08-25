<?php

declare(strict_types=1);

use App\Http\Controllers\Storefront\WishlistController;
use App\Models\Product;
use App\Models\User;
use App\Models\Wishlist;
use App\Models\WishlistItem;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\withUnencryptedCookie;

covers(WishlistController::class);

uses()->group('wishlist');

test('the wishlist page redirects to the url carrying the wishlist id', function () {
    $wishlist = Wishlist::factory()->create();

    $response = withUnencryptedCookie('wishlist_id', $wishlist->id)
        ->get(route('wishlist.show'));

    $response->assertRedirect(route('wishlist.show', $wishlist))
        ->assertCookie('wishlist_id', $wishlist->id, false);
});

test('a guest without a wishlist gets one created and is redirected to its url', function () {
    $response = get(route('wishlist.show'));

    $wishlist = Wishlist::query()->sole();

    $response->assertRedirect(route('wishlist.show', $wishlist));
});

test('guest can view wishlist page with products', function () {
    $wishlist = Wishlist::factory()->create();
    $product = Product::factory()->active()->create();

    WishlistItem::factory()->create([
        'wishlist_id' => $wishlist->id,
        'product_id' => $product->id,
    ]);

    $response = withUnencryptedCookie('wishlist_id', $wishlist->id)
        ->get(route('wishlist.show', $wishlist));

    $response->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('storefront/wishlist/show')
            ->has('products', 1)
            ->where('products.0.id', $product->id)
        );
});

test('inactive wishlisted products are excluded from the page', function () {
    $wishlist = Wishlist::factory()->create();
    $active = Product::factory()->active()->create();
    $inactive = Product::factory()->create(['is_active' => false]);

    WishlistItem::factory()->create(['wishlist_id' => $wishlist->id, 'product_id' => $active->id]);
    WishlistItem::factory()->create(['wishlist_id' => $wishlist->id, 'product_id' => $inactive->id]);

    $response = withUnencryptedCookie('wishlist_id', $wishlist->id)
        ->get(route('wishlist.show', $wishlist));

    $response->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('storefront/wishlist/show')
            ->has('products', 1)
            ->where('products.0.id', $active->id)
        );
});

test('authenticated user can view their wishlist', function () {
    $user = User::factory()->create();
    $wishlist = Wishlist::factory()->create(['customer_id' => $user->id]);
    $product = Product::factory()->active()->create();

    WishlistItem::factory()->create([
        'wishlist_id' => $wishlist->id,
        'product_id' => $product->id,
    ]);

    $response = actingAs($user)->get(route('wishlist.show', $wishlist));

    $response->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('storefront/wishlist/show')
        );
});

test('a shared wishlist is shown read only without touching the visitor wishlist', function () {
    $owner = Wishlist::factory()->create();
    $visitor = Wishlist::factory()->create();
    $product = Product::factory()->active()->create();

    WishlistItem::factory()->create(['wishlist_id' => $owner->id, 'product_id' => $product->id]);

    $response = withUnencryptedCookie('wishlist_id', $visitor->id)
        ->get(route('wishlist.show', $owner));

    $response->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('storefront/wishlist/show')
            ->where('is_shared', true)
            ->where('share_url', route('wishlist.show', $owner))
            ->has('products', 1)
            ->where('products.0.id', $product->id)
        );

    expect($visitor->refresh()->items)->toBeEmpty();
});

test('visiting your own wishlist by id is not treated as shared', function () {
    $wishlist = Wishlist::factory()->create();

    $response = withUnencryptedCookie('wishlist_id', $wishlist->id)
        ->get(route('wishlist.show', $wishlist));

    $response->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('is_shared', false));
});

test('an unknown shared wishlist id returns not found', function () {
    get(route('wishlist.show', Str::uuid7()->toString()))->assertNotFound();
});
