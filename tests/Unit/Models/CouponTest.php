<?php

declare(strict_types=1);

use App\Models\Coupon;
use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Relations\HasMany;

test('has factory', function () {
    expect(Coupon::factory())->toBeInstanceOf(Factory::class);
});

test('casts attributes correctly', function () {
    $coupon = Coupon::factory()->valid()->create([
        'value' => '10.5000',
        'min_order_value' => '50.0000',
        'maximum_discount' => '100.0000',
        'is_active' => true,
        'starts_at' => '2024-01-01 00:00:00',
        'expires_at' => '2024-12-31 23:59:59',
    ]);

    $casts = $coupon->casts();

    expect($casts)
        ->toBeArray()
        ->toHaveKey('value', 'decimal:4')
        ->toHaveKey('min_order_value', 'decimal:4')
        ->toHaveKey('maximum_discount', 'decimal:4')
        ->toHaveKey('is_active', 'boolean')
        ->toHaveKey('starts_at', 'datetime')
        ->toHaveKey('expires_at', 'datetime');
});

test('stores the code uppercased and trimmed', function () {
    $coupon = Coupon::factory()->valid()->create(['code' => '  summer-24  ']);

    expect($coupon->code)->toBe('SUMMER-24')
        ->and($coupon->fresh()->code)->toBe('SUMMER-24')
        ->and(Coupon::normalizeCode(' SuMmEr-24 '))->toBe('SUMMER-24');
});

test('has orders relationship', function () {
    $coupon = Coupon::factory()->valid()->create();

    expect($coupon->orders())->toBeInstanceOf(HasMany::class);
    expect($coupon->orders()->getRelated())->toBeInstanceOf(Order::class);
});

test('handles null decimal values correctly', function () {
    $coupon = Coupon::factory()->valid()->create();

    expect($coupon->min_order_value)->toBeNull();
    expect($coupon->maximum_discount)->toBeNull();
});

test('handles zero values correctly', function () {
    $coupon = Coupon::factory()->valid()->create([
        'value' => '0.0000',
        'used_count' => 0,
        'usage_limit' => 0,
    ]);

    expect($coupon->value)->toBe('0.0000');
    expect($coupon->used_count)->toBe(0);
    expect($coupon->usage_limit)->toBe(0);
});

test('handles decimal precision correctly', function () {
    $coupon = Coupon::factory()->valid()->create([
        'value' => '99.9900',
        'min_order_value' => '0.0100',
        'maximum_discount' => '999.9900',
    ]);

    expect($coupon->value)->toBe('99.9900');
    expect($coupon->min_order_value)->toBe('0.0100');
    expect($coupon->maximum_discount)->toBe('999.9900');
});
