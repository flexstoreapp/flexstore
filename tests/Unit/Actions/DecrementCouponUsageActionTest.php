<?php

declare(strict_types=1);

use App\Actions\DecrementCouponUsageAction;
use App\Models\Coupon;

covers(DecrementCouponUsageAction::class);

uses()->group('actions', 'coupon');

test('decrements coupon usage count', function (): void {
    $coupon = Coupon::factory()->create(['used_count' => 5]);

    $result = app(DecrementCouponUsageAction::class)->handle($coupon);

    expect($result)->toBeInstanceOf(Coupon::class)
        ->and($result->used_count)->toBe(4);

    $coupon->refresh();
    expect($coupon->used_count)->toBe(4);
});

test('does not decrement below zero', function (): void {
    $coupon = Coupon::factory()->create(['used_count' => 0]);

    $result = app(DecrementCouponUsageAction::class)->handle($coupon);

    expect($result->used_count)->toBe(0);

    $coupon->refresh();
    expect($coupon->used_count)->toBe(0);
});

test('decrements from one to zero', function (): void {
    $coupon = Coupon::factory()->create(['used_count' => 1]);

    $result = app(DecrementCouponUsageAction::class)->handle($coupon);

    expect($result->used_count)->toBe(0);

    $coupon->refresh();
    expect($coupon->used_count)->toBe(0);
});

test('returns original coupon when record no longer exists', function (): void {
    $coupon = Coupon::factory()->create(['used_count' => 5]);
    $originalId = $coupon->id;

    // Delete the coupon before the action runs so the lockForUpdate query returns null.
    Coupon::query()->where('id', $originalId)->delete();

    $result = app(DecrementCouponUsageAction::class)->handle($coupon);

    expect($result)->toBe($coupon);
});
