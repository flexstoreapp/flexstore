<?php

declare(strict_types=1);

use App\Actions\RecordPaymentAction;
use App\Enums\PaymentStatus;
use App\Enums\Permission;
use App\Enums\Role as RoleEnum;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Http\Controllers\Admin\OrderPaymentRecordController;
use App\Models\Order;
use App\Models\OrderTransaction;
use App\Models\PaymentGateway;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\post;

covers(OrderPaymentRecordController::class, RecordPaymentAction::class);

uses()->group('admin', 'orders', 'payment');

test('admin can record payment for unpaid COD order', function () {
    $gateway = PaymentGateway::factory()->cod()->active()->create();

    $order = Order::factory()->unfulfilled()->create([
        'payment_gateway_id' => $gateway->id,
        'total' => '100.0000',
        'balance_due_total' => '100.0000',
    ]);

    actingAsSuperAdmin()
        ->post(route('admin.orders.record-payment.store', $order))
        ->assertRedirect();

    assertDatabaseHas('order_transactions', [
        'order_id' => $order->id,
        'type' => TransactionType::Sale->value,
        'status' => TransactionStatus::Success->value,
        'amount' => '100.0000',
        'is_manual_entry' => true,
    ]);

    assertDatabaseHas('order_activities', [
        'order_id' => $order->id,
        'type' => 'payment_received',
    ]);

    $order->refresh();
    expect($order->payment_status)->toBe(PaymentStatus::Paid)
        ->and($order->balance_due_total)->toBe('0.0000');
});

test('admin can record payment for outstanding balance on partially paid order', function () {
    $gateway = PaymentGateway::factory()->stripe()->active()->create();

    $order = Order::factory()->create([
        'payment_status' => PaymentStatus::PartiallyPaid,
        'total' => '130.0000',
        'paid_total' => '100.0000',
        'balance_due_total' => '30.0000',
        'payment_gateway_id' => $gateway->id,
    ]);

    OrderTransaction::factory()->sale()->successful()->create([
        'order_id' => $order->id,
        'amount' => '100.0000',
    ]);

    actingAsSuperAdmin()
        ->post(route('admin.orders.record-payment.store', $order))
        ->assertRedirect();

    assertDatabaseHas('order_transactions', [
        'order_id' => $order->id,
        'type' => TransactionType::Sale->value,
        'status' => TransactionStatus::Success->value,
        'amount' => '30.0000',
        'is_manual_entry' => true,
    ]);

    $order->refresh();
    expect($order->payment_status)->toBe(PaymentStatus::Paid)
        ->and($order->balance_due_total)->toBe('0.0000');
});

test('cannot record payment when no outstanding balance', function () {
    $order = Order::factory()->create([
        'payment_status' => PaymentStatus::Paid,
        'total' => '100.0000',
        'balance_due_total' => '0.0000',
    ]);

    actingAsSuperAdmin()
        ->post(route('admin.orders.record-payment.store', $order))
        ->assertStatus(422);
});

test('requires authentication', function () {
    $order = Order::factory()->create();

    post(route('admin.orders.record-payment.store', $order))
        ->assertRedirect(route('admin.login'));
});

test('requires orders.update permission', function () {
    $gateway = PaymentGateway::factory()->cod()->active()->create();
    $order = Order::factory()->unfulfilled()->create([
        'payment_gateway_id' => $gateway->id,
        'total' => '100.0000',
        'balance_due_total' => '100.0000',
    ]);

    $role = Role::query()->where(['name' => RoleEnum::Admin])->firstOrFail();
    $role->revokePermissionTo(Permission::OrdersManage);

    actingAsAdmin()->post(route('admin.orders.record-payment.store', $order))
        ->assertForbidden();
});
