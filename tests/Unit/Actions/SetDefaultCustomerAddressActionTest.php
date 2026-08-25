<?php

declare(strict_types=1);

use App\Actions\SetDefaultCustomerAddressAction;
use App\Models\CustomerAddress;
use App\Models\User;

covers(SetDefaultCustomerAddressAction::class);

uses()->group('actions', 'customer');

test('sets address as default', function () {
    $user = User::factory()->create();
    $address = CustomerAddress::factory()->forUser($user)->create(['is_default' => false]);

    $action = new SetDefaultCustomerAddressAction();
    $action->handle($user, $address);

    $address->refresh();
    expect($address->is_default)->toBeTrue();
});

test('unsets other default addresses when setting new default', function () {
    $user = User::factory()->create();
    $existingDefault = CustomerAddress::factory()->forUser($user)->default()->create();
    $newDefault = CustomerAddress::factory()->forUser($user)->create(['is_default' => false]);

    $action = new SetDefaultCustomerAddressAction();
    $action->handle($user, $newDefault);

    $existingDefault->refresh();
    $newDefault->refresh();

    expect($existingDefault->is_default)->toBeFalse()
        ->and($newDefault->is_default)->toBeTrue();
});
