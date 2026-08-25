<?php

declare(strict_types=1);

use App\Actions\ResolveWishlistAction;
use App\Models\User;
use App\Models\Wishlist;

covers(ResolveWishlistAction::class);

uses()->group('queries', 'wishlist');

test('creates a new wishlist for guest', function () {
    $query = app(ResolveWishlistAction::class);

    $wishlist = $query->handle();

    expect($wishlist)->toBeInstanceOf(Wishlist::class)
        ->and($wishlist->id)->not()->toBeNull()
        ->and($wishlist->customer_id)->toBeNull()
        ->and($wishlist->items)->toBeEmpty();
});

test('reuses existing customer wishlist when present', function () {
    $customer = User::factory()->customer()->create();
    $existing = Wishlist::factory()->create([
        'customer_id' => $customer->id,
    ]);

    $query = app(ResolveWishlistAction::class);

    $wishlist = $query->handle(customer: $customer);

    expect($wishlist->id)->toBe($existing->id)
        ->and($wishlist->customer_id)->toBe($customer->id);
});

test('assigns guest wishlist to customer', function () {
    $customer = User::factory()->customer()->create();
    $guestWishlist = Wishlist::factory()->create([
        'customer_id' => null,
    ]);

    $query = app(ResolveWishlistAction::class);

    $wishlist = $query->handle($guestWishlist->id, $customer);

    expect($wishlist->id)->toBe($guestWishlist->id)
        ->and($wishlist->customer_id)->toBe($customer->id);
});

test('does not return cached wishlist when different id requested', function () {
    $wishlist1 = Wishlist::factory()->create();
    $wishlist2 = Wishlist::factory()->create();
    $query = app(ResolveWishlistAction::class);

    $result1 = $query->handle($wishlist1->id);
    $result2 = $query->handle($wishlist2->id);

    expect($result1->id)->toBe($wishlist1->id)
        ->and($result2->id)->toBe($wishlist2->id);
});
