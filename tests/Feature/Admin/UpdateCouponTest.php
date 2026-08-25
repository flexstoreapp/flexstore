<?php

declare(strict_types=1);

use App\Actions\UpdateCouponAction;
use App\Enums\CouponType;
use App\Enums\Permission;
use App\Enums\Role as RoleEnum;
use App\Http\Controllers\Admin\CouponController;
use App\Http\Requests\Admin\UpdateCouponRequest;
use App\Models\Coupon;
use App\Models\User;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Laravel\get;
use function Pest\Laravel\patch;

covers(CouponController::class, UpdateCouponRequest::class, UpdateCouponAction::class);

uses()->group('coupon');

test('displays coupon edit form', function () {
    $coupon = Coupon::factory()->create([
        'code' => 'EDIT20',
        'type' => CouponType::Percentage,
        'value' => '20',
    ]);

    $response = actingAsSuperAdmin()->get(route('admin.coupons.edit', $coupon));

    $response->assertOk()
        ->assertInertia(
            fn ($page) => $page
                ->component('admin/coupons/edit')
                ->has('coupon')
                ->where('coupon.id', $coupon->id)
                ->where('coupon.code', 'EDIT20')
        );
});

test('updates coupon successfully', function () {
    $coupon = Coupon::factory()->create([
        'code' => 'ORIGINAL',
        'type' => CouponType::Flat,
        'value' => '10.0000',
        'min_order_value' => '50.0000',
        'is_active' => false,
    ]);

    $updateData = [
        'code' => 'UPDATED',
        'type' => CouponType::Percentage->value,
        'value' => '25',
        'min_order_value' => '100.0000',
        'maximum_discount' => '75.0000',
        'usage_limit' => 200,
        'usage_limit_per_customer' => 2,
        'is_active' => true,
        'starts_at' => now()->addDay()->format('Y-m-d H:i:s'),
        'expires_at' => now()->addMonth()->format('Y-m-d H:i:s'),
    ];

    $response = actingAsSuperAdmin()->patch(route('admin.coupons.update', $coupon), $updateData);

    $response->assertRedirectBack()
        ->assertSessionHasNoErrors();

    $coupon->refresh();
    expect($coupon->code)->toBe('UPDATED')
        ->and($coupon->type)->toBe(CouponType::Percentage)
        ->and($coupon->value)->toBe('25.0000')
        ->and($coupon->min_order_value)->toBe('100.0000')
        ->and($coupon->maximum_discount)->toBe('75.0000')
        ->and($coupon->usage_limit)->toBe(200)
        ->and($coupon->usage_limit_per_customer)->toBe(2)
        ->and($coupon->is_active)->toBeTrue();
});

test('updates coupon with minimal data', function () {
    $coupon = Coupon::factory()->create([
        'code' => 'MINIMAL',
        'type' => CouponType::Flat,
        'value' => '5',
    ]);

    $updateData = [
        'code' => 'SIMPLE',
        'type' => CouponType::Percentage->value,
        'value' => '15',
        'is_active' => true,
    ];

    $response = actingAsSuperAdmin()->patch(route('admin.coupons.update', $coupon), $updateData);

    $response->assertRedirectBack()
        ->assertSessionHasNoErrors();

    $coupon->refresh();
    expect($coupon->code)->toBe('SIMPLE')
        ->and($coupon->type)->toBe(CouponType::Percentage)
        ->and($coupon->value)->toBe('15.0000')
        ->and($coupon->is_active)->toBeTrue();
});

test('validates required fields', function () {
    $coupon = Coupon::factory()->create();

    $response = actingAsSuperAdmin()->patch(route('admin.coupons.update', $coupon), [
        'code' => '',
        'type' => '',
        'value' => '',
    ]);

    $response->assertRedirectBack()
        ->assertInvalid(['code', 'type', 'value']);
});

test('validates code uniqueness except current coupon', function () {
    $coupon1 = Coupon::factory()->create(['code' => 'COUPON1']);
    $coupon2 = Coupon::factory()->create(['code' => 'COUPON2']);

    // Should fail - code belongs to another coupon
    $response = actingAsSuperAdmin()->patch(route('admin.coupons.update', $coupon1), [
        'code' => 'COUPON2',
        'type' => CouponType::Flat->value,
        'value' => '10.0000',
    ]);

    $response->assertRedirectBack()
        ->assertInvalid('code');

    // Should succeed - same code for same coupon
    $response = actingAsSuperAdmin()->patch(route('admin.coupons.update', $coupon1), [
        'code' => 'COUPON1',
        'type' => CouponType::Flat->value,
        'value' => '15.0000',
    ]);

    $response->assertRedirectBack()
        ->assertSessionHasNoErrors();
});

test('validates code uniqueness regardless of case', function () {
    $coupon = Coupon::factory()->create(['code' => 'COUPON1']);
    Coupon::factory()->create(['code' => 'COUPON2']);

    $response = actingAsSuperAdmin()->patch(route('admin.coupons.update', $coupon), [
        'code' => 'coupon2',
        'type' => CouponType::Flat->value,
        'value' => '10.0000',
    ]);

    $response->assertRedirectBack()
        ->assertInvalid('code');
});

test('validates code format', function () {
    $coupon = Coupon::factory()->create();

    $updateData = [
        'code' => 'invalid code!', // Special characters are not allowed
        'type' => CouponType::Flat->value,
        'value' => '10.0000',
    ];

    $response = actingAsSuperAdmin()->patch(route('admin.coupons.update', $coupon), $updateData);

    $response->assertRedirectBack()
        ->assertInvalid('code');
});

test('validates code length', function () {
    $coupon = Coupon::factory()->create();

    $updateData = [
        'code' => str_repeat('A', 51), // Exceeds max:50
        'type' => CouponType::Flat->value,
        'value' => '10.0000',
    ];

    $response = actingAsSuperAdmin()->patch(route('admin.coupons.update', $coupon), $updateData);

    $response->assertRedirectBack()
        ->assertInvalid('code');
});

test('validates coupon type', function () {
    $coupon = Coupon::factory()->create();

    $updateData = [
        'code' => 'VALID',
        'type' => 'invalid_type',
        'value' => '10.0000',
    ];

    $response = actingAsSuperAdmin()->patch(route('admin.coupons.update', $coupon), $updateData);

    $response->assertRedirectBack()
        ->assertInvalid('type');
});

test('validates percentage value maximum', function () {
    $coupon = Coupon::factory()->create();

    $updateData = [
        'code' => 'OVER100',
        'type' => CouponType::Percentage->value,
        'value' => '150.0000', // Over 100%
    ];

    $response = actingAsSuperAdmin()->patch(route('admin.coupons.update', $coupon), $updateData);

    $response->assertRedirectBack()
        ->assertInvalid('value');
});

test('validates value is numeric and positive', function () {
    $coupon = Coupon::factory()->create();

    $updateData = [
        'code' => 'NEGATIVE',
        'type' => CouponType::Flat->value,
        'value' => '-10.00',
    ];

    $response = actingAsSuperAdmin()->patch(route('admin.coupons.update', $coupon), $updateData);

    $response->assertRedirectBack()
        ->assertInvalid('value');
});

test('validates min_order_value is positive when provided', function () {
    $coupon = Coupon::factory()->create();

    $updateData = [
        'code' => 'MINORDER',
        'type' => CouponType::Flat->value,
        'value' => '10.0000',
        'min_order_value' => '-50.00',
    ];

    $response = actingAsSuperAdmin()->patch(route('admin.coupons.update', $coupon), $updateData);

    $response->assertRedirectBack()
        ->assertInvalid('min_order_value');
});

test('validates maximum_discount is positive when provided', function () {
    $coupon = Coupon::factory()->create();

    $updateData = [
        'code' => 'MAXDISCOUNT',
        'type' => CouponType::Percentage->value,
        'value' => '20.0000',
        'maximum_discount' => '-10.00',
    ];

    $response = actingAsSuperAdmin()->patch(route('admin.coupons.update', $coupon), $updateData);

    $response->assertRedirectBack()
        ->assertInvalid('maximum_discount');
});

test('validates usage_limit is positive integer when provided', function () {
    $coupon = Coupon::factory()->create();

    $updateData = [
        'code' => 'USAGE',
        'type' => CouponType::Flat->value,
        'value' => '10.0000',
        'usage_limit' => 0,
    ];

    $response = actingAsSuperAdmin()->patch(route('admin.coupons.update', $coupon), $updateData);

    $response->assertRedirectBack()
        ->assertInvalid('usage_limit');
});

test('validates usage_limit_per_customer is positive integer when provided', function () {
    $coupon = Coupon::factory()->create();

    $updateData = [
        'code' => 'PERCUSTOMER',
        'type' => CouponType::Flat->value,
        'value' => '10.0000',
        'usage_limit_per_customer' => 0,
    ];

    $response = actingAsSuperAdmin()->patch(route('admin.coupons.update', $coupon), $updateData);

    $response->assertRedirectBack()
        ->assertInvalid('usage_limit_per_customer');
});

test('validates expires_at is after starts_at', function () {
    $coupon = Coupon::factory()->create();

    $updateData = [
        'code' => 'BADEXPIRY',
        'type' => CouponType::Flat->value,
        'value' => '10.0000',
        'starts_at' => now()->addWeek()->format('Y-m-d H:i:s'),
        'expires_at' => now()->addDay()->format('Y-m-d H:i:s'), // Before starts_at
    ];

    $response = actingAsSuperAdmin()->patch(route('admin.coupons.update', $coupon), $updateData);

    $response->assertRedirectBack()
        ->assertInvalid('expires_at');
});

test('requires authentication', function () {
    $brand = Coupon::factory()->create();

    $response = get(route('admin.coupons.edit', $brand));

    $response->assertRedirect(route('admin.login'));

    $response = patch(route('admin.coupons.update', $brand), [
        'code' => 'NOPERM',
        'type' => CouponType::Flat->value,
        'value' => '10.0000',
    ]);

    $response->assertRedirect(route('admin.login'));
});

test('requires coupons.update permission', function () {
    $adminRole = Role::where('name', RoleEnum::Admin->value)->first();
    $adminRole->givePermissionTo(Permission::CouponsManage->value);

    $admin = User::factory()->create();
    $admin->assignRole($adminRole);

    $coupon = Coupon::factory()->create([
        'code' => 'ORIGINAL',
        'type' => CouponType::Flat,
        'value' => '10.0000',
    ]);

    $response = actingAs($admin)->get(route('admin.coupons.edit', $coupon));

    $response->assertOk();

    $response = actingAs($admin)->patch(route('admin.coupons.update', $coupon), [
        'code' => 'UPDATED',
        'type' => CouponType::Percentage->value,
        'value' => '25.0000',
    ]);

    $response->assertRedirectBack()
        ->assertSessionHasNoErrors();

    assertDatabaseHas('coupons', [
        'id' => $coupon->id,
        'code' => 'UPDATED',
        'type' => CouponType::Percentage->value,
        'value' => '25.0000',
    ]);

    $adminRole->revokePermissionTo(Permission::CouponsManage->value);

    $response = actingAs($admin)->get(route('admin.coupons.edit', $coupon));

    $response->assertForbidden();

    $response = actingAs($admin)->patch(route('admin.coupons.update', $coupon), [
        'code' => 'FORBIDDEN',
        'type' => CouponType::Flat->value,
        'value' => '50.0000',
    ]);

    $response->assertForbidden();

    assertDatabaseMissing('coupons', [
        'id' => $coupon->id,
        'code' => 'FORBIDDEN',
    ]);
});
