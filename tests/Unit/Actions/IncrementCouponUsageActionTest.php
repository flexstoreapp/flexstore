<?php

declare(strict_types=1);

use App\Actions\IncrementCouponUsageAction;
use App\Models\Coupon;

covers(IncrementCouponUsageAction::class);

uses()->group('actions', 'coupon');

test('increments coupon used_count by 1', function () {
    $coupon = Coupon::factory()->valid()->create(['used_count' => 5]);

    $action = new IncrementCouponUsageAction();
    $result = $action->handle($coupon);

    expect($result)->toBeInstanceOf(Coupon::class)
        ->and($result->used_count)->toBe(6)
        ->and($result->id)->toBe($coupon->id);
});

test('increments coupon used_count from 0', function () {
    $coupon = Coupon::factory()->valid()->create(['used_count' => 0]);

    $action = new IncrementCouponUsageAction();
    $result = $action->handle($coupon);

    expect($result->used_count)->toBe(1);
});

test('persists the increment to the database', function () {
    $coupon = Coupon::factory()->valid()->create(['used_count' => 10]);

    $action = new IncrementCouponUsageAction();
    $action->handle($coupon);

    // Refresh from database to verify persistence
    $coupon->refresh();
    expect($coupon->used_count)->toBe(11);
});

test('can be called multiple times', function () {
    $coupon = Coupon::factory()->valid()->create(['used_count' => 0]);

    $action = new IncrementCouponUsageAction();

    $action->handle($coupon);
    $action->handle($coupon);
    $action->handle($coupon);

    $coupon->refresh();
    expect($coupon->used_count)->toBe(3);
});
