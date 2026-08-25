<?php

declare(strict_types=1);

use App\Actions\StoreOrderActivityAction;
use App\Enums\OrderActivityType;
use App\Http\Controllers\Admin\OrderActivityController;
use App\Http\Requests\Admin\StoreOrderActivityRequest;
use App\Models\Order;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\post;

covers(OrderActivityController::class, StoreOrderActivityRequest::class, StoreOrderActivityAction::class);

uses()->group('admin', 'order');

test('note can be added to an order', function () {
    actingAsSuperAdmin();

    $order = Order::factory()->create();

    post(route('admin.orders.activities.store', $order), [
        'comment' => 'Customer called about delivery',
    ])->assertRedirect();

    assertDatabaseHas('order_activities', [
        'order_id' => $order->id,
        'user_id' => auth()->id(),
        'type' => OrderActivityType::NoteAdded->value,
        'comment' => 'Customer called about delivery',
    ]);
});

test('storing a note validates required fields', function () {
    actingAsSuperAdmin();

    $order = Order::factory()->create();

    post(route('admin.orders.activities.store', $order), [
        'comment' => '',
    ])->assertSessionHasErrors('comment');

    post(route('admin.orders.activities.store', $order), [
        'comment' => str_repeat('a', 1001),
    ])->assertSessionHasErrors('comment');
});
