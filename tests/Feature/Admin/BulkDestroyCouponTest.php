<?php

declare(strict_types=1);

use App\Actions\BulkDestroyCouponAction;
use App\Enums\Permission;
use App\Enums\Role as RoleEnum;
use App\Http\Controllers\Admin\BulkCouponController;
use App\Http\Requests\Admin\BulkDestroyCouponRequest;
use App\Models\Coupon;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;

covers(BulkCouponController::class, BulkDestroyCouponRequest::class, BulkDestroyCouponAction::class);

uses()->group('coupon');

test('bulk deletes coupons', function () {
    $coupons = Coupon::factory(3)->create();
    $ids = $coupons->pluck('id')->all();

    $response = actingAsSuperAdmin()->delete(route('admin.coupons.bulk.destroy'), [
        'ids' => $ids,
    ]);

    $response->assertRedirect()
        ->assertSessionHasNoErrors();

    foreach ($ids as $id) {
        assertDatabaseMissing('coupons', ['id' => $id]);
    }
});

test('requires at least one coupon id', function () {
    $response = actingAsSuperAdmin()->delete(route('admin.coupons.bulk.destroy'), [
        'ids' => [],
    ]);

    $response->assertRedirectBack()
        ->assertInvalid('ids');
});

test('validates that ids exist', function () {
    $coupon = Coupon::factory()->create();
    $nonExistentId = 9999;

    $response = actingAsSuperAdmin()->delete(route('admin.coupons.bulk.destroy'), [
        'ids' => [$coupon->id, $nonExistentId],
    ]);

    $response->assertRedirectBack()
        ->assertInvalid('ids');
});

test('requires coupons.delete permission', function () {
    $role = Role::query()->where(['name' => RoleEnum::Admin])->firstOrFail();
    $coupons = Coupon::factory()->count(2)->create();
    $ids = $coupons->pluck('id')->all();

    $response = actingAsAdmin()->delete(route('admin.coupons.bulk.destroy'), [
        'ids' => $ids,
    ]);

    $response->assertRedirectBack()
        ->assertSessionHasNoErrors();

    foreach ($coupons as $coupon) {
        assertDatabaseMissing('coupons', [
            'id' => $coupon->id,
        ]);
    }

    $role->revokePermissionTo(Permission::CouponsDelete);

    $moreCoupons = Coupon::factory()->count(2)->create();

    $response = actingAsAdmin()->delete(route('admin.coupons.bulk.destroy'), [
        'ids' => $moreCoupons->pluck('id')->all(),
    ]);

    $response->assertForbidden();

    foreach ($moreCoupons as $coupon) {
        assertDatabaseHas('coupons', [
            'id' => $coupon->id,
        ]);
    }
});
