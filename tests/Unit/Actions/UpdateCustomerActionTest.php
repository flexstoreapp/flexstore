<?php

declare(strict_types=1);

use App\Actions\UpdateCustomerAction;
use App\DTOs\UpdateCustomerInput;
use App\Enums\Role as RoleEnum;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\assertDatabaseHas;

covers(UpdateCustomerAction::class, UpdateCustomerInput::class);

uses()->group('actions', 'customer');

test('updates customer name and email', function () {
    $customerRole = Role::firstOrCreate(['name' => RoleEnum::Customer]);
    $customer = User::factory()->create([
        'name' => 'Original Name',
        'email' => 'original@example.com',
    ]);
    $customer->assignRole($customerRole);

    $data = [
        'name' => 'Updated Name',
        'email' => 'updated@example.com',
    ];

    $action = new UpdateCustomerAction();
    $updatedCustomer = $action->handle($customer, UpdateCustomerInput::fromArray($data));

    expect($updatedCustomer)->toBeInstanceOf(User::class)
        ->and($updatedCustomer->id)->toBe($customer->id)
        ->and($updatedCustomer->name)->toBe('Updated Name')
        ->and($updatedCustomer->email)->toBe('updated@example.com');

    assertDatabaseHas('users', [
        'id' => $customer->id,
        'name' => 'Updated Name',
        'email' => 'updated@example.com',
    ]);
});

test('updates customer password when provided', function () {
    $customerRole = Role::firstOrCreate(['name' => RoleEnum::Customer]);
    $customer = User::factory()->create([
        'password' => Hash::make('old-password'),
    ]);
    $customer->assignRole($customerRole);

    $data = [
        'name' => $customer->name,
        'email' => $customer->email,
        'password' => 'new-password',
    ];

    $action = new UpdateCustomerAction();
    $updatedCustomer = $action->handle($customer, UpdateCustomerInput::fromArray($data));

    expect(Hash::check('new-password', $updatedCustomer->password))->toBeTrue()
        ->and(Hash::check('old-password', $updatedCustomer->password))->toBeFalse();
});

test('does not update password when not provided', function () {
    $customerRole = Role::firstOrCreate(['name' => RoleEnum::Customer]);
    $originalPassword = Hash::make('original-password');
    $customer = User::factory()->create([
        'password' => $originalPassword,
    ]);
    $customer->assignRole($customerRole);

    $data = [
        'name' => 'Updated Name',
        'email' => 'updated@example.com',
    ];

    $action = new UpdateCustomerAction();
    $updatedCustomer = $action->handle($customer, UpdateCustomerInput::fromArray($data));

    expect($updatedCustomer->password)->toBe($originalPassword);
});

test('does not update password when empty string provided', function () {
    $customerRole = Role::firstOrCreate(['name' => RoleEnum::Customer]);
    $originalPassword = Hash::make('original-password');
    $customer = User::factory()->create([
        'password' => $originalPassword,
    ]);
    $customer->assignRole($customerRole);

    $data = [
        'name' => 'Updated Name',
        'email' => 'updated@example.com',
        'password' => '',
    ];

    $action = new UpdateCustomerAction();
    $updatedCustomer = $action->handle($customer, UpdateCustomerInput::fromArray($data));

    expect($updatedCustomer->password)->toBe($originalPassword);
});

test('updates only name when only name is provided', function () {
    $customerRole = Role::firstOrCreate(['name' => RoleEnum::Customer]);
    $customer = User::factory()->create([
        'name' => 'Original Name',
        'email' => 'original@example.com',
    ]);
    $customer->assignRole($customerRole);

    $data = [
        'name' => 'Updated Name',
        'email' => $customer->email,
    ];

    $action = new UpdateCustomerAction();
    $updatedCustomer = $action->handle($customer, UpdateCustomerInput::fromArray($data));

    expect($updatedCustomer->name)->toBe('Updated Name')
        ->and($updatedCustomer->email)->toBe('original@example.com');
});

test('updates only email when only email is provided', function () {
    $customerRole = Role::firstOrCreate(['name' => RoleEnum::Customer]);
    $customer = User::factory()->create([
        'name' => 'Original Name',
        'email' => 'original@example.com',
    ]);
    $customer->assignRole($customerRole);

    $data = [
        'name' => $customer->name,
        'email' => 'updated@example.com',
    ];

    $action = new UpdateCustomerAction();
    $updatedCustomer = $action->handle($customer, UpdateCustomerInput::fromArray($data));

    expect($updatedCustomer->name)->toBe('Original Name')
        ->and($updatedCustomer->email)->toBe('updated@example.com');
});

test('preserves email when only name is in the input payload', function () {
    $customerRole = Role::firstOrCreate(['name' => RoleEnum::Customer]);
    $customer = User::factory()->create([
        'name' => 'Original Name',
        'email' => 'keep@example.com',
    ]);
    $customer->assignRole($customerRole);

    $action = new UpdateCustomerAction();
    $action->handle($customer, UpdateCustomerInput::fromArray(['name' => 'New Name']));

    expect($customer->fresh()->email)->toBe('keep@example.com')
        ->and($customer->fresh()->name)->toBe('New Name');
});

test('preserves name when only email is in the input payload', function () {
    $customerRole = Role::firstOrCreate(['name' => RoleEnum::Customer]);
    $customer = User::factory()->create([
        'name' => 'Keep Name',
        'email' => 'old@example.com',
    ]);
    $customer->assignRole($customerRole);

    $action = new UpdateCustomerAction();
    $action->handle($customer, UpdateCustomerInput::fromArray(['email' => 'new@example.com']));

    expect($customer->fresh()->name)->toBe('Keep Name')
        ->and($customer->fresh()->email)->toBe('new@example.com');
});

test('hashes new password correctly', function () {
    $customerRole = Role::firstOrCreate(['name' => RoleEnum::Customer]);
    $customer = User::factory()->create([
        'password' => Hash::make('old-password'),
    ]);
    $customer->assignRole($customerRole);

    $data = [
        'name' => $customer->name,
        'email' => $customer->email,
        'password' => 'new-secure-password',
    ];

    $action = new UpdateCustomerAction();
    $updatedCustomer = $action->handle($customer, UpdateCustomerInput::fromArray($data));

    expect(Hash::check('new-secure-password', $updatedCustomer->password))->toBeTrue()
        ->and($updatedCustomer->password)->not->toBe('new-secure-password');
});
