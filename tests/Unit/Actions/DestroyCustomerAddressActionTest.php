<?php

declare(strict_types=1);

use App\Actions\DestroyCustomerAddressAction;
use App\Enums\Role as RoleEnum;
use App\Models\CustomerAddress;
use App\Models\User;

use function Pest\Laravel\assertDatabaseMissing;

covers(DestroyCustomerAddressAction::class);

uses()->group('actions', 'customer');

test('deletes a customer address', function () {
    $customer = User::factory()->create();
    $customer->assignRole(RoleEnum::Customer);

    $address = CustomerAddress::factory()->forUser($customer)->create();

    $action = new DestroyCustomerAddressAction();
    $result = $action->handle($address);

    expect($result)->toBeTrue();

    assertDatabaseMissing('customer_addresses', [
        'id' => $address->id,
    ]);
});
