<?php

declare(strict_types=1);

use App\Actions\CancelOrderAction;
use App\Enums\OrderActivityType;
use App\Enums\PaymentStatus;
use App\Enums\Permission;
use App\Enums\Role as RoleEnum;
use App\Http\Controllers\Admin\CancelOrderController;
use App\Http\Requests\Admin\CancelOrderRequest;
use App\Models\Order;
use App\Models\OrderActivity;
use App\Models\OrderItem;
use App\Models\PaymentGateway;
use App\Models\Product;
use App\Payment\Drivers\CodDriver;
use App\Payment\PaymentManager;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\post;

covers(CancelOrderController::class, CancelOrderRequest::class, CancelOrderAction::class);

uses()->group('orders');

beforeEach(function () {
    PaymentManager::fake();
});

test('unfulfilled unpaid order can be canceled', function () {
    $order = Order::factory()->unfulfilled()->create();

    $response = actingAsSuperAdmin()
        ->withoutExceptionHandling()
        ->post(route('admin.orders.cancel', $order), [
            'reason' => 'customer_request',
        ]);

    $response->assertRedirect(route('admin.orders.show', $order));

    $order->refresh();
    expect($order->canceled_at)->not->toBeNull()
        ->and($order->payment_status)->toBe(PaymentStatus::Canceled);

    assertDatabaseHas('order_activities', [
        'order_id' => $order->id,
        'type' => 'order_canceled',
    ]);
});

test('cancel returns with errors when gateway refund fails', function () {
    $failingDriver = new class() implements App\Payment\Contracts\PaymentDriver
    {
        public function verifyPayment(?string $gatewayReference, PaymentStatus $currentStatus): App\Payment\DTOs\VerificationResult
        {
            return new App\Payment\DTOs\VerificationResult(status: $currentStatus);
        }

        public function createSession(App\Payment\DTOs\CreateSession $session): App\Payment\DTOs\SessionResult
        {
            return new App\Payment\DTOs\SessionResult(status: PaymentStatus::Paid, redirectUrl: '');
        }

        public function refund(App\Payment\DTOs\RefundPayment $refund): App\Payment\DTOs\RefundResult
        {
            throw new App\Exceptions\GatewayRefundFailedException('Refund provider declined the request.');
        }

        public function verifyWebhook(Illuminate\Http\Request $request): bool
        {
            return true;
        }

        public function parseWebhook(Illuminate\Http\Request $request): App\Payment\DTOs\WebhookEvent
        {
            return new App\Payment\DTOs\WebhookEvent(type: 'noop');
        }

        public function testConnection(): bool
        {
            return true;
        }

        public function supportsRefunds(): bool
        {
            return true;
        }

        public function isManual(): bool
        {
            return false;
        }
    };

    PaymentManager::fake($failingDriver);

    $gateway = PaymentGateway::factory()->stripe()->active()->create();
    $product = Product::factory()->create(['track_stock' => false]);
    $order = Order::factory()->inProgress()->create([
        'payment_gateway_id' => $gateway->id,
        'total' => '100.0000',
        'subtotal' => '100.0000',
        'paid_total' => '100.0000',
        'net_paid_total' => '100.0000',
        'refund_total' => '0.0000',
        'payment_status' => PaymentStatus::Paid,
    ]);

    OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'quantity' => 1,
        'unit_price' => '100.0000',
        'total_price' => '100.0000',
    ]);

    App\Models\OrderTransaction::factory()->sale()->successful()->create([
        'order_id' => $order->id,
        'amount' => '100.0000',
        'gateway_reference' => 'pi_refund_fail_123',
    ]);

    $response = actingAsSuperAdmin()->post(route('admin.orders.cancel', $order), [
        'reason' => 'fraudulent',
        'refund' => true,
        'restock' => false,
        'notify_customer' => false,
    ]);

    $response->assertRedirectBack()->assertSessionHasErrors('gateway');
});

test('in-progress paid order can be canceled with refund', function () {
    $gateway = PaymentGateway::factory()->cod()->active()->create();
    $product = Product::factory()->create(['track_stock' => true, 'stock' => 5]);
    $order = Order::factory()->inProgress()->create([
        'payment_gateway_id' => $gateway->id,
        'total' => '100.0000',
        'subtotal' => '100.0000',
        'shipping_total' => '0.0000',
        'tax_total' => '0.0000',
        'discount_total' => '0.0000',
        'refund_total' => '0.0000',
    ]);

    OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'quantity' => 2,
        'unit_price' => '50.0000',
        'total_price' => '100.0000',
        'tax_amount' => '0.0000',
    ]);

    PaymentManager::fake(new CodDriver());

    $response = actingAsSuperAdmin()
        ->withoutExceptionHandling()
        ->post(route('admin.orders.cancel', $order), [
            'reason' => 'fraudulent',
            'refund' => true,
            'restock' => true,
            'notify_customer' => true,
        ]);

    $response->assertRedirect(route('admin.orders.show', $order));

    $order->refresh();
    expect($order->canceled_at)->not->toBeNull();

    assertDatabaseHas('order_refunds', [
        'order_id' => $order->id,
        'status' => 'completed',
    ]);

    $product->refresh();
    expect($product->stock)->toBe(7);
});

test('order can be canceled without refund', function () {
    $order = Order::factory()->inProgress()->create([
        'total' => '100.0000',
        'refund_total' => '0.0000',
    ]);

    $response = actingAsSuperAdmin()
        ->withoutExceptionHandling()
        ->post(route('admin.orders.cancel', $order), [
            'reason' => 'inventory',
            'refund' => false,
            'restock' => false,
            'notify_customer' => false,
        ]);

    $response->assertRedirect(route('admin.orders.show', $order));

    $order->refresh();
    expect($order->canceled_at)->not->toBeNull()
        ->and($order->payment_status)->toBe(PaymentStatus::Paid);
});

test('already canceled order cannot be canceled again', function () {
    $order = Order::factory()->canceled()->create();

    actingAsSuperAdmin()
        ->post(route('admin.orders.cancel', $order), [
            'reason' => 'customer_request',
        ])
        ->assertStatus(409);
});

test('fulfilled order cannot be canceled', function () {
    $order = Order::factory()->fulfilled()->create();

    actingAsSuperAdmin()
        ->post(route('admin.orders.cancel', $order), [
            'reason' => 'customer_request',
        ])
        ->assertStatus(409);
});

test('reason is required', function () {
    $order = Order::factory()->unfulfilled()->create();

    actingAsSuperAdmin()
        ->post(route('admin.orders.cancel', $order), [])
        ->assertSessionHasErrors('reason');
});

test('reason must be a valid cancellation reason', function () {
    $order = Order::factory()->unfulfilled()->create();

    actingAsSuperAdmin()
        ->post(route('admin.orders.cancel', $order), [
            'reason' => 'invalid_reason',
        ])
        ->assertSessionHasErrors('reason');
});

test('reason note is optional and limited to 500 characters', function () {
    $order = Order::factory()->unfulfilled()->create();

    actingAsSuperAdmin()
        ->post(route('admin.orders.cancel', $order), [
            'reason' => 'other',
            'reason_note' => str_repeat('a', 501),
        ])
        ->assertSessionHasErrors('reason_note');
});

test('cancellation with reason note includes it in activity', function () {
    $order = Order::factory()->unfulfilled()->create();

    actingAsSuperAdmin()
        ->withoutExceptionHandling()
        ->post(route('admin.orders.cancel', $order), [
            'reason' => 'other',
            'reason_note' => 'Customer changed their mind',
        ]);

    $activity = OrderActivity::query()
        ->where('order_id', $order->id)
        ->where('type', OrderActivityType::OrderCanceled->value)
        ->first();

    expect($activity)->not->toBeNull()
        ->and($activity->metadata['cancellation_reason'])->toBe('other')
        ->and($activity->metadata['cancellation_note'])->toBe('Customer changed their mind');
});

test('coupon usage is decremented on cancellation', function () {
    $coupon = App\Models\Coupon::factory()->create(['used_count' => 5]);
    $order = Order::factory()->unfulfilled()->create([
        'coupon_id' => $coupon->id,
        'coupon_code' => $coupon->code,
    ]);

    actingAsSuperAdmin()
        ->withoutExceptionHandling()
        ->post(route('admin.orders.cancel', $order), [
            'reason' => 'customer_request',
        ]);

    $coupon->refresh();
    expect($coupon->used_count)->toBe(4);
});

test('restock without refund restocks items', function () {
    $product = Product::factory()->create(['track_stock' => true, 'stock' => 3]);
    $order = Order::factory()->unfulfilled()->create();
    OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'quantity' => 2,
        'unit_price' => '50.0000',
        'total_price' => '100.0000',
        'tax_amount' => '0.0000',
    ]);

    actingAsSuperAdmin()
        ->withoutExceptionHandling()
        ->post(route('admin.orders.cancel', $order), [
            'reason' => 'inventory',
            'restock' => true,
        ]);

    $product->refresh();
    expect($product->stock)->toBe(5);
});

test('requires authentication', function () {
    $order = Order::factory()->create();

    post(route('admin.orders.cancel', $order))
        ->assertRedirect(route('admin.login'));
});

test('requires orders.cancel permission', function () {
    $order = Order::factory()->unfulfilled()->create();

    $role = Role::query()->where(['name' => RoleEnum::Admin])->firstOrFail();
    $role->revokePermissionTo(Permission::OrdersCancel);

    actingAsAdmin()
        ->post(route('admin.orders.cancel', $order), [
            'reason' => 'customer_request',
        ])
        ->assertForbidden();
});
