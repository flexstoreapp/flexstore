<?php

declare(strict_types=1);

use App\Actions\StoreOrderActivityAction;
use App\Enums\OrderActivityType;
use App\Enums\Permission;
use App\Http\Controllers\Api\V1\Admin\AdminOrderActivityController;
use App\Http\Requests\Admin\StoreOrderActivityRequest;
use App\Http\Requests\Admin\UpdateOrderActivityRequest;
use App\Http\Resources\Api\V1\Admin\AdminOrderActivityResource;
use App\Models\Order;
use App\Models\OrderActivity;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Laravel\deleteJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\patchJson;
use function Pest\Laravel\postJson;

covers(
    AdminOrderActivityController::class,
    AdminOrderActivityResource::class,
    StoreOrderActivityRequest::class,
    UpdateOrderActivityRequest::class,
    StoreOrderActivityAction::class,
);

uses()->group('api', 'admin-api');

test('the timeline is listed newest first', function (): void {
    actingAsApiAdmin(userWithPermissions([Permission::OrdersView]));

    $order = Order::factory()->create();
    $older = OrderActivity::factory()->forOrder($order)->create(['created_at' => now()->subDay()]);
    $newer = OrderActivity::factory()->forOrder($order)->create(['created_at' => now()]);
    OrderActivity::factory()->create();

    getJson('/api/v1/admin/orders/' . $order->id . '/activities')
        ->assertOk()
        ->assertJsonCount(2)
        ->assertJsonPath('0.id', $newer->id)
        ->assertJsonPath('1.id', $older->id)
        ->assertJsonStructure([['id', 'order_id', 'type', 'comment', 'metadata', 'created_at', 'user']]);
});

test('listing the timeline requires the orders view permission', function (): void {
    actingAsApiAdmin(userWithPermissions([Permission::ProductsView]));

    $order = Order::factory()->create();

    getJson('/api/v1/admin/orders/' . $order->id . '/activities')->assertForbidden();
});

test('a note is added to the timeline', function (): void {
    $user = actingAsApiAdmin(userWithPermissions([Permission::OrdersManage]));

    $order = Order::factory()->create();

    postJson('/api/v1/admin/orders/' . $order->id . '/activities', ['comment' => 'Customer called about delivery'])
        ->assertCreated()
        ->assertJsonPath('type', OrderActivityType::NoteAdded->value)
        ->assertJsonPath('comment', 'Customer called about delivery')
        ->assertJsonPath('user.id', $user->id);

    assertDatabaseHas('order_activities', [
        'order_id' => $order->id,
        'user_id' => $user->id,
        'type' => OrderActivityType::NoteAdded->value,
        'comment' => 'Customer called about delivery',
    ]);
});

test('a note requires a comment', function (): void {
    actingAsApiAdmin(userWithPermissions([Permission::OrdersManage]));

    $order = Order::factory()->create();

    postJson('/api/v1/admin/orders/' . $order->id . '/activities', [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('comment');
});

test('adding a note requires the orders manage permission', function (): void {
    actingAsApiAdmin(userWithPermissions([Permission::OrdersView]));

    $order = Order::factory()->create();

    postJson('/api/v1/admin/orders/' . $order->id . '/activities', ['comment' => 'Nope'])
        ->assertForbidden();
});

test('a note can be updated', function (): void {
    actingAsApiAdmin(userWithPermissions([Permission::OrdersManage]));

    $order = Order::factory()->create();
    $activity = OrderActivity::factory()->forOrder($order)->create([
        'type' => OrderActivityType::NoteAdded,
        'comment' => 'Original',
    ]);

    patchJson('/api/v1/admin/orders/' . $order->id . '/activities/' . $activity->id, ['comment' => 'Updated'])
        ->assertOk()
        ->assertJsonPath('comment', 'Updated');

    assertDatabaseHas('order_activities', ['id' => $activity->id, 'comment' => 'Updated']);
});

test('a system activity cannot be edited', function (): void {
    actingAsApiAdmin(userWithPermissions([Permission::OrdersManage]));

    $order = Order::factory()->create();
    $activity = OrderActivity::factory()->forOrder($order)->create(['type' => OrderActivityType::OrderPlaced]);

    patchJson('/api/v1/admin/orders/' . $order->id . '/activities/' . $activity->id, ['comment' => 'Nope'])
        ->assertForbidden();
});

test('a note belonging to another order cannot be edited', function (): void {
    actingAsApiAdmin(userWithPermissions([Permission::OrdersManage]));

    $order = Order::factory()->create();
    $otherOrder = Order::factory()->create();
    $activity = OrderActivity::factory()->forOrder($otherOrder)->create([
        'type' => OrderActivityType::NoteAdded,
        'comment' => 'Elsewhere',
    ]);

    patchJson('/api/v1/admin/orders/' . $order->id . '/activities/' . $activity->id, ['comment' => 'Updated'])
        ->assertNotFound();

    assertDatabaseHas('order_activities', ['id' => $activity->id, 'comment' => 'Elsewhere']);
});

test('a note is deleted', function (): void {
    actingAsApiAdmin(userWithPermissions([Permission::OrdersManage]));

    $order = Order::factory()->create();
    $activity = OrderActivity::factory()->forOrder($order)->create(['type' => OrderActivityType::NoteAdded]);

    deleteJson('/api/v1/admin/orders/' . $order->id . '/activities/' . $activity->id)->assertNoContent();

    assertDatabaseMissing('order_activities', ['id' => $activity->id]);
});

test('a system activity cannot be deleted', function (): void {
    actingAsApiAdmin(userWithPermissions([Permission::OrdersManage]));

    $order = Order::factory()->create();
    $activity = OrderActivity::factory()->forOrder($order)->create(['type' => OrderActivityType::PaymentReceived]);

    deleteJson('/api/v1/admin/orders/' . $order->id . '/activities/' . $activity->id)->assertForbidden();

    assertDatabaseHas('order_activities', ['id' => $activity->id]);
});
