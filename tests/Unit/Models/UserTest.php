<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Enums\AdminThemeColor;
use App\Enums\Appearance;
use App\Models\CustomerAddress;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasPermissions;
use Spatie\Permission\Traits\HasRoles;

covers(User::class);

uses()->group('models', 'user');

test('has factory', function () {
    expect(User::factory())->toBeInstanceOf(Factory::class);
});

test('extends Authenticatable', function () {
    expect(User::class)->toExtend(Authenticatable::class);
});

test('uses Notifiable trait', function () {
    expect(User::class)->toUseTrait(Notifiable::class);
});

test('uses HasFactory trait', function () {
    expect(User::class)->toUseTrait(HasFactory::class);
});

test('uses HasRoles trait', function () {
    expect(User::class)->toUseTrait(HasRoles::class);
});

test('uses HasPermissions trait', function () {
    expect(User::class)->toUseTrait(HasPermissions::class);
});

test('has correct table name', function () {
    $user = new User();

    expect($user->getTable())->toBe('users');
});

test('has correct primary key', function () {
    $user = new User();

    expect($user->getKeyName())->toBe('id');
});

test('has incrementing primary key', function () {
    $user = new User();

    expect($user->getIncrementing())->toBeTrue();
});

test('uses timestamps', function () {
    $user = new User();

    expect($user->usesTimestamps())->toBeTrue();
});

test('has correct hidden attributes', function () {
    $user = new User();
    $hidden = $user->getHidden();

    expect($hidden)
        ->toBeArray()
        ->toContain('password')
        ->toContain('two_factor_secret')
        ->toContain('two_factor_recovery_codes')
        ->toContain('remember_token');
});

test('casts attributes correctly', function () {
    $user = new User();
    $casts = $user->casts();

    expect($casts)
        ->toBeArray()
        ->toHaveKey('password', 'hashed')
        ->toHaveKey('two_factor_secret', 'encrypted')
        ->toHaveKey('two_factor_recovery_codes', 'encrypted:array')
        ->toHaveKey('two_factor_confirmed_at', 'datetime')
        ->toHaveKey('email_verified_at', 'datetime')
        ->toHaveKey('last_login_at', 'datetime')
        ->toHaveKey('lifetime_value', 'decimal:4')
        ->toHaveKey('appearance', Appearance::class)
        ->toHaveKey('admin_theme_color', AdminThemeColor::class);
});

test('hasTwoFactorSecret returns false when  2FA has no secret', function () {
    $user = User::factory()->create([
        'two_factor_secret' => null,
    ]);

    expect($user->hasTwoFactorSecret())->toBeFalse();
});

test('hasTwoFactorSecret returns true when 2FA has secret', function () {
    $user = User::factory()->create([
        'two_factor_secret' => 'test-secret',
    ]);

    expect($user->hasTwoFactorSecret())->toBeTrue();
});

test('hasTwoFactorEnabled returns false when 2FA has no secret', function () {
    $user = User::factory()->create([
        'two_factor_secret' => null,
        'two_factor_confirmed_at' => null,
    ]);

    expect($user->hasTwoFactorEnabled())->toBeFalse();
});

test('hasTwoFactorEnabled returns false when 2FA has secret but not confirmed', function () {
    $user = User::factory()->create([
        'two_factor_secret' => 'test-secret',
        'two_factor_confirmed_at' => null,
    ]);

    expect($user->hasTwoFactorEnabled())->toBeFalse();
});

test('hasTwoFactorEnabled returns false when 2FA is confirmed but has no secret', function () {
    $user = User::factory()->create([
        'two_factor_secret' => null,
        'two_factor_confirmed_at' => now(),
    ]);

    expect($user->hasTwoFactorEnabled())->toBeFalse();
});

test('hasTwoFactorEnabled returns true when 2FA has secret and confirmed', function () {
    $user = User::factory()->create([
        'two_factor_secret' => 'test-secret',
        'two_factor_confirmed_at' => now(),
    ]);

    expect($user->hasTwoFactorEnabled())->toBeTrue();
});

test('hasPendingTwoFactorConfirmation returns false when 2FA has no secret', function () {
    $user = User::factory()->create([
        'two_factor_secret' => null,
        'two_factor_confirmed_at' => null,
    ]);

    expect($user->hasPendingTwoFactorConfirmation())->toBeFalse();
});

test('hasPendingTwoFactorConfirmation returns true when 2FA has secret but not confirmed', function () {
    $user = User::factory()->create([
        'two_factor_secret' => 'test-secret',
        'two_factor_confirmed_at' => null,
    ]);

    expect($user->hasPendingTwoFactorConfirmation())->toBeTrue();
});

test('hasPendingTwoFactorConfirmation returns false when 2FA has secret and confirmed', function () {
    $user = User::factory()->create([
        'two_factor_secret' => 'test-secret',
        'two_factor_confirmed_at' => now(),
    ]);

    expect($user->hasPendingTwoFactorConfirmation())->toBeFalse();
});

test('hasPendingTwoFactorConfirmation returns false when confirmed but has no secret', function () {
    $user = User::factory()->create([
        'two_factor_secret' => null,
        'two_factor_confirmed_at' => now(),
    ]);

    expect($user->hasPendingTwoFactorConfirmation())->toBeFalse();
});

test('two factor secret is encrypted in database', function () {
    $secret = 'test-secret-key';
    $user = User::factory()->create([
        'two_factor_secret' => $secret,
    ]);

    // Raw database value should be encrypted
    $rawValue = $user->getAttributes()['two_factor_secret'];
    expect($rawValue)->not->toBe($secret);
    expect($rawValue)->toBeString();

    // Decrypted value should match original
    expect($user->two_factor_secret)->toBe($secret);
});

test('user 2FA state transitions correctly', function () {
    $user = User::factory()->create();

    // Initial state: no 2FA
    expect($user->hasTwoFactorEnabled())->toBeFalse();
    expect($user->hasPendingTwoFactorConfirmation())->toBeFalse();

    // Enable 2FA (pending confirmation)
    $user->update([
        'two_factor_secret' => 'test-secret',
        'two_factor_confirmed_at' => null,
    ]);

    expect($user->hasTwoFactorEnabled())->toBeFalse();
    expect($user->hasPendingTwoFactorConfirmation())->toBeTrue();

    // Confirm 2FA
    $user->update([
        'two_factor_confirmed_at' => now(),
    ]);

    expect($user->hasTwoFactorEnabled())->toBeTrue();
    expect($user->hasPendingTwoFactorConfirmation())->toBeFalse();

    // Disable 2FA
    $user->update([
        'two_factor_secret' => null,
        'two_factor_confirmed_at' => null,
        'two_factor_recovery_codes' => null,
    ]);

    expect($user->hasTwoFactorEnabled())->toBeFalse();
    expect($user->hasPendingTwoFactorConfirmation())->toBeFalse();
});

test('has addresses relationship', function () {
    $user = User::factory()->create();
    $relationship = $user->addresses();

    expect($relationship)->toBeInstanceOf(HasMany::class);
    expect($relationship->getRelated())->toBeInstanceOf(CustomerAddress::class);
    expect($relationship->getForeignKeyName())->toBe('user_id');
    expect($relationship->getLocalKeyName())->toBe('id');
});

test('has orders relationship', function () {
    $user = User::factory()->create();
    $relationship = $user->orders();

    expect($relationship)->toBeInstanceOf(HasMany::class);
    expect($relationship->getRelated())->toBeInstanceOf(Order::class);
    expect($relationship->getForeignKeyName())->toBe('customer_id');
    expect($relationship->getLocalKeyName())->toBe('id');
});

test('order count attribute returns correct count', function () {
    $user = User::factory()->create();

    expect($user->order_count)->toBe(0);

    Order::factory()->create(['customer_id' => $user->id, 'customer_email' => $user->email]);
    Order::factory()->create(['customer_id' => $user->id, 'customer_email' => $user->email]);
    Order::factory()->create(['customer_id' => $user->id, 'customer_email' => $user->email]);

    expect($user->refresh()->order_count)->toBe(3);
});

test('last fulfilled order attribute returns last fulfilled order date', function () {
    $user = User::factory()->create();

    expect($user->last_fulfilled_order_date)->toBeNull();

    Order::factory()->unfulfilled()->create([
        'customer_id' => $user->id,
        'customer_email' => $user->email,
        'created_at' => now()->subDays(5),
    ]);

    Order::factory()->failed()->create([
        'customer_id' => $user->id,
        'customer_email' => $user->email,
        'created_at' => now()->subDays(3),
    ]);

    $latestOrder = Order::factory()->fulfilled()->create([
        'customer_id' => $user->id,
        'customer_email' => $user->email,
        'created_at' => now()->subDays(1),
    ]);

    $user->refresh();
    expect($user->last_fulfilled_order_date)->not->toBeNull();
    expect($user->last_fulfilled_order_date->toDateString())->toBe($latestOrder->created_at->toDateString());
});
