<?php

declare(strict_types=1);

use App\Actions\StoreOrderShipmentAction;
use App\DTOs\StoreOrderShipmentInput;
use App\Enums\FulfillmentStatus;
use App\Enums\OrderActivityType;
use App\Enums\OrderAddressType;
use App\Exceptions\OrderConflictException;
use App\Models\Order;
use App\Models\OrderActivity;
use App\Models\OrderAddress;
use App\Models\OrderItem;
use App\Models\OrderShipment;
use App\Models\OrderShipmentItem;
use App\Models\ShippingCarrier;
use App\Models\User;
use App\Notifications\CustomerOrderFulfilledNotification;
use App\Shipping\Contracts\ShippingDriver;
use App\Shipping\DTOs\ShipmentRequest;
use App\Shipping\DTOs\ShipmentResult;
use App\Shipping\DTOs\ShippingWebhookEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;

covers(StoreOrderShipmentAction::class, StoreOrderShipmentInput::class);

uses()->group('actions', 'shipment');

function recordingStoreDriver(): ShippingDriver
{
    return new class() implements ShippingDriver
    {
        public bool $createCalled = false;

        public function rates(ShipmentRequest $request): Collection
        {
            return collect();
        }

        public function createShipment(ShipmentRequest $request, ?string $serviceCode = null): ShipmentResult
        {
            $this->createCalled = true;

            return new ShipmentResult(success: true, trackingNumber: 'REC123', shipmentReference: 'rec_ref');
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

function carrierShippableOrder(FulfillmentStatus $status = FulfillmentStatus::Unfulfilled): array
{
    $carrier = ShippingCarrier::factory()->shippo()->create();
    $order = Order::factory()->create([
        'fulfillment_status' => $status,
        'shipping_carrier_id' => $carrier->id,
    ]);
    OrderAddress::factory()->create(['order_id' => $order->id, 'type' => OrderAddressType::Shipping]);
    $item = OrderItem::factory()->create(['order_id' => $order->id, 'requires_shipping' => true, 'quantity' => 2]);

    return [$order, $item];
}

function createOrderWithShippableItems(FulfillmentStatus $status = FulfillmentStatus::Unfulfilled, int $itemCount = 2, int $quantity = 3): array
{
    $order = Order::factory()->create([
        'fulfillment_status' => $status,
    ]);

    $items = OrderItem::factory()->count($itemCount)->create([
        'order_id' => $order->id,
        'quantity' => $quantity,
    ]);

    return [$order, $items];
}

test('creates a shipment with items', function () {
    [$order, $items] = createOrderWithShippableItems();
    $user = User::factory()->create();

    $action = app(StoreOrderShipmentAction::class);
    $shipment = $action->handle($user, $order, StoreOrderShipmentInput::fromArray([
        'tracking_number' => 'TRACK123',
        'tracking_url' => 'https://example.com/track/123',
        'items' => [
            ['order_item_id' => $items[0]->id, 'quantity' => 1],
        ],
    ]));

    expect($shipment)->toBeInstanceOf(OrderShipment::class)
        ->and($shipment->order_id)->toBe($order->id)
        ->and($shipment->user_id)->toBe($user->id)
        ->and($shipment->tracking_number)->toBe('TRACK123')
        ->and($shipment->tracking_url)->toBe('https://example.com/track/123')
        ->and($shipment->shipped_at)->not->toBeNull();

    expect(OrderShipmentItem::query()->where('order_shipment_id', $shipment->id)->count())->toBe(1);
});

test('auto-transitions to InProgress when partially fulfilled', function () {
    [$order, $items] = createOrderWithShippableItems();
    $user = User::factory()->create();

    $action = app(StoreOrderShipmentAction::class);
    $action->handle($user, $order, StoreOrderShipmentInput::fromArray([
        'items' => [
            ['order_item_id' => $items[0]->id, 'quantity' => 1],
        ],
    ]));

    expect($order->fresh()->fulfillment_status)->toBe(FulfillmentStatus::InProgress);
});

test('auto-transitions to Fulfilled when all shippable items are shipped', function () {
    [$order, $items] = createOrderWithShippableItems(itemCount: 1, quantity: 2);
    $user = User::factory()->create();

    $action = app(StoreOrderShipmentAction::class);
    $action->handle($user, $order, StoreOrderShipmentInput::fromArray([
        'items' => [
            ['order_item_id' => $items[0]->id, 'quantity' => 2],
        ],
    ]));

    expect($order->fresh()->fulfillment_status)->toBe(FulfillmentStatus::Fulfilled);
});

test('rejects shipment creation for on-hold orders', function () {
    [$order, $items] = createOrderWithShippableItems(FulfillmentStatus::OnHold, itemCount: 1, quantity: 2);
    $user = User::factory()->create();

    $action = app(StoreOrderShipmentAction::class);
    $action->handle($user, $order, StoreOrderShipmentInput::fromArray([
        'items' => [
            ['order_item_id' => $items[0]->id, 'quantity' => 2],
        ],
    ]));
})->throws(OrderConflictException::class);

test('logs activity on shipment creation', function () {
    [$order, $items] = createOrderWithShippableItems();
    $user = User::factory()->create();

    $action = app(StoreOrderShipmentAction::class);
    $shipment = $action->handle($user, $order, StoreOrderShipmentInput::fromArray([
        'tracking_number' => 'ABC123',
        'items' => [
            ['order_item_id' => $items[0]->id, 'quantity' => 1],
        ],
    ]));

    $activity = OrderActivity::query()
        ->where('order_id', $order->id)
        ->where('type', OrderActivityType::ItemsFulfilled)
        ->first();

    expect($activity)->not->toBeNull()
        ->and($activity->metadata['shipment_id'])->toBe($shipment->id)
        ->and($activity->metadata['tracking_number'])->toBe('ABC123')
        ->and($activity->metadata['items'])->toHaveCount(1)
        ->and($activity->metadata['items'][0]['quantity'])->toBe(1);
});

test('multiple shipments accumulate shipped quantities', function () {
    [$order, $items] = createOrderWithShippableItems(itemCount: 1, quantity: 5);
    $user = User::factory()->create();

    $action = app(StoreOrderShipmentAction::class);

    $action->handle($user, $order, StoreOrderShipmentInput::fromArray([
        'items' => [
            ['order_item_id' => $items[0]->id, 'quantity' => 2],
        ],
    ]));

    expect($order->fresh()->fulfillment_status)->toBe(FulfillmentStatus::InProgress);

    $action->handle($user, $order->fresh(), StoreOrderShipmentInput::fromArray([
        'items' => [
            ['order_item_id' => $items[0]->id, 'quantity' => 3],
        ],
    ]));

    expect($order->fresh()->fulfillment_status)->toBe(FulfillmentStatus::Fulfilled);
});

test('creates shipment with multiple items', function () {
    [$order, $items] = createOrderWithShippableItems(itemCount: 3, quantity: 2);
    $user = User::factory()->create();

    $action = app(StoreOrderShipmentAction::class);
    $shipment = $action->handle($user, $order, StoreOrderShipmentInput::fromArray([
        'items' => [
            ['order_item_id' => $items[0]->id, 'quantity' => 1],
            ['order_item_id' => $items[1]->id, 'quantity' => 2],
            ['order_item_id' => $items[2]->id, 'quantity' => 1],
        ],
    ]));

    expect(OrderShipmentItem::query()->where('order_shipment_id', $shipment->id)->count())->toBe(3);
});

test('creates shipment without tracking info', function () {
    [$order, $items] = createOrderWithShippableItems();
    $user = User::factory()->create();

    $action = app(StoreOrderShipmentAction::class);
    $shipment = $action->handle($user, $order, StoreOrderShipmentInput::fromArray([
        'items' => [
            ['order_item_id' => $items[0]->id, 'quantity' => 1],
        ],
    ]));

    expect($shipment->tracking_number)->toBeNull()
        ->and($shipment->tracking_url)->toBeNull();
});

test('rejects shipment creation for canceled orders', function () {
    [$order, $items] = createOrderWithShippableItems();
    $order->update(['canceled_at' => now()]);
    $user = User::factory()->create();

    $action = app(StoreOrderShipmentAction::class);
    $action->handle($user, $order, StoreOrderShipmentInput::fromArray([
        'items' => [
            ['order_item_id' => $items[0]->id, 'quantity' => 1],
        ],
    ]));
})->throws(OrderConflictException::class);

test('dispatches fulfilled notification to customer', function () {
    Notification::fake();

    $customer = User::factory()->create();
    $order = Order::factory()->create([
        'customer_id' => $customer->id,
        'fulfillment_status' => FulfillmentStatus::Unfulfilled,
    ]);
    $items = OrderItem::factory()->count(1)->create([
        'order_id' => $order->id,
        'quantity' => 2,
    ]);

    $action = app(StoreOrderShipmentAction::class);
    $action->handle(User::factory()->create(), $order, StoreOrderShipmentInput::fromArray([
        'notify_customer' => true,
        'items' => [
            ['order_item_id' => $items[0]->id, 'quantity' => 2],
        ],
    ]));

    Notification::assertSentTo($customer, CustomerOrderFulfilledNotification::class);
});

test('does not dispatch fulfilled notification when notify_customer is false', function () {
    Notification::fake();

    [$order, $items] = createOrderWithShippableItems();

    $action = app(StoreOrderShipmentAction::class);
    $action->handle(User::factory()->create(), $order, StoreOrderShipmentInput::fromArray([
        'notify_customer' => false,
        'items' => [
            ['order_item_id' => $items[0]->id, 'quantity' => 1],
        ],
    ]));

    Notification::assertNothingSent();
});
