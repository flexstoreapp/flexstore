<?php

declare(strict_types=1);

use App\Actions\VoidPaymentAction;
use App\Enums\PaymentStatus;
use App\Enums\Permission;
use App\Enums\Role as RoleEnum;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Http\Controllers\Admin\VoidPaymentController;
use App\Models\Order;
use App\Models\OrderTransaction;
use App\Models\PaymentGateway;
use App\Payment\Drivers\CodDriver;
use App\Payment\PaymentManager;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\post;

covers(VoidPaymentController::class, VoidPaymentAction::class);

uses()->group('admin', 'orders', 'payment');

test('paid COD order can be voided', function () {
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

    PaymentManager::fake(new CodDriver());

    actingAsSuperAdmin()
        ->post(route('admin.orders.void-payment', $order))
        ->assertRedirect(route('admin.orders.show', $order));

    assertDatabaseHas('order_transactions', [
        'order_id' => $order->id,
        'type' => TransactionType::Void->value,
        'status' => TransactionStatus::Success->value,
        'amount' => '100.0000',
        'is_manual_entry' => true,
    ]);

    assertDatabaseHas('order_activities', [
        'order_id' => $order->id,
        'type' => 'payment_voided',
    ]);

    $order->refresh();
    expect($order->payment_status)->toBe(PaymentStatus::Unpaid)
        ->and($order->paid_total)->toBe('0.0000')
        ->and($order->balance_due_total)->toBe('100.0000');
});

test('cannot void order with non-manual payment gateway', function () {
    $gateway = PaymentGateway::factory()->stripe()->active()->create();
    $order = Order::factory()->inProgress()->create([
        'payment_gateway_id' => $gateway->id,
        'paid_total' => '100.0000',
    ]);

    PaymentManager::fake();

    actingAsSuperAdmin()->post(route('admin.orders.void-payment', $order))
        ->assertStatus(422);
});

test('cannot void order without payment gateway', function () {
    $order = Order::factory()->inProgress()->create([
        'payment_gateway_id' => null,
    ]);

    actingAsSuperAdmin()->post(route('admin.orders.void-payment', $order))
        ->assertStatus(422);
});

test('cannot void order with no recorded payments', function () {
    $gateway = PaymentGateway::factory()->cod()->active()->create();
    $order = Order::factory()->unfulfilled()->create([
        'payment_gateway_id' => $gateway->id,
        'paid_total' => '0.0000',
    ]);

    PaymentManager::fake(new CodDriver());

    actingAsSuperAdmin()->post(route('admin.orders.void-payment', $order))
        ->assertStatus(422);
});

test('requires authentication', function () {
    $order = Order::factory()->create();

    post(route('admin.orders.void-payment', $order))
        ->assertRedirect(route('admin.login'));
});

test('requires orders.update permission', function () {
    $gateway = PaymentGateway::factory()->cod()->active()->create();
    $order = Order::factory()->inProgress()->create([
        'payment_gateway_id' => $gateway->id,
        'paid_total' => '100.0000',
    ]);

    PaymentManager::fake(new CodDriver());

    $role = Role::query()->where(['name' => RoleEnum::Admin])->firstOrFail();
    $role->revokePermissionTo(Permission::OrdersManage);

    actingAsAdmin()->post(route('admin.orders.void-payment', $order))
        ->assertForbidden();
});
