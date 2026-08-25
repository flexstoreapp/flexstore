<?php

declare(strict_types=1);

use App\DTOs\CouponValidationResult;
use App\Enums\CouponType;
use App\Models\Coupon;
use App\Models\Order;
use App\Utilities\CouponDiscountCalculator;
use App\Utilities\CouponValidator;
use Illuminate\Validation\ValidationException;

covers(CouponValidator::class, CouponDiscountCalculator::class, CouponValidationResult::class);

uses()->group('utilities', 'coupon');

test('validates a valid coupon successfully', function () {
    $coupon = Coupon::factory()->valid()->create([
        'code' => 'VALID10',
        'type' => CouponType::Percentage,
        'value' => '10.0000',
        'is_active' => true,
        'starts_at' => now()->subDay(),
        'expires_at' => now()->addDay(),
        'used_count' => 0,
    ]);

    $validator = new CouponValidator(new CouponDiscountCalculator());
    $result = $validator->validate('VALID10', '100.0000', 'test@example.com');

    expect($result)->toBeInstanceOf(CouponValidationResult::class)
        ->and($result->coupon->id)->toBe($coupon->id)
        ->and($result->discount)->toBe('10.0000');
});

test('calculates percentage discount correctly', function () {
    Coupon::factory()->valid()->create([
        'code' => 'PERCENT20',
        'type' => CouponType::Percentage,
        'value' => '20.0000',
        'is_active' => true,
        'used_count' => 0,
    ]);

    $validator = new CouponValidator(new CouponDiscountCalculator());
    $result = $validator->validate('PERCENT20', '100.0000');

    expect($result->discount)->toBe('20.0000');
});

test('calculates fixed discount correctly', function () {
    Coupon::factory()->valid()->create([
        'code' => 'FIXED15',
        'type' => CouponType::Flat,
        'value' => '15.0000',
        'is_active' => true,
        'used_count' => 0,
    ]);

    $validator = new CouponValidator(new CouponDiscountCalculator());
    $result = $validator->validate('FIXED15', '100.0000');

    expect($result->discount)->toBe('15.0000');
});

test('applies maximum discount limit for percentage coupons', function () {
    Coupon::factory()->valid()->create([
        'code' => 'MAXED_PERCENT',
        'type' => CouponType::Percentage,
        'value' => '50.0000', // 50% of 200 = 100
        'is_active' => true,
        'used_count' => 0,
        'maximum_discount' => '25.0000', // But max is 25
    ]);

    $validator = new CouponValidator(new CouponDiscountCalculator());
    $result = $validator->validate('MAXED_PERCENT', '200');

    expect($result->discount)->toBe('25.0000');
});

test('does not exceed subtotal for fixed discount', function () {
    Coupon::factory()->valid()->create([
        'code' => 'BIG_FIXED',
        'type' => CouponType::Flat,
        'value' => '150.0000', // More than subtotal
        'is_active' => true,
        'used_count' => 0,
    ]);

    $validator = new CouponValidator(new CouponDiscountCalculator());
    $result = $validator->validate('BIG_FIXED', '100.0000');

    expect($result->discount)->toBe('100.0000'); // Should not exceed subtotal
});

test('allows customer usage when under limit', function () {
    $coupon = Coupon::factory()->valid()->create([
        'code' => 'CUSTOMER_OK',
        'type' => CouponType::Flat,
        'value' => '10.0000',
        'usage_limit_per_customer' => 3,
        'is_active' => true,
    ]);

    Order::factory()->create([
        'customer_email' => 'test@example.com',
        'coupon_id' => $coupon->id,
    ]);

    $validator = new CouponValidator(new CouponDiscountCalculator());
    $result = $validator->validate('CUSTOMER_OK', '100.0000', 'test@example.com');

    expect($result->coupon->id)->toBe($coupon->id);
});

test('skips customer usage check when no customer email provided', function () {
    $coupon = Coupon::factory()->valid()->create([
        'code' => 'NO_EMAIL',
        'type' => CouponType::Flat,
        'value' => '10.0000',
        'is_active' => true,
        'used_count' => 0,
        'usage_limit_per_customer' => 1,
    ]);

    Order::factory()->count(5)->create([
        'customer_email' => 'other@example.com',
        'coupon_id' => $coupon->id,
    ]);

    // Should not throw exception since no customer email is provided
    $validator = new CouponValidator(new CouponDiscountCalculator());
    $result = $validator->validate('NO_EMAIL', '100.0000');

    expect($result->coupon->id)->toBe($coupon->id);
});

test('validates coupon with all constraints satisfied', function () {
    $coupon = Coupon::factory()->valid()->create([
        'code' => 'COMPLEX',
        'type' => CouponType::Percentage,
        'value' => '15.0000',
        'is_active' => true,
        'starts_at' => now()->subHour(),
        'expires_at' => now()->addHour(),
        'min_order_value' => '50.0000',
        'maximum_discount' => '20.0000',
        'usage_limit' => 100,
        'usage_limit_per_customer' => 5,
        'used_count' => 10,
    ]);

    // Create 2 orders for customer (under limit of 5)
    Order::factory()->count(2)->create([
        'customer_email' => 'test@example.com',
        'coupon_id' => $coupon->id,
    ]);

    $validator = new CouponValidator(new CouponDiscountCalculator());

    $result = $validator->validate('COMPLEX', '100.0000', 'test@example.com');

    expect($result->coupon->id)->toBe($coupon->id)
        ->and($result->discount)->toBe('15.0000'); // 15% of 100
});

test('returns CouponValidationResult DTO', function () {
    $coupon = Coupon::factory()->valid()->create([
        'code' => 'STRUCTURE',
        'type' => CouponType::Flat,
        'value' => '25.0000',
        'is_active' => true,
        'used_count' => 0,
    ]);

    $validator = new CouponValidator(new CouponDiscountCalculator());
    $result = $validator->validate('STRUCTURE', '100.0000');

    expect($result)->toBeInstanceOf(CouponValidationResult::class)
        ->and($result->coupon)->toBeInstanceOf(Coupon::class)
        ->and($result->discount)->toBeString()
        ->and($result->coupon->id)->toBe($coupon->id)
        ->and($result->discount)->toBe('25.0000');
});

test('throws exception for non-existent coupon', function () {
    $validator = new CouponValidator(new CouponDiscountCalculator());

    expect(fn () => $validator->validate('NONEXISTENT', '100.0000'))
        ->toThrow(ValidationException::class);
});

test('throws exception for inactive coupon', function () {
    Coupon::factory()->valid()->inactive()->create([
        'code' => 'INACTIVE',
    ]);

    $validator = new CouponValidator(new CouponDiscountCalculator());

    expect(fn () => $validator->validate('INACTIVE', '100.0000'))
        ->toThrow(ValidationException::class);
});

test('throws exception for expired coupon', function () {
    Coupon::factory()->expired()->active()->create([
        'code' => 'EXPIRED',
    ]);

    $validator = new CouponValidator(new CouponDiscountCalculator());

    expect(fn () => $validator->validate('EXPIRED', '100.0000'))
        ->toThrow(ValidationException::class);
});

test('throws exception for future coupon', function () {
    Coupon::factory()->valid()->create([
        'code' => 'FUTURE',
        'is_active' => true,
        'starts_at' => now()->addDay(),
    ]);

    $validator = new CouponValidator(new CouponDiscountCalculator());

    expect(fn () => $validator->validate('FUTURE', '100.0000'))
        ->toThrow(ValidationException::class);
});

test('throws exception for usage limit exceeded', function () {
    Coupon::factory()->valid()->create([
        'code' => 'LIMIT_EXCEEDED',
        'is_active' => true,
        'usage_limit' => 5,
        'used_count' => 5,
    ]);

    $validator = new CouponValidator(new CouponDiscountCalculator());

    expect(fn () => $validator->validate('LIMIT_EXCEEDED', '100.0000'))
        ->toThrow(ValidationException::class);
});

test('throws exception for customer usage limit exceeded', function () {
    $coupon = Coupon::factory()->valid()->create([
        'code' => 'CUSTOMER_LIMIT',
        'is_active' => true,
        'usage_limit_per_customer' => 2,
    ]);

    Order::factory()->count(2)->create([
        'customer_email' => 'test@example.com',
        'coupon_id' => $coupon->id,
    ]);

    $validator = new CouponValidator(new CouponDiscountCalculator());

    expect(fn () => $validator->validate('CUSTOMER_LIMIT', '100.0000', 'test@example.com'))
        ->toThrow(ValidationException::class);
});

test('throws exception when discount is zero due to minimum amount', function () {
    Coupon::factory()->valid()->create([
        'code' => 'MIN_AMOUNT',
        'type' => CouponType::Flat,
        'value' => '10.0000',
        'is_active' => true,
        'min_order_value' => '100.0000',
    ]);

    $validator = new CouponValidator(new CouponDiscountCalculator());

    expect(fn () => $validator->validate('MIN_AMOUNT', '50.0000'))
        ->toThrow(ValidationException::class);
});

test('handles edge case with zero subtotal', function () {
    Coupon::factory()->valid()->create([
        'code' => 'ZERO_SUB',
        'type' => CouponType::Percentage,
        'value' => '10.0000',
        'is_active' => true,
    ]);

    $validator = new CouponValidator(new CouponDiscountCalculator());

    expect(fn () => $validator->validate('ZERO_SUB', '0.0000'))
        ->toThrow(ValidationException::class);
});

test('validates coupon code with leading/trailing whitespace', function () {
    $coupon = Coupon::factory()->valid()->create([
        'code' => 'TRIMMED',
        'type' => CouponType::Percentage,
        'value' => '10.0000',
        'is_active' => true,
    ]);

    $validator = new CouponValidator(new CouponDiscountCalculator());
    $result = $validator->validate('  TRIMMED  ', '100.0000');

    expect($result->discount)->toBe('10.0000');
});

test('validates coupon code regardless of case', function () {
    Coupon::factory()->valid()->create([
        'code' => 'CASELESS',
        'type' => CouponType::Percentage,
        'value' => '10.0000',
    ]);

    $validator = new CouponValidator(new CouponDiscountCalculator());

    expect($validator->validate('caseless', '100.0000')->discount)->toBe('10.0000')
        ->and($validator->validate(' CaSeLeSs ', '100.0000')->discount)->toBe('10.0000');
});

test('validates coupon just before usage limit is reached', function () {
    $coupon = Coupon::factory()->valid()->create([
        'code' => 'ALMOST_LIMIT',
        'is_active' => true,
        'usage_limit' => 10,
        'used_count' => 9,
    ]);

    $validator = new CouponValidator(new CouponDiscountCalculator());
    $result = $validator->validate('ALMOST_LIMIT', '100.0000');

    expect($result->coupon->id)->toBe($coupon->id);
});

test('skips customer usage check when customer_email is null', function () {
    $coupon = Coupon::factory()->valid()->create([
        'code' => 'SKIP_CHECK',
        'type' => CouponType::Flat,
        'value' => '10.0000',
        'usage_limit_per_customer' => 1,
        'is_active' => true,
    ]);

    Order::factory()->create([
        'customer_email' => 'test@example.com',
        'coupon_id' => $coupon->id,
    ]);

    $validator = new CouponValidator(new CouponDiscountCalculator());
    $result = $validator->validate('SKIP_CHECK', '100.0000', null);

    expect($result->coupon->id)->toBe($coupon->id);
});

test('validates coupon code with whitespace', function () {
    $coupon = Coupon::factory()->valid()->create([
        'code' => 'SAVE20',
        'type' => CouponType::Percentage,
        'value' => '20.0000',
        'is_active' => true,
    ]);

    $validator = new CouponValidator(new CouponDiscountCalculator());
    $result = $validator->validate('  SAVE20  ', '100.0000');

    expect($result->coupon->id)->toBe($coupon->id)
        ->and($result->discount)->toBe('20.0000');
});
