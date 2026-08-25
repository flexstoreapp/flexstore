<?php

declare(strict_types=1);

use App\Models\CustomerAddress;
use App\Models\User;
use App\Queries\CustomerAddressListQuery;
use Illuminate\Database\Eloquent\Collection;

covers(CustomerAddressListQuery::class);

uses()->group('queries', 'customer-address');

test('returns addresses for the given user', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    CustomerAddress::factory()->count(2)->forUser($user)->create();
    CustomerAddress::factory()->forUser($otherUser)->create();

    $query = new CustomerAddressListQuery();
    $result = $query->execute($user);

    expect($result)->toBeInstanceOf(Collection::class)
        ->and($result)->toHaveCount(2);
});

test('orders default address first', function () {
    $user = User::factory()->create();

    $regular = CustomerAddress::factory()->forUser($user)->create(['is_default' => false]);
    $default = CustomerAddress::factory()->forUser($user)->default()->create();

    $query = new CustomerAddressListQuery();
    $result = $query->execute($user);

    expect($result->first()->id)->toBe($default->id);
});

test('returns empty collection when user has no addresses', function () {
    $user = User::factory()->create();

    $query = new CustomerAddressListQuery();
    $result = $query->execute($user);

    expect($result)->toBeEmpty();
});
