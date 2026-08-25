<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\OrderActivityController;
use App\Http\Requests\Admin\UpdateOrderActivityRequest;
use App\Models\Order;
use App\Models\OrderActivity;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Laravel\delete;
use function Pest\Laravel\patch;

covers(OrderActivityController::class, UpdateOrderActivityRequest::class);

uses()->group('admin', 'order');

test('note can be updated', function () {
    actingAsSuperAdmin();

    $order = Order::factory()->create();
    $activity = OrderActivity::factory()->forOrder($order)->note('Original note')->create();

    patch(route('admin.orders.activities.update', [$order, $activity]), [
        'comment' => 'Updated note',
    ])->assertRedirect();

    assertDatabaseHas('order_activities', [
        'id' => $activity->id,
        'comment' => 'Updated note',
    ]);
});

test('updating a note validates required fields', function () {
    actingAsSuperAdmin();

    $order = Order::factory()->create();
    $activity = OrderActivity::factory()->forOrder($order)->note()->create();

    patch(route('admin.orders.activities.update', [$order, $activity]), [
        'comment' => '',
    ])->assertSessionHasErrors('comment');

    patch(route('admin.orders.activities.update', [$order, $activity]), [
        'comment' => str_repeat('a', 1001),
    ])->assertSessionHasErrors('comment');
});

test('updating a status change activity is forbidden', function () {
    actingAsSuperAdmin();

    $order = Order::factory()->create();
    $activity = OrderActivity::factory()->forOrder($order)->paymentStatusChange()->create();

    patch(route('admin.orders.activities.update', [$order, $activity]), [
        'comment' => 'Hacked',
    ])->assertForbidden();
});

test('updating an activity from another order is forbidden', function () {
    actingAsSuperAdmin();

    $order = Order::factory()->create();
    $otherOrder = Order::factory()->create();
    $activity = OrderActivity::factory()->forOrder($otherOrder)->note()->create();

    patch(route('admin.orders.activities.update', [$order, $activity]), [
        'comment' => 'Hacked',
    ])->assertForbidden();
});

test('note can be deleted', function () {
    actingAsSuperAdmin();

    $order = Order::factory()->create();
    $activity = OrderActivity::factory()->forOrder($order)->note()->create();

    delete(route('admin.orders.activities.destroy', [$order, $activity]))
        ->assertRedirect();

    assertDatabaseMissing('order_activities', [
        'id' => $activity->id,
    ]);
});

test('deleting a status change activity is forbidden', function () {
    actingAsSuperAdmin();

    $order = Order::factory()->create();
    $activity = OrderActivity::factory()->forOrder($order)->paymentStatusChange()->create();

    delete(route('admin.orders.activities.destroy', [$order, $activity]))
        ->assertForbidden();
});

test('deleting an activity from another order is forbidden', function () {
    actingAsSuperAdmin();

    $order = Order::factory()->create();
    $otherOrder = Order::factory()->create();
    $activity = OrderActivity::factory()->forOrder($otherOrder)->note()->create();

    delete(route('admin.orders.activities.destroy', [$order, $activity]))
        ->assertForbidden();
});
