<?php

declare(strict_types=1);

use App\Enums\Permission;
use App\Enums\Role as RoleEnum;
use App\Http\Controllers\Admin\CouponController;
use App\Http\Requests\Admin\IndexCouponRequest;
use App\Models\Coupon;
use App\Queries\CouponListQuery;
use Inertia\Testing\AssertableInertia;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\get;

covers(CouponController::class, CouponListQuery::class, IndexCouponRequest::class);

uses()->group('coupon');

test('displays coupon list page', function () {
    Coupon::factory(3)->create();

    $response = actingAsSuperAdmin()->get(route('admin.coupons.index'));

    $response->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('admin/coupons/list')
                ->has('coupons.data', 3)
                ->where('filters.query', null)
                ->where('filters.is_active', null)
                ->where('filters.sort', 'created_at')
                ->where('filters.direction', 'desc')
        );
});

test('sorts coupons by code ascending', function () {
    $couponC = Coupon::factory()->create(['code' => 'COUPON_C']);
    $couponA = Coupon::factory()->create(['code' => 'COUPON_A']);
    $couponB = Coupon::factory()->create(['code' => 'COUPON_B']);

    $response = actingAsSuperAdmin()->get(route('admin.coupons.index', ['sort' => 'code', 'direction' => 'asc']));

    $response->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('admin/coupons/list')
                ->where('coupons.data.0.id', $couponA->id)
                ->where('coupons.data.0.code', 'COUPON_A')
                ->where('coupons.data.1.id', $couponB->id)
                ->where('coupons.data.1.code', 'COUPON_B')
                ->where('coupons.data.2.id', $couponC->id)
                ->where('coupons.data.2.code', 'COUPON_C')
        );
});

test('sorts coupons by code descending', function () {
    $couponA = Coupon::factory()->create(['code' => 'COUPON_A']);
    $couponC = Coupon::factory()->create(['code' => 'COUPON_C']);
    $couponB = Coupon::factory()->create(['code' => 'COUPON_B']);

    $response = actingAsSuperAdmin()->get(route('admin.coupons.index', ['sort' => 'code', 'direction' => 'desc']));

    $response->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('admin/coupons/list')
                ->where('coupons.data.0.id', $couponC->id)
                ->where('coupons.data.0.code', 'COUPON_C')
                ->where('coupons.data.1.id', $couponB->id)
                ->where('coupons.data.1.code', 'COUPON_B')
                ->where('coupons.data.2.id', $couponA->id)
                ->where('coupons.data.2.code', 'COUPON_A')
        );
});

test('sorts coupons by value ascending', function () {
    $couponLow = Coupon::factory()->create(['value' => '5.0000']);
    $couponHigh = Coupon::factory()->create(['value' => '50.0000']);
    $couponMedium = Coupon::factory()->create(['value' => '25.0000']);

    $response = actingAsSuperAdmin()->get(route('admin.coupons.index', ['sort' => 'value', 'direction' => 'asc']));

    $response->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('admin/coupons/list')
                ->where('coupons.data.0.id', $couponLow->id)
                ->where('coupons.data.0.value', '5.0000')
                ->where('coupons.data.1.id', $couponMedium->id)
                ->where('coupons.data.1.value', '25.0000')
                ->where('coupons.data.2.id', $couponHigh->id)
                ->where('coupons.data.2.value', '50.0000')
        );
});

test('sorts coupons by expires_at ascending', function () {
    // Create coupons with specific dates to test sorting
    $noExpiry = Coupon::factory()->create(['expires_at' => null, 'code' => 'NO_EXPIRY_TEST']);
    $expiresSoon = Coupon::factory()->create(['expires_at' => now()->addDay(), 'code' => 'EXPIRES_SOON_TEST']);
    $expiresLater = Coupon::factory()->create(['expires_at' => now()->addMonth(), 'code' => 'EXPIRES_LATER_TEST']);

    $response = actingAsSuperAdmin()->get(route('admin.coupons.index', ['sort' => 'expires_at', 'direction' => 'asc']));

    $response->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('admin/coupons/list')
                ->has('coupons.data')
        );

    // Find the positions of our test coupons in the response
    $data = $response->original->getData()['page']['props']['coupons']['data'];
    $positions = [];
    foreach ($data as $index => $coupon) {
        if (in_array($coupon['code'], ['NO_EXPIRY_TEST', 'EXPIRES_SOON_TEST', 'EXPIRES_LATER_TEST'])) {
            $positions[$coupon['code']] = $index;
        }
    }

    // Verify null values come before dates, and dates are in chronological order
    expect($positions['NO_EXPIRY_TEST'])->toBeLessThan($positions['EXPIRES_SOON_TEST']);
    expect($positions['EXPIRES_SOON_TEST'])->toBeLessThan($positions['EXPIRES_LATER_TEST']);
});

test('sorts coupons by used_count ascending', function () {
    $unusedCoupon = Coupon::factory()->create(['used_count' => 0, 'code' => 'UNUSED']);
    $usedCoupon = Coupon::factory()->create(['used_count' => 5, 'code' => 'USED']);

    $response = actingAsSuperAdmin()->get(route('admin.coupons.index', ['sort' => 'used_count', 'direction' => 'asc']));

    $response->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('admin/coupons/list')
                ->where('coupons.data.0.id', $unusedCoupon->id)
                ->where('coupons.data.0.used_count', 0)
                ->where('coupons.data.1.id', $usedCoupon->id)
                ->where('coupons.data.1.used_count', 5)
        );
});

test('sorts coupons by used_count descending', function () {
    $unusedCoupon = Coupon::factory()->create(['used_count' => 0, 'code' => 'UNUSED']);
    $usedCoupon = Coupon::factory()->create(['used_count' => 5, 'code' => 'USED']);

    $response = actingAsSuperAdmin()->get(route('admin.coupons.index', ['sort' => 'used_count', 'direction' => 'desc']));

    $response->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('admin/coupons/list')
                ->where('coupons.data.0.id', $usedCoupon->id)
                ->where('coupons.data.0.used_count', 5)
                ->where('coupons.data.1.id', $unusedCoupon->id)
                ->where('coupons.data.1.used_count', 0)
        );
});

test('sorts coupons by created_at descending by default', function () {
    $oldCoupon = Coupon::factory()->create(['created_at' => now()->subDays(2), 'code' => 'OLD']);
    $newCoupon = Coupon::factory()->create(['created_at' => now(), 'code' => 'NEW']);

    $response = actingAsSuperAdmin()->get(route('admin.coupons.index'));

    $response->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('admin/coupons/list')
                ->where('coupons.data.0.id', $newCoupon->id)
                ->where('coupons.data.1.id', $oldCoupon->id)
        );
});

test('requires authentication', function () {
    Coupon::factory()->count(2)->create();

    $response = get(route('admin.coupons.index'));

    $response->assertRedirect(route('admin.login'));
});

test('requires coupons.view permission', function () {
    $role = Role::query()->where(['name' => RoleEnum::Admin])->firstOrFail();
    Coupon::factory()->count(2)->create();

    $response = actingAsAdmin()->get(route('admin.coupons.index'));

    $response->assertOk();

    $role->revokePermissionTo(Permission::CouponsView);

    $response = actingAsAdmin()->get(route('admin.coupons.index'));

    $response->assertForbidden();
});
