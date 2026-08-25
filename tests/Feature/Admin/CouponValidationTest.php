<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\CouponValidationController;
use App\Http\Requests\Admin\ValidateCouponRequest;
use App\Models\Coupon;
use App\Utilities\CouponValidator;

use function Pest\Laravel\postJson;

covers(CouponValidationController::class, ValidateCouponRequest::class, CouponValidator::class);

uses()->group('coupon');

test('returns success message for valid coupon', function () {
    Coupon::factory()->active()->percentage(10)->valid()->create([
        'code' => 'VALID_COUPON',
        'expires_at' => now()->addDay(),
    ]);

    $response = actingAsSuperAdmin()
        ->postJson(route('admin.coupons.validate'), [
            'coupon_code' => 'VALID_COUPON',
            'subtotal' => '100',
            'customer_email' => 'test@example.com',
        ]);

    $response->assertOk()
        ->assertJsonStructure([
            'coupon' => ['id', 'code', 'type', 'value'],
            'discount',
        ]);
});

test('returns specific error message for invalid coupon code', function () {
    $response = actingAsSuperAdmin()
        ->postJson(route('admin.coupons.validate'), [
            'coupon_code' => 'INVALID_CODE',
            'subtotal' => '100',
            'customer_email' => 'test@example.com',
        ]);

    $response->assertStatus(422)
        ->assertJson([
            'message' => __('Invalid coupon code.'),
        ]);
});

test('returns specific error message for expired coupon', function () {
    Coupon::factory()->active()->expired()->create([
        'code' => 'EXPIRED_COUPON',
    ]);

    $response = actingAsSuperAdmin()
        ->postJson(route('admin.coupons.validate'), [
            'coupon_code' => 'EXPIRED_COUPON',
            'subtotal' => '100',
            'customer_email' => 'test@example.com',
        ]);

    $response->assertStatus(422)
        ->assertJson([
            'message' => __('This coupon is no longer valid.'),
        ]);
});

test('requires authentication', function () {
    Coupon::factory()->active()->percentage(10)->valid()->create([
        'code' => 'TEST_COUPON',
        'expires_at' => now()->addDay(),
    ]);

    $response = postJson(route('admin.coupons.validate'), [
        'coupon_code' => 'TEST_COUPON',
        'subtotal' => '100',
        'customer_email' => 'test@example.com',
    ]);

    $response->assertUnauthorized();
});
