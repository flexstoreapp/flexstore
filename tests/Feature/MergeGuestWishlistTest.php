<?php

declare(strict_types=1);

use App\Actions\MergeGuestWishlistAction;
use App\Listeners\MergeGuestWishlistOnLogin;
use App\Models\Product;
use App\Models\User;
use App\Models\Wishlist;
use App\Models\WishlistItem;
use Illuminate\Auth\Events\Login;

covers(MergeGuestWishlistAction::class, MergeGuestWishlistOnLogin::class);

uses()->group('wishlist');

test('merges guest wishlist items to user wishlist on login', function () {
    $user = User::factory()->create();
    $product1 = Product::factory()->active()->create();
    $product2 = Product::factory()->active()->create();

    $userWishlist = Wishlist::factory()->create(['customer_id' => $user->id]);
    WishlistItem::factory()->create([
        'wishlist_id' => $userWishlist->id,
        'product_id' => $product1->id,
    ]);

    $guestWishlist = Wishlist::factory()->create(['customer_id' => null]);
    WishlistItem::factory()->create([
        'wishlist_id' => $guestWishlist->id,
        'product_id' => $product2->id,
    ]);

    $action = new MergeGuestWishlistAction();
    $action->handle($guestWishlist->id, $user);

    expect(Wishlist::query()->whereKey($guestWishlist->id)->exists())->toBeFalse();

    $userWishlist->refresh();
    $userWishlist->load('items');

    expect($userWishlist->items)->toHaveCount(2);

    $productIds = $userWishlist->items->pluck('product_id')->sort()->values()->all();
    expect($productIds)->toEqual([$product1->id, $product2->id]);
});

test('does not duplicate items when merging', function () {
    $user = User::factory()->create();
    $product = Product::factory()->active()->create();

    $userWishlist = Wishlist::factory()->create(['customer_id' => $user->id]);
    WishlistItem::factory()->create([
        'wishlist_id' => $userWishlist->id,
        'product_id' => $product->id,
    ]);

    $guestWishlist = Wishlist::factory()->create(['customer_id' => null]);
    WishlistItem::factory()->create([
        'wishlist_id' => $guestWishlist->id,
        'product_id' => $product->id,
    ]);

    $action = new MergeGuestWishlistAction();
    $action->handle($guestWishlist->id, $user);

    $userWishlist->refresh();
    $userWishlist->load('items');

    expect($userWishlist->items)->toHaveCount(1);
});

test('assigns guest wishlist to user when user has no wishlist', function () {
    $user = User::factory()->create();
    $product = Product::factory()->active()->create();

    $guestWishlist = Wishlist::factory()->create(['customer_id' => null]);
    WishlistItem::factory()->create([
        'wishlist_id' => $guestWishlist->id,
        'product_id' => $product->id,
    ]);

    $action = new MergeGuestWishlistAction();
    $action->handle($guestWishlist->id, $user);

    $guestWishlist->refresh();

    expect($guestWishlist->customer_id)->toBe($user->id);
    expect(Wishlist::query()->where('customer_id', $user->id)->count())->toBe(1);
});

test('does nothing when guest wishlist id is null', function () {
    $user = User::factory()->create();

    $action = new MergeGuestWishlistAction();
    $action->handle(null, $user);

    expect(true)->toBeTrue();
});

test('does nothing when guest wishlist id is empty string', function () {
    $user = User::factory()->create();

    $action = new MergeGuestWishlistAction();
    $action->handle('', $user);

    expect(true)->toBeTrue();
});

test('does nothing when guest wishlist does not exist', function () {
    $user = User::factory()->create();

    $action = new MergeGuestWishlistAction();
    $action->handle('00000000-0000-0000-0000-000000000000', $user);

    expect(true)->toBeTrue();
});

test('does nothing when wishlist already belongs to another user', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $product = Product::factory()->active()->create();

    $otherUserWishlist = Wishlist::factory()->create(['customer_id' => $otherUser->id]);
    WishlistItem::factory()->create([
        'wishlist_id' => $otherUserWishlist->id,
        'product_id' => $product->id,
    ]);

    $action = new MergeGuestWishlistAction();
    $action->handle($otherUserWishlist->id, $user);

    $otherUserWishlist->refresh();
    expect($otherUserWishlist->customer_id)->toBe($otherUser->id);
});

test('listener merges guest wishlist when cookie is present', function () {
    $user = User::factory()->create();
    $product = Product::factory()->active()->create();

    $guestWishlist = Wishlist::factory()->create(['customer_id' => null]);
    WishlistItem::factory()->create([
        'wishlist_id' => $guestWishlist->id,
        'product_id' => $product->id,
    ]);

    request()->cookies->set('wishlist_id', $guestWishlist->id);

    (new MergeGuestWishlistOnLogin(new MergeGuestWishlistAction()))
        ->handle(new Login('web', $user, false));

    $guestWishlist->refresh();
    expect($guestWishlist->customer_id)->toBe($user->id);
});

test('listener does nothing when cookie is missing', function () {
    $user = User::factory()->create();

    (new MergeGuestWishlistOnLogin(new MergeGuestWishlistAction()))
        ->handle(new Login('web', $user, false));

    expect(Wishlist::query()->where('customer_id', $user->id)->count())->toBe(0);
});

test('listener does nothing when authenticatable is not a user', function () {
    $otherAuthenticatable = new class() implements Illuminate\Contracts\Auth\Authenticatable
    {
        public function getAuthIdentifierName(): string
        {
            return 'id';
        }

        public function getAuthIdentifier(): int
        {
            return 1;
        }

        public function getAuthPasswordName(): string
        {
            return 'password';
        }

        public function getAuthPassword(): string
        {
            return '';
        }

        public function getRememberToken(): string
        {
            return '';
        }

        public function setRememberToken($value): void
        {
        }

        public function getRememberTokenName(): string
        {
            return 'remember_token';
        }
    };

    request()->cookies->set('wishlist_id', 'some-id');

    (new MergeGuestWishlistOnLogin(new MergeGuestWishlistAction()))
        ->handle(new Login('web', $otherAuthenticatable, false));

    expect(true)->toBeTrue();
});
