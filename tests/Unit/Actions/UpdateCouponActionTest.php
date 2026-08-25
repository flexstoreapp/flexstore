<?php

declare(strict_types=1);

use App\Actions\UpdateCouponAction;
use App\DTOs\UpdateCouponInput;
use App\Enums\CouponType;
use App\Models\Coupon;

use function Pest\Laravel\assertDatabaseHas;

covers(UpdateCouponAction::class, UpdateCouponInput::class);

uses()->group('actions', 'coupon');

test('updates a coupon with basic information', function () {
    $coupon = Coupon::factory()->create([
        'code' => 'ORIGINAL',
        'type' => CouponType::Flat,
        'value' => 10.00,
        'is_active' => false,
    ]);

    $updateData = [
        'code' => 'UPDATED',
        'type' => CouponType::Percentage,
        'value' => 25.00,
        'is_active' => true,
    ];

    $action = new UpdateCouponAction();
    $result = $action->handle($coupon, UpdateCouponInput::fromArray($updateData));

    expect($result)->toBeInstanceOf(Coupon::class)
        ->and($result->id)->toBe($coupon->id)
        ->and($result->code)->toBe('UPDATED')
        ->and($result->type)->toBe(CouponType::Percentage)
        ->and($result->value)->toBe('25.0000')
        ->and($result->is_active)->toBeTrue();

    assertDatabaseHas('coupons', [
        'id' => $coupon->id,
        'code' => 'UPDATED',
        'type' => CouponType::Percentage->value,
        'value' => '25.0000',
        'is_active' => true,
    ]);
});

test('updates a coupon with all fields', function () {
    $coupon = Coupon::factory()->create([
        'code' => 'OLD',
        'type' => CouponType::Flat,
        'value' => 5.00,
    ]);

    $updateData = [
        'code' => 'NEW',
        'type' => CouponType::Percentage,
        'value' => 30.00,
        'min_order_value' => 150.00,
        'maximum_discount' => 100.00,
        'usage_limit' => 200,
        'usage_limit_per_customer' => 3,
        'is_active' => false,
        'starts_at' => now()->addWeek(),
        'expires_at' => now()->addMonth(),
    ];

    $action = new UpdateCouponAction();
    $result = $action->handle($coupon, UpdateCouponInput::fromArray($updateData));

    expect($result->code)->toBe('NEW')
        ->and($result->type)->toBe(CouponType::Percentage)
        ->and($result->value)->toBe('30.0000')
        ->and($result->min_order_value)->toBe('150.0000')
        ->and($result->maximum_discount)->toBe('100.0000')
        ->and($result->usage_limit)->toBe(200)
        ->and($result->usage_limit_per_customer)->toBe(3)
        ->and($result->is_active)->toBeFalse()
        ->and($result->starts_at)->not->toBeNull()
        ->and($result->expires_at)->not->toBeNull();
});

test('normalizes code to uppercase during update', function () {
    $coupon = Coupon::factory()->create(['code' => 'ORIGINAL']);

    $updateData = [
        'code' => 'lowercase-update',
        'type' => CouponType::Flat,
        'value' => 15.00,
    ];

    $action = new UpdateCouponAction();
    $result = $action->handle($coupon, UpdateCouponInput::fromArray($updateData));

    expect($result->code)->toBe('LOWERCASE-UPDATE');

    assertDatabaseHas('coupons', [
        'id' => $coupon->id,
        'code' => 'LOWERCASE-UPDATE',
    ]);
});

test('updates coupon type from flat to percentage', function () {
    $coupon = Coupon::factory()->create([
        'type' => CouponType::Flat,
        'value' => 20.00,
    ]);

    $updateData = [
        'code' => $coupon->code,
        'type' => CouponType::Percentage,
        'value' => 15.00,
        'maximum_discount' => 50.00,
    ];

    $action = new UpdateCouponAction();
    $result = $action->handle($coupon, UpdateCouponInput::fromArray($updateData));

    expect($result->type)->toBe(CouponType::Percentage)
        ->and($result->value)->toBe('15.0000')
        ->and($result->maximum_discount)->toBe('50.0000');
});

test('updates coupon type from percentage to flat', function () {
    $coupon = Coupon::factory()->create([
        'type' => CouponType::Percentage,
        'value' => 25.00,
        'maximum_discount' => 100.00,
    ]);

    $updateData = [
        'code' => $coupon->code,
        'type' => CouponType::Flat,
        'value' => 30.00,
        'maximum_discount' => null,
    ];

    $action = new UpdateCouponAction();
    $result = $action->handle($coupon, UpdateCouponInput::fromArray($updateData));

    expect($result->type)->toBe(CouponType::Flat)
        ->and($result->value)->toBe('30.0000')
        ->and($result->maximum_discount)->toBeNull();
});

test('clears optional fields when set to null', function () {
    $coupon = Coupon::factory()->create([
        'min_order_value' => 100.00,
        'maximum_discount' => 50.00,
        'usage_limit' => 100,
        'usage_limit_per_customer' => 2,
        'starts_at' => now()->addDay(),
        'expires_at' => now()->addMonth(),
    ]);

    $updateData = [
        'code' => $coupon->code,
        'type' => $coupon->type,
        'value' => $coupon->value,
        'min_order_value' => null,
        'maximum_discount' => null,
        'usage_limit' => null,
        'usage_limit_per_customer' => null,
        'starts_at' => null,
        'expires_at' => null,
    ];

    $action = new UpdateCouponAction();
    $result = $action->handle($coupon, UpdateCouponInput::fromArray($updateData));

    expect($result->min_order_value)->toBeNull()
        ->and($result->maximum_discount)->toBeNull()
        ->and($result->usage_limit)->toBeNull()
        ->and($result->usage_limit_per_customer)->toBeNull()
        ->and($result->starts_at)->toBeNull()
        ->and($result->expires_at)->toBeNull();
});

test('updates usage limits', function () {
    $coupon = Coupon::factory()->create([
        'usage_limit' => 50,
        'usage_limit_per_customer' => 1,
    ]);

    $updateData = [
        'code' => $coupon->code,
        'type' => $coupon->type,
        'value' => $coupon->value,
        'usage_limit' => 200,
        'usage_limit_per_customer' => 5,
    ];

    $action = new UpdateCouponAction();
    $result = $action->handle($coupon, UpdateCouponInput::fromArray($updateData));

    expect($result->usage_limit)->toBe(200)
        ->and($result->usage_limit_per_customer)->toBe(5);
});

test('updates date constraints', function () {
    $coupon = Coupon::factory()->create([
        'starts_at' => now()->subDay(),
        'expires_at' => now()->addDay(),
    ]);

    $newStartDate = now()->addWeek();
    $newEndDate = now()->addMonths(2);

    $updateData = [
        'code' => $coupon->code,
        'type' => $coupon->type,
        'value' => $coupon->value,
        'starts_at' => $newStartDate,
        'expires_at' => $newEndDate,
    ];

    $action = new UpdateCouponAction();
    $result = $action->handle($coupon, UpdateCouponInput::fromArray($updateData));

    expect($result->starts_at->format('Y-m-d H:i:s'))->toBe($newStartDate->format('Y-m-d H:i:s'))
        ->and($result->expires_at->format('Y-m-d H:i:s'))->toBe($newEndDate->format('Y-m-d H:i:s'));
});

test('toggles active status', function () {
    $activeCoupon = Coupon::factory()->active()->create();
    $inactiveCoupon = Coupon::factory()->inactive()->create();

    $action = new UpdateCouponAction();

    // Deactivate active coupon
    $result1 = $action->handle($activeCoupon, UpdateCouponInput::fromArray([
        'code' => $activeCoupon->code,
        'type' => $activeCoupon->type,
        'value' => $activeCoupon->value,
        'is_active' => false,
    ]));

    // Activate inactive coupon
    $result2 = $action->handle($inactiveCoupon, UpdateCouponInput::fromArray([
        'code' => $inactiveCoupon->code,
        'type' => $inactiveCoupon->type,
        'value' => $inactiveCoupon->value,
        'is_active' => true,
    ]));

    expect($result1->is_active)->toBeFalse()
        ->and($result2->is_active)->toBeTrue();
});

test('preserves is_active when not provided in partial update', function () {
    $coupon = Coupon::factory()->inactive()->create();

    $updateData = [
        'code' => $coupon->code,
        'type' => $coupon->type,
        'value' => $coupon->value,
        // is_active not provided — must be preserved (not reset to true)
    ];

    $action = new UpdateCouponAction();
    $result = $action->handle($coupon, UpdateCouponInput::fromArray($updateData));

    expect($result->is_active)->toBeFalse();
});

test('preserves usage_limit and expires_at when not provided in partial update', function () {
    $coupon = Coupon::factory()->create([
        'usage_limit' => 100,
        'expires_at' => now()->addDays(30),
    ]);
    $originalExpiry = $coupon->expires_at;

    $action = new UpdateCouponAction();
    $action->handle($coupon, UpdateCouponInput::fromArray([
        'code' => 'NEWCODE',
    ]));

    expect($coupon->fresh()->usage_limit)->toBe(100)
        ->and($coupon->fresh()->expires_at?->toDateTimeString())->toBe($originalExpiry->toDateTimeString())
        ->and($coupon->fresh()->code)->toBe('NEWCODE');
});

test('preserves used_count during update', function () {
    $coupon = Coupon::factory()->create(['used_count' => 25]);

    $updateData = [
        'code' => 'UPDATED',
        'type' => CouponType::Percentage,
        'value' => 20.00,
    ];

    $action = new UpdateCouponAction();
    $result = $action->handle($coupon, UpdateCouponInput::fromArray($updateData));

    expect($result->used_count)->toBe(25);
});

test('updates decimal values correctly', function () {
    $coupon = Coupon::factory()->create([
        'value' => 10.00,
        'min_order_value' => 50.00,
    ]);

    $updateData = [
        'code' => $coupon->code,
        'type' => $coupon->type,
        'value' => 15.99,
        'min_order_value' => 75.50,
        'maximum_discount' => 25.25,
    ];

    $action = new UpdateCouponAction();
    $result = $action->handle($coupon, UpdateCouponInput::fromArray($updateData));

    expect($result->value)->toBe('15.9900')
        ->and($result->min_order_value)->toBe('75.5000')
        ->and($result->maximum_discount)->toBe('25.2500');
});

test('returns the same coupon instance', function () {
    $coupon = Coupon::factory()->create();

    $updateData = [
        'code' => 'SAME',
        'type' => $coupon->type,
        'value' => $coupon->value,
    ];

    $action = new UpdateCouponAction();
    $result = $action->handle($coupon, UpdateCouponInput::fromArray($updateData));

    expect($result->is($coupon))->toBeTrue()
        ->and($result->id)->toBe($coupon->id);
});

test('normalizes mixed case code with special characters', function () {
    $coupon = Coupon::factory()->create(['code' => 'OLD']);

    $updateData = [
        'code' => 'new-Code_123',
        'type' => $coupon->type,
        'value' => $coupon->value,
    ];

    $action = new UpdateCouponAction();
    $result = $action->handle($coupon, UpdateCouponInput::fromArray($updateData));

    expect($result->code)->toBe('NEW-CODE_123');
});
