<?php

declare(strict_types=1);

use App\Enums\PaymentStatus;
use App\Enums\Permission;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Http\Controllers\Api\V1\Admin\OrderPaymentRecordController;
use App\Http\Controllers\Api\V1\Admin\VoidPaymentController;
use App\Http\Resources\Api\V1\Admin\OrderPaymentSummaryResource;
use App\Models\Order;
use App\Models\OrderTransaction;
use App\Models\PaymentGateway;
use App\Payment\Drivers\CodDriver;
use App\Payment\PaymentManager;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\postJson;

covers(
    OrderPaymentRecordController::class,
    VoidPaymentController::class,
    OrderPaymentSummaryResource::class,
);

uses()->group('api', 'admin-api');

beforeEach(function (): void {
    PaymentManager::fake(new CodDriver());
});

test('an outstanding balance is recorded as paid', function (): void {
    actingAsApiAdmin(userWithPermissions([Permission::OrdersManage]));

    $gateway = PaymentGateway::factory()->cod()->active()->create();
    $order = Order::factory()->unfulfilled()->create([
        'payment_gateway_id' => $gateway->id,
        'total' => '100.0000',
        'balance_due_total' => '100.0000',
    ]);

    postJson("/api/v1/admin/orders/{$order->id}/record-payment")
        ->assertOk()
        ->assertJsonPath('payment_status', PaymentStatus::Paid->value)
        ->assertJsonPath('balance_due_total', '0.0000');

    assertDatabaseHas('order_transactions', [
        'order_id' => $order->id,
        'type' => TransactionType::Sale->value,
        'status' => TransactionStatus::Success->value,
        'amount' => '100.0000',
        'is_manual_entry' => true,
    ]);
});

test('recording a payment fails when nothing is owed', function (): void {
    actingAsApiAdmin(userWithPermissions([Permission::OrdersManage]));

    $gateway = PaymentGateway::factory()->cod()->active()->create();
    $order = Order::factory()->create([
        'payment_gateway_id' => $gateway->id,
        'payment_status' => PaymentStatus::Paid,
        'total' => '100.0000',
        'paid_total' => '100.0000',
        'balance_due_total' => '0.0000',
    ]);

    postJson("/api/v1/admin/orders/{$order->id}/record-payment")->assertUnprocessable();
});

test('a manual payment is voided', function (): void {
    actingAsApiAdmin(userWithPermissions([Permission::OrdersManage]));

    $gateway = PaymentGateway::factory()->cod()->active()->create();
    $order = Order::factory()->inProgress()->create([
        'payment_gateway_id' => $gateway->id,
        'total' => '100.0000',
        'paid_total' => '100.0000',
        'net_paid_total' => '100.0000',
        'balance_due_total' => '0.0000',
    ]);
    OrderTransaction::factory()->sale()->successful()->create([
        'order_id' => $order->id,
        'amount' => '100.0000',
        'is_manual_entry' => true,
    ]);

    postJson("/api/v1/admin/orders/{$order->id}/void-payment")
        ->assertOk()
        ->assertJsonPath('id', $order->id)
        ->assertJsonPath('paid_total', '0.0000');

    assertDatabaseHas('order_transactions', [
        'order_id' => $order->id,
        'type' => TransactionType::Void->value,
        'status' => TransactionStatus::Success->value,
        'amount' => '100.0000',
    ]);
});

test('voiding fails when there is nothing to void', function (): void {
    actingAsApiAdmin(userWithPermissions([Permission::OrdersManage]));

    $gateway = PaymentGateway::factory()->cod()->active()->create();
    $order = Order::factory()->unfulfilled()->create([
        'payment_gateway_id' => $gateway->id,
        'total' => '100.0000',
        'paid_total' => '0.0000',
        'balance_due_total' => '100.0000',
    ]);

    postJson("/api/v1/admin/orders/{$order->id}/void-payment")->assertUnprocessable();
});

test('a staff member without the manage permission cannot touch payments', function (): void {
    actingAsApiAdmin(userWithPermissions([Permission::OrdersView]));

    $order = Order::factory()->create();

    postJson("/api/v1/admin/orders/{$order->id}/record-payment")->assertForbidden();
    postJson("/api/v1/admin/orders/{$order->id}/void-payment")->assertForbidden();
});

test('a customer token cannot touch payments', function (): void {
    actingAsApiCustomer();

    $order = Order::factory()->create();

    postJson("/api/v1/admin/orders/{$order->id}/record-payment")->assertForbidden();
});
