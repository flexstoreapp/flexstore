<?php

declare(strict_types=1);

use App\Http\Controllers\Installer\AccountController;
use App\Http\Requests\Installer\StoreAccountRequest;
use App\Installer\Contracts\InstallationState;
use App\Models\User;

use function Pest\Laravel\get;
use function Pest\Laravel\post;

covers(AccountController::class, StoreAccountRequest::class);

uses()->group('installer');

beforeEach(function () {
    $mock = Mockery::mock(InstallationState::class);
    $mock->shouldReceive('isInstalled')->andReturn(false);
    $mock->shouldReceive('databaseIsMigrated')->andReturn(true);
    app()->instance(InstallationState::class, $mock);
});

test('account page can be rendered', function () {
    get(route('installer.account.create'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('installer/account'));
});

test('name is required', function () {
    post(route('installer.account.store'), [
        'email' => 'admin@flexstore.app',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
    ])->assertSessionHasErrors('name');
});

test('email is required', function () {
    post(route('installer.account.store'), [
        'name' => 'Admin',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
    ])->assertSessionHasErrors('email');
});

test('email must be valid', function () {
    post(route('installer.account.store'), [
        'name' => 'Admin',
        'email' => 'not-an-email',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
    ])->assertSessionHasErrors('email');
});

test('email must be unique', function () {
    User::factory()->create(['email' => 'admin@flexstore.app']);

    post(route('installer.account.store'), [
        'name' => 'Admin',
        'email' => 'admin@flexstore.app',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
    ])->assertSessionHasErrors('email');
});

test('password is required', function () {
    post(route('installer.account.store'), [
        'name' => 'Admin',
        'email' => 'admin@flexstore.app',
    ])->assertSessionHasErrors('password');
});

test('password must be confirmed', function () {
    post(route('installer.account.store'), [
        'name' => 'Admin',
        'email' => 'admin@flexstore.app',
        'password' => 'Password123!',
    ])->assertSessionHasErrors('password');
});

test('account page redirects to database step when database is not migrated', function () {
    $mock = Mockery::mock(InstallationState::class);
    $mock->shouldReceive('isInstalled')->andReturn(false);
    $mock->shouldReceive('databaseIsMigrated')->andReturn(false);
    app()->instance(InstallationState::class, $mock);

    get(route('installer.account.create'))
        ->assertRedirect(route('installer.database.create'));
});

test('account store redirects to database step when database is not migrated', function () {
    $mock = Mockery::mock(InstallationState::class);
    $mock->shouldReceive('isInstalled')->andReturn(false);
    $mock->shouldReceive('databaseIsMigrated')->andReturn(false);
    app()->instance(InstallationState::class, $mock);

    post(route('installer.account.store'), [
        'name' => 'Admin User',
        'email' => 'admin@flexstore.app',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
    ])->assertRedirect(route('installer.database.create'));
});
