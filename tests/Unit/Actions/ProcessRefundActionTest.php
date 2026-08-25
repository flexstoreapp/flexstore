<?php

declare(strict_types=1);

use App\Actions\ProcessRefundAction;
use App\DTOs\ProcessRefundInput;
use App\Enums\FulfillmentStatus;
use App\Enums\PaymentStatus;
use App\Enums\RefundStatus;
use App\Enums\ReturnStatus;
use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Exceptions\GatewayRefundFailedException;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderRefund;
use App\Models\OrderReturn;
use App\Models\OrderReturnItem;
use App\Models\OrderShipment;
use App\Models\OrderShipmentItem;
use App\Models\OrderTransaction;
use App\Models\PaymentGateway;
use App\Models\User;
use App\Payment\Contracts\PaymentDriver;
use App\Payment\Drivers\CodDriver;
use App\Payment\DTOs\RefundResult;
use App\Payment\PaymentManager;

covers(ProcessRefundAction::class, ProcessRefundInput::class);

uses()->group('actions', 'refund');

function fakeDriver(RefundResult|Closure $response, bool $supportsRefunds = true, ?Closure $onRefund = null): PaymentDriver
{
    return new class($response, $supportsRefunds, $onRefund) implements PaymentDriver
    {
        private int $callCount = 0;

        public function __construct(
            private RefundResult|Closure $response,
            private bool $supportsRefunds,
            private ?Closure $onRefund,
        ) {
        }

        public function createSession(App\Payment\DTOs\CreateSession $session): App\Payment\DTOs\SessionResult
        {
            throw new RuntimeException('Not implemented');
        }

        public function verifyPayment(?string $gatewayReference, PaymentStatus $currentStatus): App\Payment\DTOs\VerificationResult
        {
            throw new RuntimeException('Not implemented');
        }

        public function refund(App\Payment\DTOs\RefundPayment $refund): RefundResult
        {
            if ($this->onRefund) {
                ($this->onRefund)($refund);
            }

            if ($this->response instanceof Closure) {
                return ($this->response)($refund, $this->callCount++);
            }

            $this->callCount++;

            return $this->response;
        }

        public function verifyWebhook(Illuminate\Http\Request $request): bool
        {
            return false;
        }

        public function parseWebhook(Illuminate\Http\Request $request): App\Payment\DTOs\WebhookEvent
        {
            throw new RuntimeException('Not implemented');
        }

        public function testConnection(): bool
        {
            return true;
        }

        public function supportsRefunds(): bool
        {
            return $this->supportsRefunds;
        }

        public function isManual(): bool
        {
            return false;
        }
    };
}

function createRefundableOrder(array $orderAttributes = []): array
{
    $order = Order::factory()->create(array_merge([
        'fulfillment_status' => FulfillmentStatus::Fulfilled,
        'payment_status' => PaymentStatus::Paid,
        'subtotal' => '100.0000',
        'tax_total' => '0.0000',
        'shipping_total' => '5.0000',
        'discount_total' => '0.0000',
        'total' => '105.0000',
        'refund_total' => '0.0000',
    ], $orderAttributes));

    $orderItem = OrderItem::factory()->create([
        'order_id' => $order->id,
        'quantity' => 5,
        'unit_price' => '10.0000',
        'total_price' => '50.0000',
        'tax_amount' => '0.0000',
    ]);

    $user = User::factory()->create();

    $data = [
        'reason' => 'Customer request',
        'items' => [
            [
                'order_item_id' => $orderItem->id,
                'quantity' => 2,
            ],
        ],
    ];

    return [$user, $order, $data, $orderItem];
}

test('marks refund as completed when order has no payment gateway', function () {
    [$user, $order, $data] = createRefundableOrder(['payment_gateway_id' => null]);

    PaymentManager::fake(new CodDriver());

    $refund = app(ProcessRefundAction::class)->handle($user, $order, ProcessRefundInput::fromArray($data));

    expect($refund->status)->toBe(RefundStatus::Completed);

    $order->refresh();
    expect($order->refund_total)->toBe('20.0000')
        ->and($order->payment_status)->toBe(PaymentStatus::PartiallyRefunded);
});

test('marks refund as completed when driver does not support refunds', function () {
    $gateway = PaymentGateway::factory()->cod()->active()->create();
    [$user, $order, $data] = createRefundableOrder(['payment_gateway_id' => $gateway->id]);

    PaymentManager::fake(new CodDriver());

    $refund = app(ProcessRefundAction::class)->handle($user, $order, ProcessRefundInput::fromArray($data));

    expect($refund->status)->toBe(RefundStatus::Completed);

    $order->refresh();
    expect($order->refund_total)->toBe('20.0000');
});

test('processes refund through gateway driver on success', function () {
    $gateway = PaymentGateway::factory()->stripe()->active()->create();
    [$user, $order, $data] = createRefundableOrder([
        'payment_gateway_id' => $gateway->id,
    ]);
    OrderTransaction::factory()->sale()->successful()->create([
        'order_id' => $order->id,
        'amount' => $order->total,
        'gateway_reference' => 'pi_test_123',
    ]);

    $driver = fakeDriver(new RefundResult(
        status: RefundStatus::Completed,
        amount: '20.0000',
        gatewayReference: 're_stripe_123',
    ), onRefund: function (App\Payment\DTOs\RefundPayment $refund) use ($order) {
        expect($refund->currencyCode)->toBe($order->currency_code)
            ->and($refund->amount)->toBe('20.0000')
            ->and($refund->gatewayReference)->toBe('pi_test_123');
    });

    PaymentManager::fake($driver);

    $refund = app(ProcessRefundAction::class)->handle($user, $order, ProcessRefundInput::fromArray($data));

    expect($refund->status)->toBe(RefundStatus::Completed);

    $order->refresh();
    expect($order->refund_total)->toBe('20.0000')
        ->and($order->payment_status)->toBe(PaymentStatus::PartiallyRefunded);
});

test('throws exception and deletes refund when gateway returns failure', function () {
    $gateway = PaymentGateway::factory()->stripe()->active()->create();
    [$user, $order, $data] = createRefundableOrder([
        'payment_gateway_id' => $gateway->id,
    ]);
    OrderTransaction::factory()->sale()->successful()->create([
        'order_id' => $order->id,
        'amount' => $order->total,
        'gateway_reference' => 'pi_test_456',
    ]);

    PaymentManager::fake(fakeDriver(new RefundResult(
        status: RefundStatus::Failed,
        amount: '20.0000',
        failureReason: 'Insufficient funds on charge',
    )));

    expect(fn () => app(ProcessRefundAction::class)->handle($user, $order, ProcessRefundInput::fromArray($data)))
        ->toThrow(GatewayRefundFailedException::class, 'Insufficient funds on charge');

    expect(OrderRefund::query()->where('order_id', $order->id)->count())->toBe(0);

    $order->refresh();
    expect($order->payment_status)->toBe(PaymentStatus::Paid)
        ->and($order->refund_total)->toBe('0.0000');
});

test('throws exception with fallback message when gateway returns failure without reason', function () {
    $gateway = PaymentGateway::factory()->stripe()->active()->create();
    [$user, $order, $data] = createRefundableOrder([
        'payment_gateway_id' => $gateway->id,
    ]);
    OrderTransaction::factory()->sale()->successful()->create([
        'order_id' => $order->id,
        'amount' => $order->total,
        'gateway_reference' => 'pi_test_789',
    ]);

    PaymentManager::fake(fakeDriver(new RefundResult(
        status: RefundStatus::Failed,
        amount: '20.0000',
        failureReason: null,
    )));

    expect(fn () => app(ProcessRefundAction::class)->handle($user, $order, ProcessRefundInput::fromArray($data)))
        ->toThrow(GatewayRefundFailedException::class);
});

test('keeps refund as pending when gateway returns pending status', function () {
    $gateway = PaymentGateway::factory()->stripe()->active()->create();
    [$user, $order, $data] = createRefundableOrder([
        'payment_gateway_id' => $gateway->id,
    ]);
    OrderTransaction::factory()->sale()->successful()->create([
        'order_id' => $order->id,
        'amount' => $order->total,
        'gateway_reference' => 'pi_test_pending',
    ]);

    PaymentManager::fake(fakeDriver(new RefundResult(
        status: RefundStatus::Pending,
        amount: '20.0000',
        gatewayReference: 're_pending_123',
    )));

    $refund = app(ProcessRefundAction::class)->handle($user, $order, ProcessRefundInput::fromArray($data));

    expect($refund->status)->toBe(RefundStatus::Pending);

    $order->refresh();
    expect($order->refund_total)->toBe('0.0000')
        ->and($order->payment_status)->toBe(PaymentStatus::Paid)
        ->and($order->fulfillment_status)->toBe(FulfillmentStatus::Fulfilled);
});

test('creates pending transaction when gateway returns unpaid status', function () {
    $gateway = PaymentGateway::factory()->stripe()->active()->create();
    [$user, $order, $data] = createRefundableOrder([
        'payment_gateway_id' => $gateway->id,
    ]);
    OrderTransaction::factory()->sale()->successful()->create([
        'order_id' => $order->id,
        'amount' => $order->total,
        'gateway_reference' => 'pi_test_pending_tx',
    ]);

    PaymentManager::fake(fakeDriver(new RefundResult(
        status: RefundStatus::Pending,
        amount: '20.0000',
        gatewayReference: 're_pending_tx_123',
    )));

    $refund = app(ProcessRefundAction::class)->handle($user, $order, ProcessRefundInput::fromArray($data));

    $transaction = OrderTransaction::query()->where('order_refund_id', $refund->id)->sole();

    expect($transaction)
        ->type->toBe(TransactionType::Refund)
        ->status->toBe(TransactionStatus::Pending)
        ->gateway_reference->toBe('re_pending_tx_123');
});

test('does not create transaction when order has no payment gateway', function () {
    [$user, $order, $data] = createRefundableOrder(['payment_gateway_id' => null]);

    PaymentManager::fake(new CodDriver());

    $refund = app(ProcessRefundAction::class)->handle($user, $order, ProcessRefundInput::fromArray($data));

    expect(OrderTransaction::query()->where('order_refund_id', $refund->id)->exists())->toBeFalse();
});

test('does not create transaction for manual gateway refund', function () {
    $gateway = PaymentGateway::factory()->cod()->active()->create();
    [$user, $order, $data] = createRefundableOrder(['payment_gateway_id' => $gateway->id]);

    PaymentManager::fake(new CodDriver());

    $refund = app(ProcessRefundAction::class)->handle($user, $order, ProcessRefundInput::fromArray($data));

    expect(OrderTransaction::query()->where('order_refund_id', $refund->id)->exists())->toBeFalse();
});

test('creates success transaction when gateway processes refund', function () {
    $gateway = PaymentGateway::factory()->stripe()->active()->create();
    [$user, $order, $data] = createRefundableOrder(['payment_gateway_id' => $gateway->id]);
    OrderTransaction::factory()->sale()->successful()->create([
        'order_id' => $order->id,
        'amount' => $order->total,
        'gateway_reference' => 'pi_test_tx_success',
    ]);

    PaymentManager::fake(fakeDriver(new RefundResult(
        status: RefundStatus::Completed,
        amount: '20.0000',
        gatewayReference: 're_tx_success',
    )));

    $refund = app(ProcessRefundAction::class)->handle($user, $order, ProcessRefundInput::fromArray($data));

    $transaction = OrderTransaction::query()->where('order_refund_id', $refund->id)->sole();

    expect($transaction)
        ->type->toBe(TransactionType::Refund)
        ->status->toBe(TransactionStatus::Success)
        ->amount->toBe('20.0000')
        ->gateway_reference->toBe('re_tx_success');
});

test('does not change fulfillment status on partial item refund for unshipped order', function () {
    [$user, $order, $data] = createRefundableOrder([
        'payment_gateway_id' => null,
        'fulfillment_status' => FulfillmentStatus::Unfulfilled,
    ]);

    PaymentManager::fake(new CodDriver());

    app(ProcessRefundAction::class)->handle($user, $order, ProcessRefundInput::fromArray($data));

    $order->refresh();
    expect($order->fulfillment_status)->toBe(FulfillmentStatus::Unfulfilled);
});

test('transitions to fulfilled when refunding remaining unfulfilled items', function () {
    $user = User::factory()->create();
    $order = Order::factory()->create([
        'fulfillment_status' => FulfillmentStatus::InProgress,
        'payment_status' => PaymentStatus::Paid,
        'subtotal' => '50.0000',
        'tax_total' => '0.0000',
        'shipping_total' => '0.0000',
        'discount_total' => '0.0000',
        'total' => '50.0000',
        'refund_total' => '0.0000',
        'payment_gateway_id' => null,
    ]);

    $itemA = OrderItem::factory()->create([
        'order_id' => $order->id,
        'quantity' => 1,
        'unit_price' => '25.0000',
        'total_price' => '25.0000',
    ]);

    $itemB = OrderItem::factory()->create([
        'order_id' => $order->id,
        'quantity' => 1,
        'unit_price' => '25.0000',
        'total_price' => '25.0000',
    ]);

    $shipment = OrderShipment::query()->create([
        'order_id' => $order->id,
        'user_id' => $user->id,
        'shipped_at' => now(),
    ]);

    OrderShipmentItem::query()->create([
        'order_shipment_id' => $shipment->id,
        'order_item_id' => $itemA->id,
        'quantity' => 1,
    ]);

    PaymentManager::fake(new CodDriver());

    app(ProcessRefundAction::class)->handle($user, $order, ProcessRefundInput::fromArray([
        'reason' => 'Customer request',
        'items' => [
            ['order_item_id' => $itemB->id, 'quantity' => 1],
        ],
    ]));

    $order->refresh();
    expect($order->fulfillment_status)->toBe(FulfillmentStatus::Fulfilled);
});

test('transitions to fulfilled when fulfilling remaining items after refund', function () {
    $user = User::factory()->create();
    $order = Order::factory()->create([
        'fulfillment_status' => FulfillmentStatus::Unfulfilled,
        'payment_status' => PaymentStatus::Paid,
        'subtotal' => '50.0000',
        'tax_total' => '0.0000',
        'shipping_total' => '0.0000',
        'discount_total' => '0.0000',
        'total' => '50.0000',
        'refund_total' => '0.0000',
        'payment_gateway_id' => null,
    ]);

    $itemA = OrderItem::factory()->create([
        'order_id' => $order->id,
        'quantity' => 1,
        'unit_price' => '25.0000',
        'total_price' => '25.0000',
    ]);

    $itemB = OrderItem::factory()->create([
        'order_id' => $order->id,
        'quantity' => 1,
        'unit_price' => '25.0000',
        'total_price' => '25.0000',
    ]);

    PaymentManager::fake(new CodDriver());

    app(ProcessRefundAction::class)->handle($user, $order, ProcessRefundInput::fromArray([
        'reason' => 'Customer request',
        'items' => [
            ['order_item_id' => $itemA->id, 'quantity' => 1],
        ],
    ]));

    $order->refresh();
    expect($order->fulfillment_status)->toBe(FulfillmentStatus::Unfulfilled);

    app(App\Actions\StoreOrderShipmentAction::class)->handle($user, $order, App\DTOs\StoreOrderShipmentInput::fromArray([
        'items' => [
            ['order_item_id' => $itemB->id, 'quantity' => 1],
        ],
    ]));

    $order->refresh();
    expect($order->fulfillment_status)->toBe(FulfillmentStatus::Fulfilled);
});

test('does not transition to fulfilled when all items are refunded without any shipments', function () {
    $user = User::factory()->create();
    $order = Order::factory()->create([
        'fulfillment_status' => FulfillmentStatus::Unfulfilled,
        'payment_status' => PaymentStatus::Paid,
        'subtotal' => '50.0000',
        'tax_total' => '0.0000',
        'shipping_total' => '0.0000',
        'discount_total' => '0.0000',
        'total' => '50.0000',
        'refund_total' => '0.0000',
        'payment_gateway_id' => null,
    ]);

    $itemA = OrderItem::factory()->create([
        'order_id' => $order->id,
        'quantity' => 1,
        'unit_price' => '25.0000',
        'total_price' => '25.0000',
    ]);

    $itemB = OrderItem::factory()->create([
        'order_id' => $order->id,
        'quantity' => 1,
        'unit_price' => '25.0000',
        'total_price' => '25.0000',
    ]);

    PaymentManager::fake(new CodDriver());

    app(ProcessRefundAction::class)->handle($user, $order, ProcessRefundInput::fromArray([
        'reason' => 'Customer request',
        'items' => [
            ['order_item_id' => $itemA->id, 'quantity' => 1],
            ['order_item_id' => $itemB->id, 'quantity' => 1],
        ],
    ]));

    $order->refresh();
    expect($order->fulfillment_status)->toBe(FulfillmentStatus::Unfulfilled);
});

test('does not update fulfillment status for shipping-only refund', function () {
    [$user, $order] = createRefundableOrder(['payment_gateway_id' => null]);

    PaymentManager::fake(new CodDriver());

    $refund = app(ProcessRefundAction::class)->handle($user, $order, ProcessRefundInput::fromArray([
        'reason' => 'Shipping overcharge',
        'shipping_amount' => '5.0000',
    ]));

    $order->refresh();
    expect($order->fulfillment_status)->toBe(FulfillmentStatus::Fulfilled)
        ->and($refund->status)->toBe(RefundStatus::Completed);
});

test('transitions to refunded when full amount is refunded', function () {
    $order = Order::factory()->create([
        'fulfillment_status' => FulfillmentStatus::Fulfilled,
        'payment_status' => PaymentStatus::Paid,
        'subtotal' => '50.0000',
        'tax_total' => '0.0000',
        'shipping_total' => '0.0000',
        'discount_total' => '0.0000',
        'total' => '50.0000',
        'refund_total' => '0.0000',
    ]);

    $orderItem = OrderItem::factory()->create([
        'order_id' => $order->id,
        'quantity' => 5,
        'unit_price' => '10.0000',
        'total_price' => '50.0000',
        'tax_amount' => '0.0000',
    ]);

    $user = User::factory()->create();

    PaymentManager::fake(new CodDriver());

    app(ProcessRefundAction::class)->handle($user, $order, ProcessRefundInput::fromArray([
        'reason' => 'Full refund',
        'items' => [['order_item_id' => $orderItem->id, 'quantity' => 5]],
    ]));

    $order->refresh();
    expect($order->payment_status)->toBe(PaymentStatus::Refunded)
        ->and($order->refund_total)->toBe('50.0000');
});

test('records refund completed activity', function () {
    [$user, $order, $data] = createRefundableOrder(['payment_gateway_id' => null]);

    PaymentManager::fake(new CodDriver());

    $refund = app(ProcessRefundAction::class)->handle($user, $order, ProcessRefundInput::fromArray($data));

    $activity = $order->activities()->where('type', 'refund_completed')->first();

    expect($activity)->not->toBeNull()
        ->and($activity->metadata['refund_id'])->toBe($refund->id);
});

test('records refund pending activity when gateway returns pending status', function () {
    $gateway = PaymentGateway::factory()->stripe()->active()->create();
    [$user, $order, $data] = createRefundableOrder([
        'payment_gateway_id' => $gateway->id,
    ]);
    OrderTransaction::factory()->sale()->successful()->create([
        'order_id' => $order->id,
        'amount' => $order->total,
        'gateway_reference' => 'pi_test_activity_pending',
    ]);

    PaymentManager::fake(fakeDriver(new RefundResult(
        status: RefundStatus::Pending,
        amount: '20.0000',
        gatewayReference: 're_pending_activity',
    )));

    $refund = app(ProcessRefundAction::class)->handle($user, $order, ProcessRefundInput::fromArray($data));

    $activity = $order->activities()->where('type', 'refund_pending')->first();

    expect($activity)->not->toBeNull()
        ->and($activity->metadata['refund_id'])->toBe($refund->id)
        ->and($activity->user_id)->toBe($user->id);
});

test('record_only skips gateway and completes refund', function () {
    $gateway = PaymentGateway::factory()->stripe()->active()->create();
    [$user, $order, $data] = createRefundableOrder([
        'payment_gateway_id' => $gateway->id,
    ]);

    $called = false;
    PaymentManager::fake(fakeDriver(new RefundResult(
        status: RefundStatus::Completed,
        amount: '20.0000',
    ), onRefund: function () use (&$called) {
        $called = true;
    }));

    $data['refund_method'] = 'record_only';
    $refund = app(ProcessRefundAction::class)->handle($user, $order, ProcessRefundInput::fromArray($data));

    expect($refund->status)->toBe(RefundStatus::Completed)
        ->and($called)->toBeFalse();

    $order->refresh();
    expect($order->refund_total)->toBe('20.0000');
});

test('attempts all allocations even when one fails', function () {
    $gateway = PaymentGateway::factory()->stripe()->active()->create();
    $order = Order::factory()->create([
        'fulfillment_status' => FulfillmentStatus::Fulfilled,
        'payment_status' => PaymentStatus::Paid,
        'payment_gateway_id' => $gateway->id,
        'subtotal' => '100.0000',
        'tax_total' => '0.0000',
        'shipping_total' => '0.0000',
        'discount_total' => '0.0000',
        'total' => '100.0000',
        'refund_total' => '0.0000',
    ]);
    OrderTransaction::factory()->sale()->successful()->create([
        'order_id' => $order->id,
        'amount' => '60.0000',
        'gateway_reference' => 'pi_first',
    ]);
    OrderTransaction::factory()->sale()->successful()->create([
        'order_id' => $order->id,
        'amount' => '40.0000',
        'gateway_reference' => 'pi_second',
    ]);
    $orderItem = OrderItem::factory()->create([
        'order_id' => $order->id,
        'quantity' => 10,
        'unit_price' => '10.0000',
        'total_price' => '100.0000',
        'tax_amount' => '0.0000',
    ]);
    $user = User::factory()->create();

    $refundCalls = [];
    $driver = fakeDriver(function (App\Payment\DTOs\RefundPayment $refund, int $index) use (&$refundCalls) {
        $refundCalls[] = $refund->gatewayReference;

        if ($index === 0) {
            return new RefundResult(
                status: RefundStatus::Failed,
                amount: $refund->amount,
                failureReason: 'Charge expired',
            );
        }

        return new RefundResult(
            status: RefundStatus::Completed,
            amount: $refund->amount,
            gatewayReference: 're_second_ok',
        );
    });

    PaymentManager::fake($driver);

    $refund = app(ProcessRefundAction::class)->handle($user, $order, ProcessRefundInput::fromArray([
        'reason' => 'Test partial failure',
        'items' => [['order_item_id' => $orderItem->id, 'quantity' => 10]],
    ]));

    expect($refundCalls)->toHaveCount(2)
        ->and($refund->status)->toBe(RefundStatus::Pending);
});

test('records transactions for successful allocations when others fail', function () {
    $gateway = PaymentGateway::factory()->stripe()->active()->create();
    $order = Order::factory()->create([
        'fulfillment_status' => FulfillmentStatus::Fulfilled,
        'payment_status' => PaymentStatus::Paid,
        'payment_gateway_id' => $gateway->id,
        'subtotal' => '100.0000',
        'tax_total' => '0.0000',
        'shipping_total' => '0.0000',
        'discount_total' => '0.0000',
        'total' => '100.0000',
        'refund_total' => '0.0000',
    ]);
    $saleTx1 = OrderTransaction::factory()->sale()->successful()->create([
        'order_id' => $order->id,
        'amount' => '60.0000',
        'gateway_reference' => 'pi_first',
    ]);
    $saleTx2 = OrderTransaction::factory()->sale()->successful()->create([
        'order_id' => $order->id,
        'amount' => '40.0000',
        'gateway_reference' => 'pi_second',
    ]);
    $orderItem = OrderItem::factory()->create([
        'order_id' => $order->id,
        'quantity' => 10,
        'unit_price' => '10.0000',
        'total_price' => '100.0000',
        'tax_amount' => '0.0000',
    ]);
    $user = User::factory()->create();

    $driver = fakeDriver(function (App\Payment\DTOs\RefundPayment $refund, int $index) {
        if ($index === 0) {
            return new RefundResult(
                status: RefundStatus::Completed,
                amount: $refund->amount,
                gatewayReference: 're_first_ok',
            );
        }

        return new RefundResult(
            status: RefundStatus::Failed,
            amount: $refund->amount,
            failureReason: 'Insufficient balance',
        );
    });

    PaymentManager::fake($driver);

    $refund = app(ProcessRefundAction::class)->handle($user, $order, ProcessRefundInput::fromArray([
        'reason' => 'Test partial failure',
        'items' => [['order_item_id' => $orderItem->id, 'quantity' => 10]],
    ]));

    $refundTransactions = OrderTransaction::query()
        ->where('order_refund_id', $refund->id)
        ->get();

    expect($refundTransactions)->toHaveCount(1)
        ->and($refundTransactions->first())
        ->status->toBe(TransactionStatus::Pending)
        ->gateway_reference->toBe('re_first_ok')
        ->related_transaction_id->not->toBeNull();
});

test('treats gateway exception as failed result instead of crashing', function () {
    $gateway = PaymentGateway::factory()->stripe()->active()->create();
    [$user, $order, $data] = createRefundableOrder([
        'payment_gateway_id' => $gateway->id,
    ]);
    OrderTransaction::factory()->sale()->successful()->create([
        'order_id' => $order->id,
        'amount' => $order->total,
        'gateway_reference' => 'pi_test_exception',
    ]);

    $driver = fakeDriver(function () {
        throw new RuntimeException('Network timeout');
    });

    PaymentManager::fake($driver);

    expect(fn () => app(ProcessRefundAction::class)->handle($user, $order, ProcessRefundInput::fromArray($data)))
        ->toThrow(GatewayRefundFailedException::class, 'Network timeout');

    expect(OrderRefund::query()->where('order_id', $order->id)->count())->toBe(0);
});

test('catches exception on second allocation and treats as partial failure', function () {
    $gateway = PaymentGateway::factory()->stripe()->active()->create();
    $order = Order::factory()->create([
        'fulfillment_status' => FulfillmentStatus::Fulfilled,
        'payment_status' => PaymentStatus::Paid,
        'payment_gateway_id' => $gateway->id,
        'subtotal' => '100.0000',
        'tax_total' => '0.0000',
        'shipping_total' => '0.0000',
        'discount_total' => '0.0000',
        'total' => '100.0000',
        'refund_total' => '0.0000',
    ]);
    OrderTransaction::factory()->sale()->successful()->create([
        'order_id' => $order->id,
        'amount' => '60.0000',
        'gateway_reference' => 'pi_first',
    ]);
    OrderTransaction::factory()->sale()->successful()->create([
        'order_id' => $order->id,
        'amount' => '40.0000',
        'gateway_reference' => 'pi_second',
    ]);
    $orderItem = OrderItem::factory()->create([
        'order_id' => $order->id,
        'quantity' => 10,
        'unit_price' => '10.0000',
        'total_price' => '100.0000',
        'tax_amount' => '0.0000',
    ]);
    $user = User::factory()->create();

    $driver = fakeDriver(function (App\Payment\DTOs\RefundPayment $refund, int $index) {
        if ($index === 0) {
            return new RefundResult(
                status: RefundStatus::Completed,
                amount: $refund->amount,
                gatewayReference: 're_first_ok',
            );
        }

        throw new RuntimeException('Gateway connection lost');
    });

    PaymentManager::fake($driver);

    $refund = app(ProcessRefundAction::class)->handle($user, $order, ProcessRefundInput::fromArray([
        'reason' => 'Test exception on second allocation',
        'items' => [['order_item_id' => $orderItem->id, 'quantity' => 10]],
    ]));

    expect($refund->status)->toBe(RefundStatus::Pending);

    $refundTransactions = OrderTransaction::query()
        ->where('order_refund_id', $refund->id)
        ->get();

    expect($refundTransactions)->toHaveCount(1)
        ->and($refundTransactions->first()->gateway_reference)->toBe('re_first_ok');
});

test('does not restock items when restock is false', function () {
    $order = Order::factory()->create([
        'fulfillment_status' => FulfillmentStatus::Fulfilled,
        'payment_status' => PaymentStatus::Paid,
        'payment_gateway_id' => null,
        'subtotal' => '100.0000',
        'tax_total' => '0.0000',
        'shipping_total' => '0.0000',
        'discount_total' => '0.0000',
        'total' => '100.0000',
        'refund_total' => '0.0000',
    ]);
    $orderItem = OrderItem::factory()->create([
        'order_id' => $order->id,
        'quantity' => 5,
        'unit_price' => '10.0000',
        'total_price' => '50.0000',
        'tax_amount' => '0.0000',
    ]);
    $product = $orderItem->product;
    $product->update(['track_stock' => true]);

    $variant = $orderItem->productVariant;
    $stockBefore = $variant ? $variant->stock : $product->stock;

    $user = User::factory()->create();

    PaymentManager::fake(new CodDriver());

    app(ProcessRefundAction::class)->handle($user, $order, ProcessRefundInput::fromArray([
        'reason' => 'Damaged, no restock',
        'restock' => false,
        'items' => [
            [
                'order_item_id' => $orderItem->id,
                'quantity' => 2,
            ],
        ],
    ]));

    $target = $variant ? $variant->fresh() : $product->fresh();

    expect($target->stock)->toBe($stockBefore);
});

function refundableReturnSetup(ReturnStatus $status = ReturnStatus::Received): array
{
    $order = Order::factory()->create([
        'fulfillment_status' => FulfillmentStatus::Fulfilled,
        'payment_status' => PaymentStatus::Paid,
        'tax_total' => '0.0000',
        'shipping_total' => '0.0000',
        'discount_total' => '0.0000',
        'subtotal' => '20.0000',
        'total' => '20.0000',
    ]);
    $item = OrderItem::factory()->create([
        'order_id' => $order->id,
        'quantity' => 2,
        'unit_price' => '10.0000',
        'total_price' => '20.0000',
        'tax_amount' => '0.0000',
    ]);
    $return = OrderReturn::factory()->create(['order_id' => $order->id, 'status' => $status]);
    OrderReturnItem::factory()->create([
        'order_return_id' => $return->id,
        'order_item_id' => $item->id,
        'quantity' => 2,
        'received_quantity' => 2,
    ]);

    return [$order, $item, $return];
}
