<?php

declare(strict_types=1);

use App\Actions\DeleteOrderShipmentAction;
use App\Enums\FulfillmentStatus;
use App\Enums\OrderActivityType;
use App\Models\Order;
use App\Models\OrderActivity;
use App\Models\OrderItem;
use App\Models\OrderShipment;
use App\Models\OrderShipmentItem;
use App\Models\User;
use App\Shipping\Contracts\ShippingDriver;
use App\Shipping\DTOs\ShipmentRequest;
use App\Shipping\DTOs\ShipmentResult;
use App\Shipping\DTOs\ShippingWebhookEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

covers(DeleteOrderShipmentAction::class);

uses()->group('actions', 'shipment');

function recordingDeleteDriver(): ShippingDriver
{
    return new class() implements ShippingDriver
    {
        public bool $cancelCalled = false;

        public function rates(ShipmentRequest $request): Collection
        {
            return collect();
        }

        public function createShipment(ShipmentRequest $request, ?string $serviceCode = null): ShipmentResult
        {
            return new ShipmentResult(success: true);
        }

        public function createReturnShipment(ShipmentRequest $request, ?string $serviceCode = null): ShipmentResult
        {
            return new ShipmentResult(success: false);
        }

        public function supportsReturnLabels(): bool
        {
            return false;
        }

        public function cancelShipment(string $shipmentReference): bool
        {
            $this->cancelCalled = true;

            return true;
        }

        public function verifyWebhook(Request $request): bool
        {
            return true;
        }

        public function parseWebhook(Request $request): ShippingWebhookEvent
        {
            return new ShippingWebhookEvent(type: 'noop');
        }

        public function track(string $shipmentReference): ?ShippingWebhookEvent
        {
            return null;
        }

        public function testConnection(): bool
        {
            return true;
        }

        public function supportsLiveRates(): bool
        {
            return true;
        }

        public function collectsCod(): bool
        {
            return false;
        }

        public function isManual(): bool
        {
            return false;
        }
    };
}

test('deletes shipment and its items', function () {
    $order = Order::factory()->create(['fulfillment_status' => FulfillmentStatus::InProgress]);
    $item = OrderItem::factory()->create([
        'order_id' => $order->id,
        'quantity' => 3,
    ]);

    $shipment = OrderShipment::factory()->create(['order_id' => $order->id]);
    OrderShipmentItem::factory()->create([
        'order_shipment_id' => $shipment->id,
        'order_item_id' => $item->id,
        'quantity' => 3,
    ]);

    $user = User::factory()->create();
    $action = app(DeleteOrderShipmentAction::class);
    $action->handle($user, $shipment);

    expect(OrderShipment::query()->find($shipment->id))->toBeNull()
        ->and(OrderShipmentItem::query()->where('order_shipment_id', $shipment->id)->count())->toBe(0);
});

test('logs fulfillment canceled activity', function () {
    $order = Order::factory()->create(['fulfillment_status' => FulfillmentStatus::InProgress]);
    $item = OrderItem::factory()->create([
        'order_id' => $order->id,
        'quantity' => 3,
    ]);

    $shipment = OrderShipment::factory()->create(['order_id' => $order->id]);
    OrderShipmentItem::factory()->create([
        'order_shipment_id' => $shipment->id,
        'order_item_id' => $item->id,
        'quantity' => 3,
    ]);

    $user = User::factory()->create();
    $action = app(DeleteOrderShipmentAction::class);
    $action->handle($user, $shipment);

    $activity = OrderActivity::query()
        ->where('order_id', $order->id)
        ->where('type', OrderActivityType::FulfillmentCanceled->value)
        ->first();

    expect($activity)->not->toBeNull()
        ->and($activity->metadata['items'])->toHaveCount(1)
        ->and($activity->metadata['items'][0]['quantity'])->toBe(3);
});

test('reverts fulfillment to in progress when cancelling with remaining shipments', function () {
    $order = Order::factory()->create(['fulfillment_status' => FulfillmentStatus::Fulfilled]);
    $item = OrderItem::factory()->create([
        'order_id' => $order->id,
        'quantity' => 5,
    ]);

    $shipment1 = OrderShipment::factory()->create(['order_id' => $order->id]);
    OrderShipmentItem::factory()->create([
        'order_shipment_id' => $shipment1->id,
        'order_item_id' => $item->id,
        'quantity' => 3,
    ]);

    $shipment2 = OrderShipment::factory()->create(['order_id' => $order->id]);
    OrderShipmentItem::factory()->create([
        'order_shipment_id' => $shipment2->id,
        'order_item_id' => $item->id,
        'quantity' => 2,
    ]);

    $user = User::factory()->create();
    $action = app(DeleteOrderShipmentAction::class);
    $action->handle($user, $shipment2);

    expect($order->fresh()->fulfillment_status)->toBe(FulfillmentStatus::InProgress);
});

test('reverts fulfillment to unfulfilled when cancelling all shipments', function () {
    $order = Order::factory()->create(['fulfillment_status' => FulfillmentStatus::InProgress]);
    $item = OrderItem::factory()->create([
        'order_id' => $order->id,
        'quantity' => 3,
    ]);

    $shipment = OrderShipment::factory()->create(['order_id' => $order->id]);
    OrderShipmentItem::factory()->create([
        'order_shipment_id' => $shipment->id,
        'order_item_id' => $item->id,
        'quantity' => 3,
    ]);

    $user = User::factory()->create();
    $action = app(DeleteOrderShipmentAction::class);
    $action->handle($user, $shipment);

    expect($order->fresh()->fulfillment_status)->toBe(FulfillmentStatus::Unfulfilled);
});

test('reverts fulfilled to unfulfilled when cancelling the only shipment', function () {
    $order = Order::factory()->create(['fulfillment_status' => FulfillmentStatus::Fulfilled]);
    $item = OrderItem::factory()->create([
        'order_id' => $order->id,
        'quantity' => 3,
    ]);

    $shipment = OrderShipment::factory()->create(['order_id' => $order->id]);
    OrderShipmentItem::factory()->create([
        'order_shipment_id' => $shipment->id,
        'order_item_id' => $item->id,
        'quantity' => 3,
    ]);

    $user = User::factory()->create();
    $action = app(DeleteOrderShipmentAction::class);
    $action->handle($user, $shipment);

    expect($order->fresh()->fulfillment_status)->toBe(FulfillmentStatus::Unfulfilled);
});

test('ignores non-shippable items when recalculating fulfillment status', function () {
    $order = Order::factory()->create(['fulfillment_status' => FulfillmentStatus::Fulfilled]);

    $shippable = OrderItem::factory()->create([
        'order_id' => $order->id,
        'quantity' => 3,
    ]);

    OrderItem::factory()->create([
        'order_id' => $order->id,
        'quantity' => 5,
    ]);

    $shipment1 = OrderShipment::factory()->create(['order_id' => $order->id]);
    OrderShipmentItem::factory()->create([
        'order_shipment_id' => $shipment1->id,
        'order_item_id' => $shippable->id,
        'quantity' => 2,
    ]);

    $shipment2 = OrderShipment::factory()->create(['order_id' => $order->id]);
    OrderShipmentItem::factory()->create([
        'order_shipment_id' => $shipment2->id,
        'order_item_id' => $shippable->id,
        'quantity' => 1,
    ]);

    $user = User::factory()->create();
    $action = app(DeleteOrderShipmentAction::class);
    $action->handle($user, $shipment2);

    expect($order->fresh()->fulfillment_status)->toBe(FulfillmentStatus::InProgress);
});
