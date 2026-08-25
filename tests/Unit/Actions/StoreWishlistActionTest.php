<?php

declare(strict_types=1);

use App\Actions\StoreWishlistAction;
use App\Models\User;
use App\Models\Wishlist;
use Illuminate\Support\Str;

covers(StoreWishlistAction::class);

uses()->group('actions', 'wishlist');

test('creates a new wishlist', function () {
    $action = new StoreWishlistAction();

    $wishlist = $action->handle();

    expect($wishlist)->toBeInstanceOf(Wishlist::class)
        ->and($wishlist->id)->not()->toBeNull()
        ->and($wishlist->customer_id)->toBeNull()
        ->and($wishlist->items)->toBeEmpty();
});

test('creates a wishlist with specified id', function () {
    $wishlistId = Str::uuid7()->toString();
    $action = new StoreWishlistAction();

    $wishlist = $action->handle($wishlistId);

    expect($wishlist->id)->toBe($wishlistId);
});

test('creates a wishlist for a customer', function () {
    $customer = User::factory()->customer()->create();
    $action = new StoreWishlistAction();

    $wishlist = $action->handle(customer: $customer);

    expect($wishlist->customer_id)->toBe($customer->id);
});
