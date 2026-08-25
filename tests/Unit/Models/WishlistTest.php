<?php

declare(strict_types=1);

use App\Models\User;
use App\Models\Wishlist;
use App\Models\WishlistItem;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

covers(Wishlist::class);

uses()->group('models', 'wishlist');

test('has factory', function () {
    expect(Wishlist::factory())->toBeInstanceOf(Factory::class);
});

test('uses HasUuids trait', function () {
    expect(Wishlist::class)->toUseTrait(HasUuids::class);
});

test('has many items relationship', function () {
    $wishlist = Wishlist::factory()->create();
    $item1 = WishlistItem::factory()->for($wishlist)->create();
    $item2 = WishlistItem::factory()->for($wishlist)->create();

    expect($wishlist->items)->toHaveCount(2)
        ->and($wishlist->items->pluck('id')->all())->toEqualCanonicalizing([$item1->id, $item2->id]);
});

test('belongs to customer', function () {
    $user = User::factory()->create();
    $wishlist = Wishlist::factory()->create(['customer_id' => $user->id]);

    expect($wishlist->customer)->toBeInstanceOf(User::class)
        ->and($wishlist->customer->id)->toBe($user->id);
});

test('customer is null for guest wishlists', function () {
    $wishlist = Wishlist::factory()->create(['customer_id' => null]);

    expect($wishlist->customer)->toBeNull();
});

test('uses non-incrementing string primary key', function () {
    $wishlist = Wishlist::factory()->create();

    expect(Str::isUuid($wishlist->id, 7))->toBeTrue()
        ->and((new Wishlist())->getIncrementing())->toBeFalse()
        ->and((new Wishlist())->getKeyType())->toBe('string');
});
