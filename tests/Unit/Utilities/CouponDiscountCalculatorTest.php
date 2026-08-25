<?php

declare(strict_types=1);

use App\Enums\CouponType;
use App\Models\Coupon;
use App\Utilities\CouponDiscountCalculator;

covers(CouponDiscountCalculator::class);

uses()->group('utilities', 'coupon');

test('calculates percentage discount correctly', function () {
    $coupon = Coupon::factory()->valid()->create([
        'type' => CouponType::Percentage,
        'value' => '10.0000',
    ]);

    $calculator = new CouponDiscountCalculator();
    $discount = $calculator->calculate($coupon, '100.0000');

    expect($discount)->toBe('10.0000');
});

test('calculates fixed discount correctly', function () {
    $coupon = Coupon::factory()->valid()->create([
        'type' => CouponType::Flat,
        'value' => '15.0000',
    ]);

    $calculator = new CouponDiscountCalculator();
    $discount = $calculator->calculate($coupon, '100.0000');

    expect($discount)->toBe('15.0000');
});

test('calculates percentage discount with proper rounding', function () {
    $coupon = Coupon::factory()->valid()->create([
        'type' => CouponType::Percentage,
        'value' => '33.3300',
    ]);

    $calculator = new CouponDiscountCalculator();
    $discount = $calculator->calculate($coupon, '10.0000');

    expect($discount)->toBe('3.3330');
});

test('applies maximum discount limit for percentage coupons', function () {
    $coupon = Coupon::factory()->valid()->create([
        'type' => CouponType::Percentage,
        'value' => '50.0000', // 50% of 200 = 100
        'maximum_discount' => '25.0000', // But max is 25
    ]);

    $calculator = new CouponDiscountCalculator();
    $discount = $calculator->calculate($coupon, '200.0000');

    expect($discount)->toBe('25.0000');
});

test('applies maximum discount limit for fixed coupons', function () {
    $coupon = Coupon::factory()->valid()->create([
        'type' => CouponType::Flat,
        'value' => '30.0000',
        'maximum_discount' => '25.0000', // Maximum discount is applied to fixed coupons too
    ]);

    $calculator = new CouponDiscountCalculator();
    $discount = $calculator->calculate($coupon, '100.0000');

    expect($discount)->toBe('25.0000');
});

test('does not exceed subtotal for fixed discount', function () {
    $coupon = Coupon::factory()->valid()->create([
        'type' => CouponType::Flat,
        'value' => '150.0000', // More than subtotal
    ]);

    $calculator = new CouponDiscountCalculator();
    $discount = $calculator->calculate($coupon, '100.0000');

    expect($discount)->toBe('100.0000'); // Should not exceed subtotal
});

test('returns zero discount when subtotal is below minimum amount', function () {
    $coupon = Coupon::factory()->valid()->create([
        'type' => CouponType::Percentage,
        'value' => '10.0000',
        'min_order_value' => '100.0000',
    ]);

    $calculator = new CouponDiscountCalculator();
    $discount = $calculator->calculate($coupon, '50.0000');

    expect($discount)->toBe('0.0000');
});

test('calculates discount when subtotal meets minimum amount', function () {
    $coupon = Coupon::factory()->valid()->create([
        'type' => CouponType::Percentage,
        'value' => '10.0000',
        'min_order_value' => '100.0000',
    ]);

    $calculator = new CouponDiscountCalculator();
    $discount = $calculator->calculate($coupon, '100.0000');

    expect($discount)->toBe('10.0000');
});

test('calculates discount when subtotal exceeds minimum amount', function () {
    $coupon = Coupon::factory()->valid()->create([
        'type' => CouponType::Percentage,
        'value' => '10.0000',
        'min_order_value' => '50.0000',
    ]);

    $calculator = new CouponDiscountCalculator();
    $discount = $calculator->calculate($coupon, '100.0000');

    expect($discount)->toBe('10.0000');
});

test('handles zero subtotal', function () {
    $coupon = Coupon::factory()->valid()->create([
        'type' => CouponType::Percentage,
        'value' => '10.0000',
    ]);

    $calculator = new CouponDiscountCalculator();
    $discount = $calculator->calculate($coupon, '0.0000');

    expect($discount)->toBe('0.0000');
});

test('handles zero percentage value', function () {
    $coupon = Coupon::factory()->valid()->create([
        'type' => CouponType::Percentage,
        'value' => '0.0000',
    ]);

    $calculator = new CouponDiscountCalculator();
    $discount = $calculator->calculate($coupon, '100.0000');

    expect($discount)->toBe('0.0000');
});

test('handles zero fixed value', function () {
    $coupon = Coupon::factory()->valid()->create([
        'type' => CouponType::Flat,
        'value' => '0.0000',
    ]);

    $calculator = new CouponDiscountCalculator();
    $discount = $calculator->calculate($coupon, '100.0000');

    expect($discount)->toBe('0.0000');
});

test('handles large percentage values', function () {
    $coupon = Coupon::factory()->valid()->create([
        'type' => CouponType::Percentage,
        'value' => '100.0000', // 100%
    ]);

    $calculator = new CouponDiscountCalculator();
    $discount = $calculator->calculate($coupon, '50.0000');

    expect($discount)->toBe('50.0000'); // Should not exceed subtotal
});

test('handles decimal subtotal values', function () {
    $coupon = Coupon::factory()->valid()->create([
        'type' => CouponType::Percentage,
        'value' => '10.0000',
    ]);

    $calculator = new CouponDiscountCalculator();
    $discount = $calculator->calculate($coupon, '99.9900');

    expect($discount)->toBe('9.9990'); // 10% of 99.99 = 9.999, at 4 decimal scale = 9.9990
});

test('handles complex percentage calculation with maximum discount', function () {
    $coupon = Coupon::factory()->valid()->create([
        'type' => CouponType::Percentage,
        'value' => '15.7500', // 15.75%
        'maximum_discount' => '20.0000',
    ]);

    $calculator = new CouponDiscountCalculator();
    $discount = $calculator->calculate($coupon, '200.0000');

    expect($discount)->toBe('20.0000'); // 15.75% of 200 = 31.50, but capped at 20.00
});
