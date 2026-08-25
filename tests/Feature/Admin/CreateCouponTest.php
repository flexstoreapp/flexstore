<?php

declare(strict_types=1);

use App\Actions\StoreCouponAction;
use App\Enums\CouponType;
use App\Enums\Permission;
use App\Enums\Role as RoleEnum;
use App\Http\Controllers\Admin\CouponController;
use App\Http\Requests\Admin\StoreCouponRequest;
use App\Models\Coupon;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Laravel\get;
use function Pest\Laravel\post;

covers(CouponController::class, StoreCouponRequest::class, StoreCouponAction::class);

uses()->group('coupon');

test('displays coupon creation form', function () {
    $response = actingAsSuperAdmin()->get(route('admin.coupons.create'));

    $response->assertOk()
        ->assertInertia(
            fn ($page) => $page
                ->component('admin/coupons/create')
        );
});

test('creates new coupon successfully', function () {
    $couponData = [
        'code' => 'SAVE20',
        'type' => CouponType::Percentage->value,
        'value' => '20',
        'min_order_value' => '100.0000',
        'maximum_discount' => '50.0000',
        'usage_limit' => 100,
        'usage_limit_per_customer' => 1,
        'is_active' => true,
        'starts_at' => now()->addDay()->format('Y-m-d H:i:s'),
        'expires_at' => now()->addMonth()->format('Y-m-d H:i:s'),
    ];

    $response = actingAsSuperAdmin()->post(route('admin.coupons.store'), $couponData);

    $response->assertSessionHasNoErrors();

    $coupon = Coupon::where('code', 'SAVE20')->first();
    $response->assertRedirect(route('admin.coupons.edit', $coupon));

    assertDatabaseHas('coupons', [
        'code' => 'SAVE20',
        'type' => CouponType::Percentage->value,
        'value' => '20.0000',
        'min_order_value' => '100.0000',
        'maximum_discount' => '50.0000',
        'usage_limit' => 100,
        'usage_limit_per_customer' => 1,
        'is_active' => true,
    ]);
});

test('creates flat discount coupon successfully', function () {
    $couponData = [
        'code' => 'FLAT10',
        'type' => CouponType::Flat->value,
        'value' => '10.0000',
        'is_active' => true,
    ];

    $response = actingAsSuperAdmin()->post(route('admin.coupons.store'), $couponData);

    $response->assertSessionHasNoErrors();

    assertDatabaseHas('coupons', [
        'code' => 'FLAT10',
        'type' => CouponType::Flat->value,
        'value' => '10.0000',
        'is_active' => true,
    ]);
});

test('validates required fields', function () {
    $response = actingAsSuperAdmin()->post(route('admin.coupons.store'), []);

    $response->assertRedirectBack()
        ->assertInvalid(['code', 'type', 'value']);
});

test('validates code uniqueness', function () {
    Coupon::factory()->create(['code' => 'EXISTING']);

    $couponData = [
        'code' => 'EXISTING',
        'type' => CouponType::Percentage->value,
        'value' => '20',
    ];

    $response = actingAsSuperAdmin()->post(route('admin.coupons.store'), $couponData);

    $response->assertRedirectBack()
        ->assertInvalid('code');
});

test('validates code uniqueness regardless of case', function () {
    Coupon::factory()->create(['code' => 'EXISTING']);

    $response = actingAsSuperAdmin()->post(route('admin.coupons.store'), [
        'code' => 'existing',
        'type' => CouponType::Percentage->value,
        'value' => '20',
    ]);

    $response->assertRedirectBack()
        ->assertInvalid('code');
});

test('stores the code in uppercase', function () {
    $response = actingAsSuperAdmin()->post(route('admin.coupons.store'), [
        'code' => 'summer24',
        'type' => CouponType::Percentage->value,
        'value' => '20',
    ]);

    $response->assertRedirect();

    assertDatabaseHas('coupons', ['code' => 'SUMMER24']);
});

test('validates code format', function () {
    $couponData = [
        'code' => 'invalid code!',
        'type' => CouponType::Percentage->value,
        'value' => '20',
    ];

    $response = actingAsSuperAdmin()->post(route('admin.coupons.store'), $couponData);

    $response->assertRedirectBack()
        ->assertInvalid('code');
});

test('validates code length', function () {
    $couponData = [
        'code' => str_repeat('A', 51), // Exceeds max:50
        'type' => CouponType::Percentage->value,
        'value' => '20',
    ];

    $response = actingAsSuperAdmin()->post(route('admin.coupons.store'), $couponData);

    $response->assertRedirectBack()
        ->assertInvalid('code');
});

test('validates coupon type', function () {
    $couponData = [
        'code' => 'VALID',
        'type' => 'invalid_type',
        'value' => '20',
    ];

    $response = actingAsSuperAdmin()->post(route('admin.coupons.store'), $couponData);

    $response->assertRedirectBack()
        ->assertInvalid('type');
});

test('validates percentage value maximum', function () {
    $couponData = [
        'code' => 'OVER100',
        'type' => CouponType::Percentage->value,
        'value' => '150', // Over 100%
    ];

    $response = actingAsSuperAdmin()->post(route('admin.coupons.store'), $couponData);

    $response->assertRedirectBack()
        ->assertInvalid('value');
});

test('validates value is numeric and positive', function () {
    $couponData = [
        'code' => 'NEGATIVE',
        'type' => CouponType::Flat->value,
        'value' => '-10.00',
    ];

    $response = actingAsSuperAdmin()->post(route('admin.coupons.store'), $couponData);

    $response->assertRedirectBack()
        ->assertInvalid('value');
});

test('validates min_order_value is positive when provided', function () {
    $couponData = [
        'code' => 'MINORDER',
        'type' => CouponType::Flat->value,
        'value' => '10.0000',
        'min_order_value' => '-50.00',
    ];

    $response = actingAsSuperAdmin()->post(route('admin.coupons.store'), $couponData);

    $response->assertRedirectBack()
        ->assertInvalid('min_order_value');
});

test('validates maximum_discount is positive when provided', function () {
    $couponData = [
        'code' => 'MAXDISCOUNT',
        'type' => CouponType::Percentage->value,
        'value' => '20.0000',
        'maximum_discount' => '-10.00',
    ];

    $response = actingAsSuperAdmin()->post(route('admin.coupons.store'), $couponData);

    $response->assertRedirectBack()
        ->assertInvalid('maximum_discount');
});

test('validates usage_limit is positive integer when provided', function () {
    $couponData = [
        'code' => 'USAGE',
        'type' => CouponType::Flat->value,
        'value' => '10.0000',
        'usage_limit' => 0,
    ];

    $response = actingAsSuperAdmin()->post(route('admin.coupons.store'), $couponData);

    $response->assertRedirectBack()
        ->assertInvalid('usage_limit');
});

test('validates usage_limit_per_customer is positive integer when provided', function () {
    $couponData = [
        'code' => 'PERCUSTOMER',
        'type' => CouponType::Flat->value,
        'value' => '10.0000',
        'usage_limit_per_customer' => 0,
    ];

    $response = actingAsSuperAdmin()->post(route('admin.coupons.store'), $couponData);

    $response->assertRedirectBack()
        ->assertInvalid('usage_limit_per_customer');
});

test('validates expires_at is after starts_at', function () {
    $couponData = [
        'code' => 'BADEXPIRY',
        'type' => CouponType::Flat->value,
        'value' => '10.0000',
        'starts_at' => now()->addWeek()->format('Y-m-d H:i:s'),
        'expires_at' => now()->addDay()->format('Y-m-d H:i:s'), // Before starts_at
    ];

    $response = actingAsSuperAdmin()->post(route('admin.coupons.store'), $couponData);

    $response->assertRedirectBack()
        ->assertInvalid('expires_at');
});

test('requires authentication', function () {
    $response = get(route('admin.coupons.create'));

    $response->assertRedirect(route('admin.login'));

    $response = post(route('admin.coupons.store'), [
        'code' => 'TESTCOUPON',
        'type' => CouponType::Flat->value,
        'value' => '10.0000',
        'is_active' => true,
    ]);

    $response->assertRedirect(route('admin.login'));
});

test('requires coupons.create permission', function () {
    $role = Role::query()->where(['name' => RoleEnum::Admin])->firstOrFail();

    $response = actingAsAdmin()->get(route('admin.coupons.create'));

    $response->assertOk();

    $response = actingAsAdmin()->post(route('admin.coupons.store'), [
        'code' => 'TESTCOUPON',
        'type' => CouponType::Flat->value,
        'value' => '10.0000',
        'is_active' => true,
    ]);

    $response->assertSessionHasNoErrors();

    assertDatabaseHas('coupons', [
        'code' => 'TESTCOUPON',
    ]);

    $role->revokePermissionTo(Permission::CouponsManage);

    $response = actingAsAdmin()->get(route('admin.coupons.create'));

    $response->assertForbidden();

    $response = actingAsAdmin()->post(route('admin.coupons.store'), [
        'code' => 'NOPERM',
        'type' => CouponType::Flat->value,
        'value' => '10.0000',
        'is_active' => true,
    ]);

    $response->assertForbidden();

    assertDatabaseMissing('coupons', [
        'code' => 'NOPERM',
    ]);
});
