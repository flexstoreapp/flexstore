<?php

declare(strict_types=1);

namespace Tests\Unit\Actions\User;

use App\Actions\EnableTwoFactorAuthenticationAction;
use App\Models\User;

covers(EnableTwoFactorAuthenticationAction::class);

uses()->group('actions', 'two-factor');

test('generates and stores encrypted secret', function () {
    $user = User::factory()->create();

    $action = app(EnableTwoFactorAuthenticationAction::class);
    $result = $action->handle($user);

    expect($result)->toBe($user);

    $user->refresh();
    expect($user->two_factor_secret)->not->toBeNull();
    expect($user->two_factor_secret)->toBeString();
    expect($user->two_factor_secret)->toHaveLength(16);
    expect($user->two_factor_secret)->toMatch('/^[A-Z2-7]+$/');
});

test('clears confirmation timestamp', function () {
    // User with existing 2FA data
    $user = User::factory()->create([
        'two_factor_confirmed_at' => now(),
        'two_factor_recovery_codes' => 'existing-codes',
    ]);

    $action = app(EnableTwoFactorAuthenticationAction::class);
    $action->handle($user);

    $user->refresh();
    expect($user->two_factor_confirmed_at)->toBeNull();
});

test('clears recovery codes', function () {
    // User with existing recovery codes
    $user = User::factory()->create([
        'two_factor_recovery_codes' => 'existing-codes',
    ]);

    $action = app(EnableTwoFactorAuthenticationAction::class);
    $action->handle($user);

    $user->refresh();
    expect($user->two_factor_recovery_codes)->toBeNull();
});

test('returns updated user instance', function () {
    $user = User::factory()->create();

    $action = app(EnableTwoFactorAuthenticationAction::class);
    $result = $action->handle($user);

    expect($result)
        ->toBeInstanceOf(User::class)
        ->toBe($user);
});

test('handles user with no existing 2FA data', function () {
    // Ensure user has no 2FA data
    $user = User::factory()->create([
        'two_factor_secret' => null,
        'two_factor_confirmed_at' => null,
        'two_factor_recovery_codes' => null,
    ]);

    $action = app(EnableTwoFactorAuthenticationAction::class);
    $result = $action->handle($user);

    expect($result)->toBe($user);

    $user->refresh();
    expect($user->two_factor_secret)->not->toBeNull();
    expect($user->two_factor_confirmed_at)->toBeNull();
    expect($user->two_factor_recovery_codes)->toBeNull();
});

test('overwrites existing secret', function () {
    $oldSecret = 'OLDSECRETKEY1234';

    // User with existing secret
    $user = User::factory()->create([
        'two_factor_secret' => $oldSecret,
    ]);

    $action = app(EnableTwoFactorAuthenticationAction::class);
    $action->handle($user);

    $user->refresh();
    expect($user->two_factor_secret)->not->toBe($oldSecret);
    expect($user->two_factor_secret)->not->toBeNull();
});

test('generates unique secrets on multiple calls', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    $action = app(EnableTwoFactorAuthenticationAction::class);
    $action->handle($user1);
    $action->handle($user2);

    $user1->refresh();
    $user2->refresh();

    expect($user1->two_factor_secret)->not->toBe($user2->two_factor_secret);
});

test('persists changes to database', function () {
    $user = User::factory()->create();

    $action = app(EnableTwoFactorAuthenticationAction::class);
    $action->handle($user);

    // Verify changes are persisted by creating fresh instance
    $freshUser = User::find($user->id);
    expect($freshUser->two_factor_secret)->not->toBeNull();
    expect($freshUser->two_factor_confirmed_at)->toBeNull();
    expect($freshUser->two_factor_recovery_codes)->toBeNull();
});
