<?php

declare(strict_types=1);

use App\Enums\CouponType;
use App\Enums\Permission;
use App\Http\Controllers\Api\V1\Admin\BulkCouponController;
use App\Http\Controllers\Api\V1\Admin\CouponController;
use App\Http\Controllers\Api\V1\Admin\CouponValidationController;
use App\Http\Requests\Api\V1\Admin\IndexCouponRequest;
use App\Http\Requests\Api\V1\Admin\UpdateCouponRequest;
use App\Http\Resources\Api\V1\Admin\CouponResource;
use App\Http\Resources\Api\V1\Admin\CouponValidationResource;
use App\Models\Coupon;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Laravel\deleteJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\patchJson;
use function Pest\Laravel\postJson;

covers(
    CouponController::class,
    BulkCouponController::class,
    CouponValidationController::class,
    IndexCouponRequest::class,
    UpdateCouponRequest::class,
    CouponResource::class,
    CouponValidationResource::class,
);

uses()->group('api', 'admin-api');

test('the coupon index is paginated and searchable', function (): void {
    actingAsApiAdmin(userWithPermissions([Permission::CouponsView]));

    Coupon::factory()->valid()->create(['code' => 'SUMMER10', 'type' => CouponType::Percentage, 'value' => 10]);
    Coupon::factory()->valid()->create(['code' => 'WINTER20', 'type' => CouponType::Percentage, 'value' => 20]);

    getJson('/api/v1/admin/coupons?per_page=1')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('meta.per_page', 1)
        ->assertJsonPath('meta.total', 2)
        ->assertJsonStructure([
            'data' => [['id', 'code', 'type', 'value', 'used_count', 'is_active', 'starts_at', 'expires_at']],
        ]);

    getJson('/api/v1/admin/coupons?query=WINTER')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.code', 'WINTER20');
});

test('the coupon index filters by active state', function (): void {
    actingAsApiAdmin(userWithPermissions([Permission::CouponsView]));

    $active = Coupon::factory()->valid()->create(['code' => 'ONNOW']);
    Coupon::factory()->inactive()->create(['code' => 'OFFNOW']);

    getJson('/api/v1/admin/coupons?is_active=1')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $active->id);
});

test('a staff member without the view permission cannot list coupons', function (): void {
    actingAsApiAdmin(userWithPermissions([Permission::DashboardView]));

    getJson('/api/v1/admin/coupons')->assertForbidden();
});

test('a coupon is created', function (): void {
    actingAsApiAdmin(userWithPermissions([Permission::CouponsManage]));

    postJson('/api/v1/admin/coupons', [
        'code' => 'welcome15',
        'type' => CouponType::Percentage->value,
        'value' => 15,
        'usage_limit' => 100,
        'is_active' => true,
    ])
        ->assertCreated()
        ->assertJsonPath('code', 'WELCOME15')
        ->assertJsonPath('type', 'percentage')
        ->assertJsonPath('used_count', 0);

    assertDatabaseHas('coupons', ['code' => 'WELCOME15']);
});

test('creating a coupon validates the payload', function (): void {
    actingAsApiAdmin(userWithPermissions([Permission::CouponsManage]));

    postJson('/api/v1/admin/coupons', ['type' => 'percentage', 'value' => 150])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['code', 'value']);
});

test('a single coupon is shown', function (): void {
    actingAsApiAdmin(userWithPermissions([Permission::CouponsView]));

    $coupon = Coupon::factory()->valid()->create(['code' => 'SHOWME']);

    getJson('/api/v1/admin/coupons/' . $coupon->id)
        ->assertOk()
        ->assertJsonPath('id', $coupon->id)
        ->assertJsonPath('code', 'SHOWME');
});

test('a coupon is updated with patch semantics', function (): void {
    actingAsApiAdmin(userWithPermissions([Permission::CouponsManage]));

    $coupon = Coupon::factory()->valid()->create(['code' => 'KEEPME', 'type' => CouponType::Flat, 'value' => 25]);

    patchJson('/api/v1/admin/coupons/' . $coupon->id, ['is_active' => false])
        ->assertOk()
        ->assertJsonPath('code', 'KEEPME')
        ->assertJsonPath('is_active', false);

    expect($coupon->refresh()->code)->toBe('KEEPME')
        ->and($coupon->is_active)->toBeFalse();
});

test('updating a coupon rejects a duplicate code', function (): void {
    actingAsApiAdmin(userWithPermissions([Permission::CouponsManage]));

    Coupon::factory()->valid()->create(['code' => 'TAKEN']);
    $coupon = Coupon::factory()->valid()->create(['code' => 'MINE']);

    patchJson('/api/v1/admin/coupons/' . $coupon->id, ['code' => 'taken'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('code');
});

test('a coupon is deleted', function (): void {
    actingAsApiAdmin(userWithPermissions([Permission::CouponsDelete]));

    $coupon = Coupon::factory()->valid()->create();

    deleteJson('/api/v1/admin/coupons/' . $coupon->id)->assertNoContent();

    assertDatabaseMissing('coupons', ['id' => $coupon->id]);
});

test('coupons are bulk deleted', function (): void {
    actingAsApiAdmin(userWithPermissions([Permission::CouponsDelete]));

    $coupons = Coupon::factory(2)->valid()->create();

    deleteJson('/api/v1/admin/coupons/bulk', ['ids' => $coupons->pluck('id')->all()])
        ->assertNoContent();

    foreach ($coupons as $coupon) {
        assertDatabaseMissing('coupons', ['id' => $coupon->id]);
    }
});

test('bulk delete rejects unknown coupon ids', function (): void {
    actingAsApiAdmin(userWithPermissions([Permission::CouponsDelete]));

    deleteJson('/api/v1/admin/coupons/bulk', ['ids' => [99999]])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('ids');
});

test('a coupon code is validated against an order subtotal', function (): void {
    actingAsApiAdmin(userWithPermissions([Permission::OrdersManage]));

    $coupon = Coupon::factory()->valid()->create([
        'code' => 'TENOFF',
        'type' => CouponType::Percentage,
        'value' => 10,
    ]);

    postJson('/api/v1/admin/coupons/validate', [
        'coupon_code' => 'tenoff',
        'subtotal' => '200.00',
        'customer_email' => 'buyer@example.com',
    ])
        ->assertOk()
        ->assertJsonPath('coupon.id', $coupon->id)
        ->assertJsonPath('coupon.code', 'TENOFF')
        ->assertJsonPath('discount', '20.0000');
});

test('validating an unknown coupon code fails', function (): void {
    actingAsApiAdmin(userWithPermissions([Permission::OrdersManage]));

    postJson('/api/v1/admin/coupons/validate', [
        'coupon_code' => 'NOPE',
        'subtotal' => '50.00',
        'customer_email' => 'buyer@example.com',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('coupon_code');
});

test('a read-only staff member can open a coupon', function (): void {
    actingAsApiAdmin(userWithPermissions([Permission::CouponsView]));

    getJson('/api/v1/admin/coupons/' . Coupon::factory()->create()->id)->assertOk();
});
