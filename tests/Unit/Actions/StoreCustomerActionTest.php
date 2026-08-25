<?php

declare(strict_types=1);

use App\Actions\StoreCustomerAction;
use App\DTOs\StoreCustomerInput;
use App\Enums\Role as RoleEnum;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\assertDatabaseHas;

covers(StoreCustomerAction::class, StoreCustomerInput::class);

uses()->group('actions', 'customer');

test('creates a customer with correct data', function () {
    $customerRole = Role::firstOrCreate(['name' => RoleEnum::Customer]);

    $data = [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'password' => 'password123',
    ];

    $action = app(StoreCustomerAction::class);
    $user = $action->handle(StoreCustomerInput::fromArray($data));

    expect($user)->toBeInstanceOf(User::class)
        ->and($user->name)->toBe('John Doe')
        ->and($user->email)->toBe('john@example.com')
        ->and($user->hasRole(RoleEnum::Customer))->toBeTrue()
        ->and(Hash::check('password123', $user->password))->toBeTrue();

    assertDatabaseHas('users', [
        'id' => $user->id,
        'name' => 'John Doe',
        'email' => 'john@example.com',
    ]);
});

test('assigns customer role to created user', function () {
    $customerRole = Role::firstOrCreate(['name' => RoleEnum::Customer]);

    $data = [
        'name' => 'Test Customer',
        'email' => 'test@example.com',
        'password' => 'password123',
    ];

    $action = app(StoreCustomerAction::class);
    $user = $action->handle(StoreCustomerInput::fromArray($data));

    expect($user->hasRole(RoleEnum::Customer))->toBeTrue()
        ->and($user->roles->count())->toBe(1)
        ->and($user->roles->first()->name)->toBe(RoleEnum::Customer->value);
});

test('hashes password correctly', function () {
    $customerRole = Role::firstOrCreate(['name' => RoleEnum::Customer]);

    $data = [
        'name' => 'Password Test',
        'email' => 'password@example.com',
        'password' => 'secure-password-123',
    ];

    $action = app(StoreCustomerAction::class);
    $user = $action->handle(StoreCustomerInput::fromArray($data));

    expect(Hash::check('secure-password-123', $user->password))->toBeTrue()
        ->and($user->password)->not->toBe('secure-password-123');
});

test('creates multiple customers independently', function () {
    $customerRole = Role::firstOrCreate(['name' => RoleEnum::Customer]);

    $data1 = [
        'name' => 'Customer One',
        'email' => 'customer1@example.com',
        'password' => 'password123',
    ];

    $data2 = [
        'name' => 'Customer Two',
        'email' => 'customer2@example.com',
        'password' => 'password456',
    ];

    $action = app(StoreCustomerAction::class);
    $user1 = $action->handle(StoreCustomerInput::fromArray($data1));
    $user2 = $action->handle(StoreCustomerInput::fromArray($data2));

    expect($user1->id)->not->toBe($user2->id)
        ->and($user1->email)->toBe('customer1@example.com')
        ->and($user2->email)->toBe('customer2@example.com')
        ->and($user1->hasRole(RoleEnum::Customer))->toBeTrue()
        ->and($user2->hasRole(RoleEnum::Customer))->toBeTrue();
});

test('sends admin notification when new customer setting is enabled', function () {
    Role::firstOrCreate(['name' => RoleEnum::Customer]);
    App\Models\Setting::setValue('notification_admin_new_customer', true);
    App\Models\Setting::setValue('store_email', 'store@example.com');

    Illuminate\Support\Facades\Notification::fake();

    app(StoreCustomerAction::class)->handle(StoreCustomerInput::fromArray([
        'name' => 'Notifier Test',
        'email' => 'notify@example.com',
        'password' => 'password',
    ]));

    Illuminate\Support\Facades\Notification::assertSentOnDemand(App\Notifications\AdminNewCustomerNotification::class);
});
