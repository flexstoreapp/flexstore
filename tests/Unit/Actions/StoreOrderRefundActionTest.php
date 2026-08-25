<?php

declare(strict_types=1);

use App\Actions\StoreOrderRefundAction;
use App\DTOs\StoreOrderRefundInput;
use App\Enums\FulfillmentStatus;
use App\Enums\PaymentStatus;
use App\Enums\RefundItemType;
use App\Enums\RefundStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderRefund;

covers(StoreOrderRefundAction::class, StoreOrderRefundInput::class);

uses()->group('actions', 'refund');

test('creates a refund record', function () {
    $order = Order::factory()->create([
        'payment_status' => PaymentStatus::Paid,
        'fulfillment_status' => FulfillmentStatus::Fulfilled,
        'subtotal' => '100.0000',
        'tax_total' => '0.0000',
        'shipping_total' => '5.0000',
        'discount_total' => '0.0000',
        'total' => '105.0000',
    ]);
    $orderItem = OrderItem::factory()->create([
        'order_id' => $order->id,
        'quantity' => 4,
        'unit_price' => '2.0000',
        'total_price' => '10.0000',
        'tax_amount' => '0.0000',
    ]);

    $data = [
        'shipping_amount' => '5.0000',
        'reason' => 'Customer request',
        'items' => [
            [
                'order_item_id' => $orderItem->id,
                'quantity' => 2,
            ],
        ],
    ];

    $action = app(StoreOrderRefundAction::class);
    $refund = $action->handle($order, StoreOrderRefundInput::fromArray($data));

    expect($refund)
        ->toBeInstanceOf(OrderRefund::class)
        ->and($refund->order_id)->toBe($order->id)
        ->and($refund->status)->toBe(RefundStatus::Pending)
        ->and($refund->amount)->toBe('9.0000') // 4.00 items + 5.00 shipping
        ->and($refund->reason)->toBe('Customer request')
        ->and($refund->is_manual_total)->toBeFalse();

    expect($refund->id)->toBeInt()->toBeGreaterThan(0);
});

test('calculates product amount from quantity and unit price', function () {
    $order = Order::factory()->create([
        'payment_status' => PaymentStatus::Paid,
        'fulfillment_status' => FulfillmentStatus::Fulfilled,
        'subtotal' => '100.0000',
        'tax_total' => '0.0000',
        'discount_total' => '0.0000',
        'total' => '100.0000',
    ]);
    $orderItem = OrderItem::factory()->create([
        'order_id' => $order->id,
        'quantity' => 5,
        'unit_price' => '10.0000',
        'total_price' => '50.0000',
        'tax_amount' => '0.0000',
    ]);

    $action = app(StoreOrderRefundAction::class);
    $refund = $action->handle($order, StoreOrderRefundInput::fromArray([
        'items' => [
            [
                'order_item_id' => $orderItem->id,
                'quantity' => 2,
            ],
        ],
    ]));

    $refundItem = $refund->items->where('type', RefundItemType::Product)->first();

    expect($refundItem)
        ->quantity->toBe(2)
        ->amount->toEqual('20.0000');

    expect($refund->amount)->toBe('20.0000');
});

test('creates refund items for multiple products', function () {
    $order = Order::factory()->create([
        'payment_status' => PaymentStatus::Paid,
        'fulfillment_status' => FulfillmentStatus::Fulfilled,
        'tax_total' => '0.0000',
        'discount_total' => '0.0000',
    ]);
    $orderItem1 = OrderItem::factory()->create([
        'order_id' => $order->id,
        'quantity' => 5,
        'unit_price' => '2.0000',
        'total_price' => '12.0000',
        'tax_amount' => '0.0000',
    ]);
    $orderItem2 = OrderItem::factory()->create([
        'order_id' => $order->id,
        'quantity' => 3,
        'unit_price' => '2.0000',
        'total_price' => '7.0000',
        'tax_amount' => '0.0000',
    ]);

    $data = [
        'items' => [
            [
                'order_item_id' => $orderItem1->id,
                'quantity' => 2,
            ],
            [
                'order_item_id' => $orderItem2->id,
                'quantity' => 1,
            ],
        ],
    ];

    $action = app(StoreOrderRefundAction::class);
    $refund = $action->handle($order, StoreOrderRefundInput::fromArray($data));

    $productItems = $refund->items->where('type', RefundItemType::Product);
    expect($productItems)->toHaveCount(2);

    $refundItem1 = $productItems->where('order_item_id', $orderItem1->id)->first();
    $refundItem2 = $productItems->where('order_item_id', $orderItem2->id)->first();

    expect($refundItem1)
        ->quantity->toBe(2)
        ->amount->toEqual('4.0000');

    expect($refundItem2)
        ->quantity->toBe(1)
        ->amount->toEqual('2.0000');
});

test('does not update order totals or statuses', function () {
    $order = Order::factory()->create([
        'payment_status' => PaymentStatus::Paid,
        'fulfillment_status' => FulfillmentStatus::Fulfilled,
        'subtotal' => '100.0000',
        'tax_total' => '0.0000',
        'shipping_total' => '0.0000',
        'discount_total' => '0.0000',
        'total' => '100.0000',
        'refund_total' => '0.0000',
    ]);
    $orderItem = OrderItem::factory()->create([
        'order_id' => $order->id,
        'quantity' => 4,
        'unit_price' => '2.0000',
        'total_price' => '10.0000',
        'tax_amount' => '0.0000',
    ]);

    $action = app(StoreOrderRefundAction::class);
    $action->handle($order, StoreOrderRefundInput::fromArray([
        'items' => [
            [
                'order_item_id' => $orderItem->id,
                'quantity' => 2,
            ],
        ],
    ]));

    $order->refresh();

    expect($order->refund_total)->toBe('0.0000')
        ->and($order->payment_status)->toBe(PaymentStatus::Paid)
        ->and($order->fulfillment_status)->toBe(FulfillmentStatus::Fulfilled)
        ->and($order->activities()->count())->toBe(0);
});

test('handles refund without items (shipping only)', function () {
    $order = Order::factory()->create([
        'payment_status' => PaymentStatus::Paid,
        'shipping_total' => '10.0000',
    ]);

    $data = [
        'shipping_amount' => '10.0000',
        'reason' => 'Shipping refund only',
    ];

    $action = app(StoreOrderRefundAction::class);
    $refund = $action->handle($order, StoreOrderRefundInput::fromArray($data));

    expect($refund->items)->toHaveCount(1);
    expect($refund->amount)->toBe('10.0000');

    $shippingRefundItem = $refund->items->first();
    expect($shippingRefundItem->type->value)->toBe('shipping')
        ->and($shippingRefundItem->order_item_id)->toBeNull()
        ->and($shippingRefundItem->quantity)->toBeNull()
        ->and($shippingRefundItem->amount)->toBe('10.0000')
        ->and($shippingRefundItem->restock)->toBeFalse();
});

test('handles refund with zero shipping amount', function () {
    $order = Order::factory()->create([
        'payment_status' => PaymentStatus::Paid,
        'fulfillment_status' => FulfillmentStatus::Fulfilled,
    ]);
    $orderItem = OrderItem::factory()->create([
        'order_id' => $order->id,
        'quantity' => 2,
        'unit_price' => '2.0000',
        'total_price' => '5.0000',
        'tax_amount' => '0.0000',
    ]);

    $data = [
        'items' => [
            [
                'order_item_id' => $orderItem->id,
                'quantity' => 2,
            ],
        ],
    ];

    $action = app(StoreOrderRefundAction::class);
    $refund = $action->handle($order, StoreOrderRefundInput::fromArray($data));

    $shippingItems = $refund->items->filter(fn ($item) => $item->type->value === 'shipping');
    expect($shippingItems)->toHaveCount(0);
});

test('aborts when order is fully refunded', function () {
    $order = Order::factory()->create([
        'payment_status' => PaymentStatus::Refunded,
        'fulfillment_status' => FulfillmentStatus::Fulfilled,
        'subtotal' => '50.0000',
        'tax_total' => '0.0000',
        'shipping_total' => '0.0000',
        'discount_total' => '0.0000',
        'total' => '50.0000',
        'refund_total' => '50.0000',
    ]);
    $orderItem = OrderItem::factory()->create([
        'order_id' => $order->id,
        'quantity' => 2,
        'unit_price' => '25.0000',
        'total_price' => '50.0000',
        'tax_amount' => '0.0000',
    ]);

    expect($order->is_refundable)->toBeFalse();

    $action = app(StoreOrderRefundAction::class);

    $action->handle($order, StoreOrderRefundInput::fromArray([
        'items' => [
            [
                'order_item_id' => $orderItem->id,
                'quantity' => 1,
            ],
        ],
    ]));
})->throws(App\Exceptions\OrderNotRefundableException::class);

test('aborts when order payment status is not refundable', function () {
    $order = Order::factory()->create([
        'payment_status' => PaymentStatus::Unpaid,
        'fulfillment_status' => FulfillmentStatus::Fulfilled,
        'subtotal' => '50.0000',
        'tax_total' => '0.0000',
        'shipping_total' => '0.0000',
        'discount_total' => '0.0000',
        'total' => '50.0000',
        'refund_total' => '0.0000',
    ]);
    $orderItem = OrderItem::factory()->create([
        'order_id' => $order->id,
        'quantity' => 2,
        'unit_price' => '25.0000',
        'total_price' => '50.0000',
        'tax_amount' => '0.0000',
    ]);

    expect($order->is_refundable)->toBeFalse();

    $action = app(StoreOrderRefundAction::class);

    $action->handle($order, StoreOrderRefundInput::fromArray([
        'items' => [
            [
                'order_item_id' => $orderItem->id,
                'quantity' => 1,
            ],
        ],
    ]));
})->throws(App\Exceptions\OrderNotRefundableException::class);

test('creates separate tax refund item', function () {
    $order = Order::factory()->create([
        'payment_status' => PaymentStatus::Paid,
        'fulfillment_status' => FulfillmentStatus::Fulfilled,
        'subtotal' => '100.0000',
        'tax_total' => '10.0000',
        'shipping_total' => '0.0000',
        'discount_total' => '0.0000',
        'total' => '110.0000',
    ]);
    $orderItem = OrderItem::factory()->create([
        'order_id' => $order->id,
        'quantity' => 4,
        'unit_price' => '25.0000',
        'total_price' => '100.0000',
        'tax_amount' => '10.0000',
    ]);

    $action = app(StoreOrderRefundAction::class);
    $refund = $action->handle($order, StoreOrderRefundInput::fromArray([
        'items' => [
            [
                'order_item_id' => $orderItem->id,
                'quantity' => 2,
            ],
        ],
    ]));

    $taxItem = $refund->items->where('type', RefundItemType::Tax)->first();

    // tax: 10.00 / 4 * 2 = 5.00
    expect($taxItem)
        ->not->toBeNull()
        ->amount->toEqual('5.0000')
        ->order_item_id->toBeNull()
        ->quantity->toBeNull()
        ->restock->toBeFalse();

    $productItem = $refund->items->where('type', RefundItemType::Product)->first();
    expect($productItem->amount)->toEqual('50.0000'); // 25.00 * 2

    // total = products (50) + tax (5) = 55
    expect($refund->amount)->toBe('55.0000');
});

test('creates separate discount refund item', function () {
    $order = Order::factory()->create([
        'payment_status' => PaymentStatus::Paid,
        'fulfillment_status' => FulfillmentStatus::Fulfilled,
        'subtotal' => '100.0000',
        'tax_total' => '0.0000',
        'shipping_total' => '0.0000',
        'discount_total' => '20.0000',
        'total' => '80.0000',
    ]);
    $orderItem = OrderItem::factory()->create([
        'order_id' => $order->id,
        'quantity' => 4,
        'unit_price' => '25.0000',
        'total_price' => '100.0000',
        'tax_amount' => '0.0000',
    ]);

    $action = app(StoreOrderRefundAction::class);
    $refund = $action->handle($order, StoreOrderRefundInput::fromArray([
        'items' => [
            [
                'order_item_id' => $orderItem->id,
                'quantity' => 2,
            ],
        ],
    ]));

    $discountItem = $refund->items->where('type', RefundItemType::Discount)->first();

    // discount: order discount_total (20.00) * item proportion (100/100) / 4 qty * 2 = 10.00
    expect($discountItem)
        ->not->toBeNull()
        ->amount->toEqual('10.0000')
        ->order_item_id->toBeNull()
        ->quantity->toBeNull()
        ->restock->toBeFalse();

    // total = products (50) - discount (10) = 40
    expect($refund->amount)->toBe('40.0000');
});

test('creates both tax and discount refund items', function () {
    $order = Order::factory()->create([
        'payment_status' => PaymentStatus::Paid,
        'fulfillment_status' => FulfillmentStatus::Fulfilled,
        'subtotal' => '100.0000',
        'tax_total' => '10.0000',
        'shipping_total' => '0.0000',
        'discount_total' => '20.0000',
        'total' => '90.0000',
    ]);
    $orderItem = OrderItem::factory()->create([
        'order_id' => $order->id,
        'quantity' => 4,
        'unit_price' => '25.0000',
        'total_price' => '100.0000',
        'tax_amount' => '10.0000',
    ]);

    $action = app(StoreOrderRefundAction::class);
    $refund = $action->handle($order, StoreOrderRefundInput::fromArray([
        'items' => [
            [
                'order_item_id' => $orderItem->id,
                'quantity' => 2,
            ],
        ],
    ]));

    $taxItem = $refund->items->where('type', RefundItemType::Tax)->first();
    $discountItem = $refund->items->where('type', RefundItemType::Discount)->first();

    expect($taxItem->amount)->toEqual('5.0000');
    expect($discountItem->amount)->toEqual('10.0000');

    // total = products (50) - discount (10) + tax (5) = 45
    expect($refund->amount)->toBe('45.0000');
});

test('skips tax and discount items when zero', function () {
    $order = Order::factory()->create([
        'payment_status' => PaymentStatus::Paid,
        'fulfillment_status' => FulfillmentStatus::Fulfilled,
        'subtotal' => '30.0000',
        'tax_total' => '0.0000',
        'discount_total' => '0.0000',
        'total' => '30.0000',
    ]);
    $orderItem = OrderItem::factory()->create([
        'order_id' => $order->id,
        'quantity' => 2,
        'unit_price' => '1.0000',
        'total_price' => '3.0000',
        'tax_amount' => '0.0000',
    ]);

    $action = app(StoreOrderRefundAction::class);
    $refund = $action->handle($order, StoreOrderRefundInput::fromArray([
        'items' => [
            [
                'order_item_id' => $orderItem->id,
                'quantity' => 1,
            ],
        ],
    ]));

    expect($refund->items->where('type', RefundItemType::Tax))->toHaveCount(0);
    expect($refund->items->where('type', RefundItemType::Discount))->toHaveCount(0);
    expect($refund->items)->toHaveCount(1);
    expect($refund->amount)->toBe('1.0000');
});

test('skips shipping refund item when shipping amount is zero', function () {
    $order = Order::factory()->create([
        'payment_status' => PaymentStatus::Paid,
        'fulfillment_status' => FulfillmentStatus::Fulfilled,
        'subtotal' => '100.0000',
        'tax_total' => '0.0000',
        'shipping_total' => '10.0000',
        'discount_total' => '0.0000',
        'total' => '110.0000',
        'refund_total' => '0.0000',
    ]);
    $orderItem = OrderItem::factory()->create([
        'order_id' => $order->id,
        'quantity' => 2,
        'unit_price' => '50.0000',
        'total_price' => '100.0000',
        'tax_amount' => '0.0000',
    ]);

    $action = app(StoreOrderRefundAction::class);

    $refund = $action->handle($order, StoreOrderRefundInput::fromArray([
        'shipping_amount' => '0.0000',
        'items' => [
            [
                'order_item_id' => $orderItem->id,
                'quantity' => 1,
            ],
        ],
    ]));

    expect($refund->amount)->toBe('50.0000');
    expect($refund->items)->toHaveCount(1);
    expect($refund->items->first()->type)->toBe(RefundItemType::Product);
});

test('applies restock flag to all product items', function () {
    $order = Order::factory()->create([
        'payment_status' => PaymentStatus::Paid,
        'fulfillment_status' => FulfillmentStatus::Fulfilled,
        'subtotal' => '100.0000',
        'tax_total' => '0.0000',
        'shipping_total' => '0.0000',
        'discount_total' => '0.0000',
        'total' => '100.0000',
    ]);
    $orderItem1 = OrderItem::factory()->create([
        'order_id' => $order->id,
        'quantity' => 5,
        'unit_price' => '10.0000',
        'total_price' => '50.0000',
        'tax_amount' => '0.0000',
    ]);
    $orderItem2 = OrderItem::factory()->create([
        'order_id' => $order->id,
        'quantity' => 3,
        'unit_price' => '10.0000',
        'total_price' => '30.0000',
        'tax_amount' => '0.0000',
    ]);

    $action = app(StoreOrderRefundAction::class);
    $refund = $action->handle($order, StoreOrderRefundInput::fromArray([
        'restock' => false,
        'items' => [
            ['order_item_id' => $orderItem1->id, 'quantity' => 1],
            ['order_item_id' => $orderItem2->id, 'quantity' => 1],
        ],
    ]));

    $productItems = $refund->items->where('type', RefundItemType::Product);

    expect($productItems)->each(fn ($item) => $item->restock->toBeFalse());
});

test('defaults restock to true when not specified', function () {
    $order = Order::factory()->create([
        'payment_status' => PaymentStatus::Paid,
        'fulfillment_status' => FulfillmentStatus::Fulfilled,
        'subtotal' => '100.0000',
        'tax_total' => '0.0000',
        'discount_total' => '0.0000',
        'total' => '100.0000',
    ]);
    $orderItem = OrderItem::factory()->create([
        'order_id' => $order->id,
        'quantity' => 2,
        'unit_price' => '50.0000',
        'total_price' => '100.0000',
        'tax_amount' => '0.0000',
    ]);

    $action = app(StoreOrderRefundAction::class);
    $refund = $action->handle($order, StoreOrderRefundInput::fromArray([
        'items' => [
            ['order_item_id' => $orderItem->id, 'quantity' => 1],
        ],
    ]));

    expect($refund->items->where('type', RefundItemType::Product)->first()->restock)->toBeTrue();
});

test('creates shipping refund with custom amount', function () {
    $order = Order::factory()->create([
        'payment_status' => PaymentStatus::Paid,
        'shipping_total' => '15.0000',
        'total' => '100.0000',
    ]);

    $action = app(StoreOrderRefundAction::class);
    $refund = $action->handle($order, StoreOrderRefundInput::fromArray([
        'shipping_amount' => '8.5000',
        'reason' => 'Partial shipping refund',
    ]));

    expect($refund->amount)->toBe('8.5000');
    expect($refund->items)->toHaveCount(1);

    $shippingItem = $refund->items->first();
    expect($shippingItem->type)->toBe(RefundItemType::Shipping)
        ->and($shippingItem->amount)->toEqual('8.5000')
        ->and($shippingItem->restock)->toBeFalse();
});

test('stores is_manual_total and creates positive adjustment item when total is overridden upward', function () {
    $order = Order::factory()->create([
        'payment_status' => PaymentStatus::Paid,
        'fulfillment_status' => FulfillmentStatus::Fulfilled,
        'subtotal' => '100.0000',
        'tax_total' => '0.0000',
        'discount_total' => '0.0000',
        'total' => '100.0000',
    ]);
    $orderItem = OrderItem::factory()->create([
        'order_id' => $order->id,
        'quantity' => 5,
        'unit_price' => '10.0000',
        'total_price' => '50.0000',
        'tax_amount' => '0.0000',
    ]);

    $action = app(StoreOrderRefundAction::class);
    $refund = $action->handle($order, StoreOrderRefundInput::fromArray([
        'items' => [
            [
                'order_item_id' => $orderItem->id,
                'quantity' => 1,
            ],
        ],
        'total' => '15.0000',
        'is_manual_total' => true,
    ]));

    expect($refund->amount)->toBe('15.0000')
        ->and($refund->is_manual_total)->toBeTrue();

    // computed = 10.00, manual = 15.00, adjustment = +5.00
    $adjustmentItem = $refund->items->where('type', RefundItemType::Adjustment)->first();
    expect($adjustmentItem)
        ->not->toBeNull()
        ->amount->toEqual('5.0000')
        ->order_item_id->toBeNull()
        ->restock->toBeFalse();
});

test('creates negative adjustment item when manual total is lower than computed', function () {
    $order = Order::factory()->create([
        'payment_status' => PaymentStatus::Paid,
        'fulfillment_status' => FulfillmentStatus::Fulfilled,
        'subtotal' => '100.0000',
        'tax_total' => '10.0000',
        'shipping_total' => '5.0000',
        'discount_total' => '0.0000',
        'total' => '115.0000',
    ]);
    $orderItem = OrderItem::factory()->create([
        'order_id' => $order->id,
        'quantity' => 4,
        'unit_price' => '25.0000',
        'total_price' => '100.0000',
        'tax_amount' => '10.0000',
    ]);

    $action = app(StoreOrderRefundAction::class);
    $refund = $action->handle($order, StoreOrderRefundInput::fromArray([
        'items' => [
            [
                'order_item_id' => $orderItem->id,
                'quantity' => 2,
            ],
        ],
        'shipping_amount' => '5.0000',
        'total' => '50.0000',
        'is_manual_total' => true,
    ]));

    // computed = products (50) + tax (5) + shipping (5) = 60.00
    // manual = 50.00, adjustment = -10.00
    expect($refund->amount)->toBe('50.0000');

    $adjustmentItem = $refund->items->where('type', RefundItemType::Adjustment)->first();
    expect($adjustmentItem)
        ->not->toBeNull()
        ->amount->toEqual('-10.0000');
});

test('skips adjustment item when manual total equals computed total', function () {
    $order = Order::factory()->create([
        'payment_status' => PaymentStatus::Paid,
        'fulfillment_status' => FulfillmentStatus::Fulfilled,
        'subtotal' => '100.0000',
        'tax_total' => '0.0000',
        'discount_total' => '0.0000',
        'total' => '100.0000',
    ]);
    $orderItem = OrderItem::factory()->create([
        'order_id' => $order->id,
        'quantity' => 5,
        'unit_price' => '10.0000',
        'total_price' => '50.0000',
        'tax_amount' => '0.0000',
    ]);

    $action = app(StoreOrderRefundAction::class);
    $refund = $action->handle($order, StoreOrderRefundInput::fromArray([
        'items' => [
            [
                'order_item_id' => $orderItem->id,
                'quantity' => 2,
            ],
        ],
        'total' => '20.0000',
        'is_manual_total' => true,
    ]));

    expect($refund->amount)->toBe('20.0000')
        ->and($refund->is_manual_total)->toBeTrue();

    expect($refund->items->where('type', RefundItemType::Adjustment))->toHaveCount(0);
});

test('uses computed total when is_manual_total is false', function () {
    $order = Order::factory()->create([
        'payment_status' => PaymentStatus::Paid,
        'fulfillment_status' => FulfillmentStatus::Fulfilled,
        'subtotal' => '100.0000',
        'tax_total' => '0.0000',
        'shipping_total' => '5.0000',
        'discount_total' => '0.0000',
        'total' => '105.0000',
    ]);
    $orderItem = OrderItem::factory()->create([
        'order_id' => $order->id,
        'quantity' => 2,
        'unit_price' => '10.0000',
        'total_price' => '20.0000',
        'tax_amount' => '0.0000',
    ]);

    $action = app(StoreOrderRefundAction::class);
    $refund = $action->handle($order, StoreOrderRefundInput::fromArray([
        'items' => [
            [
                'order_item_id' => $orderItem->id,
                'quantity' => 1,
            ],
        ],
        'shipping_amount' => '5.0000',
    ]));

    expect($refund->amount)->toBe('15.0000') // 10.00 + 5.00 shipping
        ->and($refund->is_manual_total)->toBeFalse();
});
