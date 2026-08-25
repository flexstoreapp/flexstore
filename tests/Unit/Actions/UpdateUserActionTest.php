<?php

declare(strict_types=1);

use App\Actions\UpdateUserAction;
use App\DTOs\UpdateUserInput;
use App\Enums\AdminThemeColor;
use App\Enums\Appearance;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

covers(UpdateUserAction::class, UpdateUserInput::class);

uses()->group('actions', 'user');

test('updates user basic information', function () {
    $user = User::factory()->create([
        'name' => 'Original Name',
        'email' => 'original@example.com',
    ]);

    $updateData = [
        'name' => 'Updated Name',
        'email' => 'updated@example.com',
    ];

    $action = new UpdateUserAction();
    $result = $action->handle($user, UpdateUserInput::fromArray($updateData));

    expect($result->name)->toBe('Updated Name')
        ->and($result->email)->toBe('updated@example.com')
        ->and($result->id)->toBe($user->id);
});

test('updates user password when provided', function () {
    $user = User::factory()->create([
        'password' => Hash::make('old-password'),
    ]);

    $updateData = [
        'password' => 'new-password',
    ];

    $action = new UpdateUserAction();
    $result = $action->handle($user, UpdateUserInput::fromArray($updateData));

    expect(Hash::check('new-password', $result->password))->toBeTrue()
        ->and(Hash::check('old-password', $result->password))->toBeFalse();
});

test('does not update password when not provided', function () {
    $originalPassword = Hash::make('original-password');
    $user = User::factory()->create([
        'password' => $originalPassword,
    ]);

    $updateData = [
        'name' => 'Updated Name',
    ];

    $action = new UpdateUserAction();
    $result = $action->handle($user, UpdateUserInput::fromArray($updateData));

    expect($result->password)->toBe($originalPassword);
});

test('does not update password when empty string provided', function () {
    $originalPassword = Hash::make('original-password');
    $user = User::factory()->create([
        'password' => $originalPassword,
    ]);

    $updateData = [
        'name' => 'Updated Name',
        'password' => '',
    ];

    $action = new UpdateUserAction();
    $result = $action->handle($user, UpdateUserInput::fromArray($updateData));

    expect($result->password)->toBe($originalPassword);
});

test('syncs user roles when provided', function () {
    $adminRole = Role::firstOrCreate(['name' => 'TestAdmin' . uniqid()]);
    $managerRole = Role::firstOrCreate(['name' => 'TestManager' . uniqid()]);
    $staffRole = Role::firstOrCreate(['name' => 'TestStaff' . uniqid()]);

    $user = User::factory()->create();
    $user->assignRole([$adminRole->name, $staffRole->name]);

    $updateData = [
        'roles' => [$managerRole->id],
    ];

    $action = new UpdateUserAction();
    $result = $action->handle($user, UpdateUserInput::fromArray($updateData));

    expect($result->hasRole($managerRole->name))->toBeTrue()
        ->and($result->hasRole($adminRole->name))->toBeFalse()
        ->and($result->hasRole($staffRole->name))->toBeFalse()
        ->and($result->roles)->toHaveCount(1);
});

test('removes all roles when empty array provided', function () {
    $role = Role::firstOrCreate(['name' => 'TestAdmin' . uniqid()]);
    $user = User::factory()->create();
    $user->assignRole($role->name);

    $updateData = [
        'roles' => [],
    ];

    $action = new UpdateUserAction();
    $result = $action->handle($user, UpdateUserInput::fromArray($updateData));

    expect($result->roles)->toHaveCount(0);
});

test('does not change roles when not provided', function () {
    $role = Role::firstOrCreate(['name' => 'TestAdmin' . uniqid()]);
    $user = User::factory()->create();
    $user->assignRole($role->name);

    $updateData = [
        'name' => 'Updated Name',
    ];

    $action = new UpdateUserAction();
    $result = $action->handle($user, UpdateUserInput::fromArray($updateData));

    expect($result->hasRole($role->name))->toBeTrue()
        ->and($result->roles)->toHaveCount(1);
});

test('preserves existing values when not provided in update data', function () {
    $user = User::factory()->create([
        'name' => 'Original Name',
        'email' => 'original@example.com',
        'last_login_at' => now()->subDays(1),
    ]);

    $updateData = [
        'name' => 'Updated Name',
        // email and last_login_at not provided
    ];

    $action = new UpdateUserAction();
    $result = $action->handle($user, UpdateUserInput::fromArray($updateData));

    expect($result->name)->toBe('Updated Name')
        ->and($result->email)->toBe('original@example.com')
        ->and($result->last_login_at)->toEqual($user->last_login_at);
});

test('updates user appearance when provided', function () {
    $user = User::factory()->create([
        'appearance' => 'system',
    ]);

    $updateData = [
        'appearance' => 'dark',
    ];

    $action = new UpdateUserAction();
    $result = $action->handle($user, UpdateUserInput::fromArray($updateData));

    expect($result->appearance)->toBe(Appearance::Dark);
});

test('updates user theme when provided', function () {
    $user = User::factory()->create([
        'admin_theme_color' => 'neutral',
    ]);

    $updateData = [
        'admin_theme_color' => 'blue',
    ];

    $action = new UpdateUserAction();
    $result = $action->handle($user, UpdateUserInput::fromArray($updateData));

    expect($result->admin_theme_color)->toBe(AdminThemeColor::Blue);
});

test('updates both appearance and theme when provided', function () {
    $user = User::factory()->create([
        'appearance' => 'system',
        'admin_theme_color' => 'neutral',
    ]);

    $updateData = [
        'appearance' => 'light',
        'admin_theme_color' => 'emerald',
    ];

    $action = new UpdateUserAction();
    $result = $action->handle($user, UpdateUserInput::fromArray($updateData));

    expect($result->appearance)->toBe(Appearance::Light)
        ->and($result->admin_theme_color)->toBe(AdminThemeColor::Emerald);
});

test('does not update appearance when not provided', function () {
    $user = User::factory()->create([
        'appearance' => 'dark',
    ]);

    $updateData = [
        'name' => 'Updated Name',
    ];

    $action = new UpdateUserAction();
    $result = $action->handle($user, UpdateUserInput::fromArray($updateData));

    expect($result->appearance)->toBe(Appearance::Dark);
});

test('does not update theme when not provided', function () {
    $user = User::factory()->create([
        'admin_theme_color' => 'blue',
    ]);

    $updateData = [
        'name' => 'Updated Name',
    ];

    $action = new UpdateUserAction();
    $result = $action->handle($user, UpdateUserInput::fromArray($updateData));

    expect($result->admin_theme_color)->toBe(AdminThemeColor::Blue);
});

test('updates email verified at when provided', function () {
    $user = User::factory()->unverified()->create();
    $verifiedAt = now();

    $updateData = [
        'email_verified_at' => $verifiedAt,
    ];

    $action = new UpdateUserAction();
    $result = $action->handle($user, UpdateUserInput::fromArray($updateData));

    expect($result->email_verified_at->toDateTimeString())
        ->toBe($verifiedAt->toDateTimeString());
});

test('preserves email verified at when not provided', function () {
    $verifiedAt = now()->subDays(10);
    $user = User::factory()->create([
        'email_verified_at' => $verifiedAt,
    ]);

    $updateData = [
        'name' => 'Updated Name',
    ];

    $action = new UpdateUserAction();
    $result = $action->handle($user, UpdateUserInput::fromArray($updateData));

    expect($result->email_verified_at->toDateTimeString())
        ->toBe($verifiedAt->toDateTimeString());
});

test('clears email verified at when email changes', function () {
    $user = User::factory()->create([
        'email' => 'original@example.com',
        'email_verified_at' => now()->subDays(10),
    ]);

    $updateData = [
        'email' => 'new@example.com',
    ];

    $action = new UpdateUserAction();
    $result = $action->handle($user, UpdateUserInput::fromArray($updateData));

    expect($result->email)->toBe('new@example.com')
        ->and($result->email_verified_at)->toBeNull();
});

test('preserves email verified at when email stays the same', function () {
    $verifiedAt = now()->subDays(10);
    $user = User::factory()->create([
        'email' => 'same@example.com',
        'email_verified_at' => $verifiedAt,
    ]);

    $updateData = [
        'email' => 'same@example.com',
        'name' => 'Updated Name',
    ];

    $action = new UpdateUserAction();
    $result = $action->handle($user, UpdateUserInput::fromArray($updateData));

    expect($result->email_verified_at->toDateTimeString())
        ->toBe($verifiedAt->toDateTimeString());
});

test('allows explicit email verified at when email changes', function () {
    $user = User::factory()->create([
        'email' => 'original@example.com',
        'email_verified_at' => now()->subDays(10),
    ]);

    $newVerifiedAt = now();

    $updateData = [
        'email' => 'new@example.com',
        'email_verified_at' => $newVerifiedAt,
    ];

    $action = new UpdateUserAction();
    $result = $action->handle($user, UpdateUserInput::fromArray($updateData));

    expect($result->email)->toBe('new@example.com')
        ->and($result->email_verified_at->toDateTimeString())
        ->toBe($newVerifiedAt->toDateTimeString());
});
