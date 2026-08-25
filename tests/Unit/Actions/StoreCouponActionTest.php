<?php

declare(strict_types=1);

use App\Actions\StoreCouponAction;
use App\DTOs\StoreCouponInput;
use App\Enums\CouponType;
use App\Models\Coupon;

use function Pest\Laravel\assertDatabaseHas;

covers(StoreCouponAction::class, StoreCouponInput::class);

uses()->group('actions', 'coupon');

test('creates a coupon with basic information', function () {
    $couponData = [
        'code' => 'SAVE20',
        'type' => CouponType::Percentage,
        'value' => 20.00,
    ];

    $action = new StoreCouponAction();
    $result = $action->handle(StoreCouponInput::fromArray($couponData));

    expect($result)->toBeInstanceOf(Coupon::class)
        ->and(Coupon::query()->count())->toBe(1)
        ->and($result->code)->toBe('SAVE20')
        ->and($result->type)->toBe(CouponType::Percentage)
        ->and($result->value)->toBe('20.0000')
        ->and($result->is_active)->toBeTrue()
        ->and($result->min_order_value)->toBeNull()
        ->and($result->maximum_discount)->toBeNull()
        ->and($result->usage_limit)->toBeNull()
        ->and($result->usage_limit_per_customer)->toBeNull()
        ->and($result->starts_at)->toBeNull()
        ->and($result->expires_at)->toBeNull();

    assertDatabaseHas('coupons', [
        'code' => 'SAVE20',
        'type' => CouponType::Percentage->value,
        'value' => '20.0000',
        'is_active' => true,
    ]);
});

test('creates a coupon with all optional fields', function () {
    $couponData = [
        'code' => 'FULL50',
        'type' => CouponType::Flat,
        'value' => 50.00,
        'min_order_value' => 100.00,
        'maximum_discount' => 75.00,
        'usage_limit' => 500,
        'usage_limit_per_customer' => 2,
        'is_active' => false,
        'starts_at' => now()->addDay(),
        'expires_at' => now()->addMonth(),
    ];

    $action = new StoreCouponAction();
    $result = $action->handle(StoreCouponInput::fromArray($couponData));

    expect($result)->toBeInstanceOf(Coupon::class)
        ->and($result->code)->toBe('FULL50')
        ->and($result->type)->toBe(CouponType::Flat)
        ->and($result->value)->toBe('50.0000')
        ->and($result->min_order_value)->toBe('100.0000')
        ->and($result->maximum_discount)->toBe('75.0000')
        ->and($result->usage_limit)->toBe(500)
        ->and($result->usage_limit_per_customer)->toBe(2)
        ->and($result->is_active)->toBeFalse()
        ->and($result->starts_at)->not->toBeNull()
        ->and($result->expires_at)->not->toBeNull();
});

test('normalizes lowercase code to uppercase', function () {
    $couponData = [
        'code' => 'lowercase',
        'type' => CouponType::Flat,
        'value' => 10.00,
    ];

    $action = new StoreCouponAction();
    $result = $action->handle(StoreCouponInput::fromArray($couponData));

    expect($result->code)->toBe('LOWERCASE');

    assertDatabaseHas('coupons', [
        'code' => 'LOWERCASE',
    ]);
});

test('normalizes mixed case code to uppercase', function () {
    $couponData = [
        'code' => 'MiXeD-CaSe_123',
        'type' => CouponType::Percentage,
        'value' => 15.00,
    ];

    $action = new StoreCouponAction();
    $result = $action->handle(StoreCouponInput::fromArray($couponData));

    expect($result->code)->toBe('MIXED-CASE_123');
});

test('defaults is_active to true when not provided', function () {
    $couponData = [
        'code' => 'DEFAULT',
        'type' => CouponType::Flat,
        'value' => 25.00,
    ];

    $action = new StoreCouponAction();
    $result = $action->handle(StoreCouponInput::fromArray($couponData));

    expect($result->is_active)->toBeTrue();
});

test('respects explicit is_active false', function () {
    $couponData = [
        'code' => 'INACTIVE',
        'type' => CouponType::Flat,
        'value' => 25.00,
        'is_active' => false,
    ];

    $action = new StoreCouponAction();
    $result = $action->handle(StoreCouponInput::fromArray($couponData));

    expect($result->is_active)->toBeFalse();
});

test('creates percentage coupon correctly', function () {
    $couponData = [
        'code' => 'PERCENT30',
        'type' => CouponType::Percentage,
        'value' => 30.00,
        'maximum_discount' => 100.00,
    ];

    $action = new StoreCouponAction();
    $result = $action->handle(StoreCouponInput::fromArray($couponData));

    expect($result->type)->toBe(CouponType::Percentage)
        ->and($result->value)->toBe('30.0000')
        ->and($result->maximum_discount)->toBe('100.0000');
});

test('creates flat discount coupon correctly', function () {
    $couponData = [
        'code' => 'FLAT15',
        'type' => CouponType::Flat,
        'value' => 15.00,
        'min_order_value' => 50.00,
    ];

    $action = new StoreCouponAction();
    $result = $action->handle(StoreCouponInput::fromArray($couponData));

    expect($result->type)->toBe(CouponType::Flat)
        ->and($result->value)->toBe('15.0000')
        ->and($result->min_order_value)->toBe('50.0000');
});

test('handles null values for optional fields', function () {
    $couponData = [
        'code' => 'NULLS',
        'type' => CouponType::Flat,
        'value' => 10.00,
        'min_order_value' => null,
        'maximum_discount' => null,
        'usage_limit' => null,
        'usage_limit_per_customer' => null,
        'starts_at' => null,
        'expires_at' => null,
    ];

    $action = new StoreCouponAction();
    $result = $action->handle(StoreCouponInput::fromArray($couponData));

    expect($result->min_order_value)->toBeNull()
        ->and($result->maximum_discount)->toBeNull()
        ->and($result->usage_limit)->toBeNull()
        ->and($result->usage_limit_per_customer)->toBeNull()
        ->and($result->starts_at)->toBeNull()
        ->and($result->expires_at)->toBeNull();
});

test('creates coupon with usage limits', function () {
    $couponData = [
        'code' => 'LIMITED',
        'type' => CouponType::Percentage,
        'value' => 25.00,
        'usage_limit' => 100,
        'usage_limit_per_customer' => 1,
    ];

    $action = new StoreCouponAction();
    $result = $action->handle(StoreCouponInput::fromArray($couponData));

    expect($result->usage_limit)->toBe(100)
        ->and($result->usage_limit_per_customer)->toBe(1);

    // Refresh to get database defaults
    $result->refresh();
    expect($result->used_count)->toBe(0);
});

test('creates coupon with date constraints', function () {
    $startDate = now()->addDay();
    $endDate = now()->addMonth();

    $couponData = [
        'code' => 'TIMED',
        'type' => CouponType::Flat,
        'value' => 20.00,
        'starts_at' => $startDate,
        'expires_at' => $endDate,
    ];

    $action = new StoreCouponAction();
    $result = $action->handle(StoreCouponInput::fromArray($couponData));

    expect($result->starts_at->format('Y-m-d H:i:s'))->toBe($startDate->format('Y-m-d H:i:s'))
        ->and($result->expires_at->format('Y-m-d H:i:s'))->toBe($endDate->format('Y-m-d H:i:s'));
});

test('creates multiple coupons with different codes', function () {
    $action = new StoreCouponAction();

    $coupon1 = $action->handle(StoreCouponInput::fromArray([
        'code' => 'FIRST',
        'type' => CouponType::Flat,
        'value' => 10.00,
    ]));

    $coupon2 = $action->handle(StoreCouponInput::fromArray([
        'code' => 'SECOND',
        'type' => CouponType::Percentage,
        'value' => 20.00,
    ]));

    expect(Coupon::query()->count())->toBe(2)
        ->and($coupon1->code)->toBe('FIRST')
        ->and($coupon2->code)->toBe('SECOND')
        ->and($coupon1->id)->not->toBe($coupon2->id);
});

test('handles decimal values correctly', function () {
    $couponData = [
        'code' => 'DECIMAL',
        'type' => CouponType::Flat,
        'value' => 12.99,
        'min_order_value' => 99.99,
        'maximum_discount' => 50.50,
    ];

    $action = new StoreCouponAction();
    $result = $action->handle(StoreCouponInput::fromArray($couponData));

    expect($result->value)->toBe('12.9900')
        ->and($result->min_order_value)->toBe('99.9900')
        ->and($result->maximum_discount)->toBe('50.5000');
});
