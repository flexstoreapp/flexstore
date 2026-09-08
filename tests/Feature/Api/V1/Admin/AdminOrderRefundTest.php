<?php

declare(strict_types=1);

use App\Enums\FulfillmentStatus;
use App\Enums\PaymentStatus;
use App\Enums\Permission;
use App\Enums\RefundItemType;
use App\Http\Controllers\Api\V1\Admin\OrderRefundController;
use App\Http\Controllers\Api\V1\Admin\OrderRefundCreditController;
use App\Http\Resources\Api\V1\Admin\OrderRefundResource;
use App\Http\Resources\Api\V1\Admin\RefundableOrderResource;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderTransaction;
use App\Models\PaymentGateway;
use App\Payment\Drivers\CodDriver;
use App\Payment\PaymentManager;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;

covers(
    OrderRefundController::class,
    OrderRefundCreditController::class,
    OrderRefundResource::class,
    RefundableOrderResource::class,
);

uses()->group('api', 'admin-api');

beforeEach(function (): void {
    PaymentManager::fake(new CodDriver());
});

function refundableApiOrder(): Order
{
    return Order::factory()->create([
        'payment_status' => PaymentStatus::Paid,
        'fulfillment_status' => FulfillmentStatus::Fulfilled,
        'subtotal' => '100.0000',
        'shipping_total' => '5.0000',
        'discount_total' => '0.0000',
        'tax_total' => '0.0000',
        'total' => '105.0000',
        'paid_total' => '105.0000',
        'net_paid_total' => '105.0000',
    ]);
}

test('the refundable summary is returned for an order', function (): void {
    actingAsApiAdmin(userWithPermissions([Permission::OrdersRefund]));

    $order = refundableApiOrder();
    $item = OrderItem::factory()->create(['order_id' => $order->id, 'quantity' => 3]);

    getJson("/api/v1/admin/orders/{$order->id}/refund")
        ->assertOk()
        ->assertJsonPath("refundable_quantities.{$item->id}", 3)
        ->assertJsonPath('max_refundable_amount', '105.0000')
        ->assertJsonPath('refundable_shipping_amount', '5.0000')
        ->assertJsonPath('supports_gateway_refund', false)
        ->assertJsonMissingPath('return');
});

test('the refundable summary is gone for an unpaid order', function (): void {
    actingAsApiAdmin(userWithPermissions([Permission::OrdersRefund]));

    $order = Order::factory()->create(['payment_status' => PaymentStatus::Unpaid]);

    getJson("/api/v1/admin/orders/{$order->id}/refund")->assertStatus(410);
});

test('a refund is recorded against the order', function (): void {
    actingAsApiAdmin(userWithPermissions([Permission::OrdersRefund]));

    $order = refundableApiOrder();
    $item = OrderItem::factory()->create([
        'order_id' => $order->id,
        'quantity' => 5,
        'unit_price' => '2.0000',
        'total_price' => '10.0000',
        'tax_amount' => '0.0000',
    ]);

    $response = postJson("/api/v1/admin/orders/{$order->id}/refund", [
        'shipping_amount' => '5.0000',
        'reason' => 'Customer request',
        'refund_method' => 'record_only',
        'items' => [
            ['order_item_id' => $item->id, 'quantity' => 2],
        ],
    ])->assertCreated();

    expect($response->json('amount'))->toBe('9.0000')
        ->and($response->json('items'))->not->toBeEmpty();

    assertDatabaseHas('order_refunds', [
        'id' => $response->json('id'),
        'order_id' => $order->id,
        'reason' => 'Customer request',
    ]);
});

test('a refund requires a refund method', function (): void {
    actingAsApiAdmin(userWithPermissions([Permission::OrdersRefund]));

    $order = refundableApiOrder();
    $item = OrderItem::factory()->create(['order_id' => $order->id, 'quantity' => 2]);

    postJson("/api/v1/admin/orders/{$order->id}/refund", [
        'items' => [['order_item_id' => $item->id, 'quantity' => 1]],
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('refund_method');
});

test('a refund cannot exceed the refundable quantity', function (): void {
    actingAsApiAdmin(userWithPermissions([Permission::OrdersRefund]));

    $order = refundableApiOrder();
    $item = OrderItem::factory()->create(['order_id' => $order->id, 'quantity' => 1]);

    postJson("/api/v1/admin/orders/{$order->id}/refund", [
        'refund_method' => 'record_only',
        'items' => [['order_item_id' => $item->id, 'quantity' => 9]],
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('items.0.quantity');
});

test('the credit owed on an order is refunded', function (): void {
    actingAsApiAdmin(userWithPermissions([Permission::OrdersManage]));

    $gateway = PaymentGateway::factory()->cod()->create(['is_active' => true]);
    $order = Order::factory()->create([
        'payment_status' => PaymentStatus::Paid,
        'total' => '80.0000',
        'paid_total' => '100.0000',
        'net_paid_total' => '100.0000',
        'refund_total' => '0.0000',
        'credit_due_total' => '20.0000',
        'payment_gateway_id' => $gateway->id,
    ]);
    OrderTransaction::factory()->sale()->successful()->create([
        'order_id' => $order->id,
        'amount' => '100.0000',
    ]);

    postJson("/api/v1/admin/orders/{$order->id}/refund-credit")
        ->assertCreated()
        ->assertJsonPath('amount', '20.0000')
        ->assertJsonPath('items.0.type', RefundItemType::Adjustment->value);

    expect($order->refresh()->refund_total)->toBe('20.0000');
});

test('refunding credit fails when nothing is owed', function (): void {
    actingAsApiAdmin(userWithPermissions([Permission::OrdersManage]));

    $order = Order::factory()->create([
        'payment_status' => PaymentStatus::Paid,
        'total' => '100.0000',
        'credit_due_total' => '0.0000',
    ]);

    postJson("/api/v1/admin/orders/{$order->id}/refund-credit")->assertUnprocessable();
});

test('a staff member without the refund permission cannot refund', function (): void {
    actingAsApiAdmin(userWithPermissions([Permission::OrdersView]));

    $order = refundableApiOrder();

    getJson("/api/v1/admin/orders/{$order->id}/refund")->assertForbidden();
    postJson("/api/v1/admin/orders/{$order->id}/refund", ['refund_method' => 'record_only'])->assertForbidden();
});
