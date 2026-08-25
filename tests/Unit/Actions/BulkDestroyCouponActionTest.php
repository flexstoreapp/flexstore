<?php

declare(strict_types=1);

use App\Actions\BulkDestroyCouponAction;
use App\Models\Coupon;
use App\Models\Order;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;

covers(BulkDestroyCouponAction::class);

uses()->group('actions', 'coupon');

test('bulk deletes coupons successfully', function () {
    $coupons = Coupon::factory(3)->create();
    $ids = $coupons->pluck('id')->all();

    $action = new BulkDestroyCouponAction();
    $deletedCount = $action->handle($ids);

    expect($deletedCount)->toBe(3);

    foreach ($ids as $id) {
        assertDatabaseMissing('coupons', ['id' => $id]);
    }
});

test('returns correct count of deleted coupons', function () {
    $coupons = Coupon::factory(5)->create();
    $ids = $coupons->pluck('id')->all();

    $action = new BulkDestroyCouponAction();
    $deletedCount = $action->handle($ids);

    expect($deletedCount)->toBe(5);
});

test('handles empty array gracefully', function () {
    $action = new BulkDestroyCouponAction();
    $deletedCount = $action->handle([]);

    expect($deletedCount)->toBe(0);
});

test('handles non-existent coupon ids gracefully', function () {
    $action = new BulkDestroyCouponAction();
    $deletedCount = $action->handle([9999, 10000]);

    expect($deletedCount)->toBe(0);
});

test('deletes only existing coupons when mixed with non-existent ids', function () {
    $coupons = Coupon::factory(2)->create();
    $existingIds = $coupons->pluck('id')->all();
    $mixedIds = array_merge($existingIds, [9999, 10000]);

    $action = new BulkDestroyCouponAction();
    $deletedCount = $action->handle($mixedIds);

    expect($deletedCount)->toBe(2);

    foreach ($existingIds as $id) {
        assertDatabaseMissing('coupons', ['id' => $id]);
    }
});

test('deletes coupons with different statuses', function () {
    $activeCoupon = Coupon::factory()->active()->create();
    $inactiveCoupon = Coupon::factory()->inactive()->create();
    $expiredCoupon = Coupon::factory()->active()->expired()->create();

    $ids = [$activeCoupon->id, $inactiveCoupon->id, $expiredCoupon->id];

    $action = new BulkDestroyCouponAction();
    $deletedCount = $action->handle($ids);

    expect($deletedCount)->toBe(3);

    foreach ($ids as $id) {
        assertDatabaseMissing('coupons', ['id' => $id]);
    }
});

test('deletes coupons that are referenced in orders', function () {
    $coupon = Coupon::factory()->create();

    // Create orders that reference this coupon
    Order::factory()->count(2)->create(['coupon_id' => $coupon->id]);

    $action = new BulkDestroyCouponAction();
    $deletedCount = $action->handle([$coupon->id]);

    expect($deletedCount)->toBe(1);
    assertDatabaseMissing('coupons', ['id' => $coupon->id]);

    // Verify that orders still exist but coupon_id is set to null due to nullOnDelete constraint
    expect(Order::whereNull('coupon_id')->count())->toBe(2);
});

test('deletes coupons with high usage counts', function () {
    $coupon = Coupon::factory()->create([
        'used_count' => 1000,
        'usage_limit' => 500, // Even exceeded usage limit
    ]);

    $action = new BulkDestroyCouponAction();
    $deletedCount = $action->handle([$coupon->id]);

    expect($deletedCount)->toBe(1);
    assertDatabaseMissing('coupons', ['id' => $coupon->id]);
});

test('handles single coupon deletion', function () {
    $coupon = Coupon::factory()->create();

    $action = new BulkDestroyCouponAction();
    $deletedCount = $action->handle([$coupon->id]);

    expect($deletedCount)->toBe(1);
    assertDatabaseMissing('coupons', ['id' => $coupon->id]);
});

test('handles large batch of coupons', function () {
    $coupons = Coupon::factory(50)->create();
    $ids = $coupons->pluck('id')->all();

    $action = new BulkDestroyCouponAction();
    $deletedCount = $action->handle($ids);

    expect($deletedCount)->toBe(50);

    foreach ($ids as $id) {
        assertDatabaseMissing('coupons', ['id' => $id]);
    }
});

test('does not affect other coupons when deleting specific ones', function () {
    $couponsToDelete = Coupon::factory(3)->create();
    $couponsToKeep = Coupon::factory(2)->create();

    $idsToDelete = $couponsToDelete->pluck('id')->all();
    $idsToKeep = $couponsToKeep->pluck('id')->all();

    $action = new BulkDestroyCouponAction();
    $deletedCount = $action->handle($idsToDelete);

    expect($deletedCount)->toBe(3);

    // Verify deleted coupons are gone
    foreach ($idsToDelete as $id) {
        assertDatabaseMissing('coupons', ['id' => $id]);
    }

    // Verify kept coupons still exist
    foreach ($idsToKeep as $id) {
        assertDatabaseHas('coupons', ['id' => $id]);
    }
});
