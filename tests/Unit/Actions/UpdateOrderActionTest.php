<?php

declare(strict_types=1);

use App\Actions\RecalculateCustomerLifetimeValueAction;
use App\Actions\UpdateOrderAction;
use App\DTOs\UpdateOrderInput;
use App\Enums\CouponType;
use App\Enums\OrderActivityType;
use App\Enums\OrderAddressType;
use App\Enums\PaymentGatewayDriver;
use App\Enums\StockMovementReason;
use App\Models\Coupon;
use App\Models\Currency;
use App\Models\Order;
use App\Models\OrderActivity;
use App\Models\OrderAddress;
use App\Models\OrderItem;
use App\Models\PaymentGateway;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ShippingCarrier;
use App\Models\ShippingRate;
use App\Models\User;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;

covers(UpdateOrderAction::class, UpdateOrderInput::class);

uses()->group('actions', 'order');

test('updates order with customer information and handles partial updates', function (): void {
    actingAsSuperAdmin();
    $order = Order::factory()->create();

    $fullData = [
        'customer_email' => 'updated@example.com',
        'notes' => 'Updated notes',
    ];

    $result = app(UpdateOrderAction::class)->handle(auth()->user(), $order, UpdateOrderInput::fromArray($fullData));

    expect($result)->toBeInstanceOf(Order::class)
        ->and($result->customer_email)->toBe('updated@example.com')
        ->and($result->notes)->toBe('Updated notes');

    $order->refresh();
    expect($order->customer_email)->toBe('updated@example.com')
        ->and($order->notes)->toBe('Updated notes');

    $partialData = [
        'customer_email' => 'partial@example.com',
        'notes' => 'Partial update',
    ];

    $result2 = app(UpdateOrderAction::class)->handle(auth()->user(), $order, UpdateOrderInput::fromArray($partialData));

    expect($result2->customer_email)->toBe('partial@example.com')
        ->and($result2->notes)->toBe('Partial update');
});

test('handles shipping carrier and payment gateway updates', function (): void {
    actingAsSuperAdmin();

    $order = Order::factory()->create([
        'shipping_carrier_id' => null,
        'shipping_carrier_name' => null,
        'shipping_rate_id' => null,
        'shipping_rate_name' => null,
        'payment_gateway_id' => null,
        'payment_gateway_name' => null,
    ]);
    $shippingCarrier = ShippingCarrier::factory()->create(['name' => ['en' => 'DHL', 'de' => 'DHL DE']]);
    $shippingRate = ShippingRate::factory()->create([
        'shipping_carrier_id' => $shippingCarrier->id,
        'name' => ['en' => 'Express', 'de' => 'Express DE'],
    ]);
    $paymentGateway = PaymentGateway::query()->inRandomOrder()->first()
        ?? PaymentGateway::factory()->create([
            'driver' => PaymentGatewayDriver::Stripe,
            'name' => ['en' => 'Stripe', 'es' => 'Stripe ES'],
        ]);

    $data = [
        'shipping_rate_id' => $shippingRate->id,
        'payment_gateway_id' => $paymentGateway->id,
    ];

    $result = app(UpdateOrderAction::class)->handle(auth()->user(), $order, UpdateOrderInput::fromArray($data));

    expect($result->shipping_carrier_id)->toBe($shippingCarrier->id)
        ->and($result->shipping_rate_id)->toBe($shippingRate->id)
        ->and($result->payment_gateway_id)->toBe($paymentGateway->id)
        ->and($result->getTranslations('shipping_carrier_name'))->toEqualCanonicalizing($shippingCarrier->getTranslations('name'))
        ->and($result->getTranslations('shipping_rate_name'))->toEqualCanonicalizing($shippingRate->getTranslations('name'))
        ->and($result->getTranslations('payment_gateway_name'))->toEqualCanonicalizing($paymentGateway->getTranslations('name'));

    $originalShippingCarrierName = $order->getTranslations('shipping_carrier_name');
    $originalShippingRateName = $order->getTranslations('shipping_rate_name');
    $originalPaymentGatewayName = $order->getTranslations('payment_gateway_name');

    $shippingCarrier->update(['name' => 'DHL Updated']);
    $shippingRate->update(['name' => 'Express Updated']);
    $paymentGateway->update(['name' => 'Stripe Updated']);

    $result2 = app(UpdateOrderAction::class)->handle(auth()->user(), $order, UpdateOrderInput::fromArray([
        'customer_email' => 'no-change@example.com',
    ]));

    expect($result2->getTranslations('shipping_carrier_name'))->toBe($originalShippingCarrierName)
        ->and($result2->getTranslations('shipping_rate_name'))->toBe($originalShippingRateName)
        ->and($result2->getTranslations('payment_gateway_name'))->toBe($originalPaymentGatewayName);
});

test('updates order with coupon code', function (): void {
    actingAsSuperAdmin();
    $order = Order::factory()->create();

    Coupon::factory()->valid()->create([
        'code' => 'SAVE20',
        'type' => CouponType::Flat,
        'value' => '10.0000',
        'is_active' => true,
    ]);

    $data = [
        'coupon_code' => 'SAVE20',
        'customer_email' => $order->customer_email,
    ];

    $result = app(UpdateOrderAction::class)->handle(auth()->user(), $order, UpdateOrderInput::fromArray($data));

    expect($result->coupon_code)->toBe('SAVE20');

    $order->refresh();
    expect($order->coupon_code)->toBe('SAVE20');
});

test('updates order with shipping address', function (): void {
    actingAsSuperAdmin();
    $order = Order::factory()->create();

    $shippingData = [
        'first_name' => 'John',
        'last_name' => 'Doe',
        'address_line_1' => '123 Main St',
        'address_line_2' => 'Apt 4B',
        'city' => 'New York',
        'state' => 'NY',
        'postal_code' => '10001',
        'country_code' => 'US',
        'phone' => '+1234567890',
    ];

    $data = [
        'customer_email' => 'shipping@example.com',
        'shipping_address' => $shippingData,
    ];

    $result = app(UpdateOrderAction::class)->handle(auth()->user(), $order, UpdateOrderInput::fromArray($data));

    expect($result->customer_email)->toBe('shipping@example.com');

    assertDatabaseHas('order_addresses', [
        'order_id' => $order->id,
        'type' => OrderAddressType::Shipping->value,
        'first_name' => 'John',
        'last_name' => 'Doe',
        'address_line_1' => '123 Main St',
        'address_line_2' => 'Apt 4B',
        'city' => 'New York',
        'state' => 'NY',
        'postal_code' => '10001',
        'country_code' => 'US',
        'phone' => '+1234567890',
    ]);
});

test('updates order with separate billing address', function (): void {
    actingAsSuperAdmin();
    $order = Order::factory()->create();

    $billingData = [
        'first_name' => 'Jane',
        'last_name' => 'Smith',
        'address_line_1' => '456 Oak Ave',
        'address_line_2' => 'Suite 100',
        'city' => 'Los Angeles',
        'state' => 'CA',
        'postal_code' => '90210',
        'country_code' => 'US',
        'phone' => '+9876543210',
    ];

    $data = [
        'different_billing_address' => true,
        'billing_address' => $billingData,
    ];

    $result = app(UpdateOrderAction::class)->handle(auth()->user(), $order, UpdateOrderInput::fromArray($data));

    assertDatabaseHas('order_addresses', [
        'order_id' => $order->id,
        'type' => OrderAddressType::Billing->value,
        'first_name' => 'Jane',
        'last_name' => 'Smith',
        'address_line_1' => '456 Oak Ave',
        'address_line_2' => 'Suite 100',
        'city' => 'Los Angeles',
        'state' => 'CA',
        'postal_code' => '90210',
        'country_code' => 'US',
        'phone' => '+9876543210',
    ]);
});

test('updates existing shipping address', function (): void {
    actingAsSuperAdmin();
    $order = Order::factory()->create();

    $existingAddress = OrderAddress::factory()->create([
        'order_id' => $order->id,
        'type' => OrderAddressType::Shipping,
        'first_name' => 'Old Name',
        'address_line_1' => 'Old Address',
    ]);

    $shippingData = [
        'first_name' => 'New Name',
        'last_name' => 'Updated',
        'address_line_1' => 'New Address',
        'city' => 'Updated City',
        'state' => 'UC',
        'postal_code' => '12345',
        'country_code' => 'US',
    ];

    $data = ['shipping_address' => $shippingData];

    $result = app(UpdateOrderAction::class)->handle(auth()->user(), $order, UpdateOrderInput::fromArray($data));

    assertDatabaseHas('order_addresses', [
        'id' => $existingAddress->id,
        'order_id' => $order->id,
        'type' => OrderAddressType::Shipping->value,
        'first_name' => 'New Name',
        'address_line_1' => 'New Address',
    ]);

    expect(OrderAddress::query()->where('order_id', $order->id)
        ->where('type', OrderAddressType::Shipping)
        ->count())->toBe(1);
});

test('handles null values correctly', function (): void {
    actingAsSuperAdmin();
    $order = Order::factory()->create();

    $data = [
        'payment_gateway_id' => null,
        'notes' => null,
    ];

    $result = app(UpdateOrderAction::class)->handle(auth()->user(), $order, UpdateOrderInput::fromArray($data));

    expect($result->getTranslations('payment_gateway_name'))->toBe([])
        ->and($result->notes)->toBeNull();
});

test('handles empty data array', function (): void {
    actingAsSuperAdmin();
    $order = Order::factory()->create();
    $originalEmail = $order->customer_email;

    $data = [];

    $result = app(UpdateOrderAction::class)->handle(auth()->user(), $order, UpdateOrderInput::fromArray($data));

    expect($result->customer_email)->toBe($originalEmail);

    assertDatabaseMissing('order_activities', [
        'order_id' => $order->id,
        'comment' => 'Order details updated',
    ]);
});

test('handles order item creation, updates, and deletion correctly', function (): void {
    actingAsSuperAdmin();
    $user = User::factory()->create(['email' => 'item-test@example.com']);
    $order = Order::factory()->fulfilled()->create([
        'customer_id' => $user->id,
        'customer_email' => $user->email,
    ]);

    $product1 = Product::factory()->create([
        'price' => '12.0000',
        'track_stock' => true,
        'stock' => 10,
    ]);
    $product2 = Product::factory()->create([
        'price' => '5.0000',
        'track_stock' => true,
        'stock' => 5,
    ]);
    $product3 = Product::factory()->create([
        'price' => '25.0000',
        'track_stock' => true,
        'stock' => 8,
    ]);

    $item1 = OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product1->id,
        'quantity' => 1,
    ]);

    $item2 = OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product2->id,
        'quantity' => 2,
    ]);

    $data = [
        'restock' => true,
        'items' => [
            [
                'id' => $item1->id,
                'product_id' => $product1->id,
                'quantity' => 3,
            ],
            [
                'product_id' => $product3->id,
                'quantity' => 2,
            ],
        ],
    ];

    $result = app(UpdateOrderAction::class)->handle(auth()->user(), $order, UpdateOrderInput::fromArray($data));

    expect($result->items)->toHaveCount(2);

    assertDatabaseHas('order_items', [
        'id' => $item1->id,
        'order_id' => $order->id,
        'product_id' => $product1->id,
        'quantity' => 3,
        'unit_price' => '12.0000', // From product price
        'total_price' => '36.0000', // 12.00 * 3
    ]);

    assertDatabaseHas('order_items', [
        'order_id' => $order->id,
        'product_id' => $product3->id,
        'quantity' => 2,
        'unit_price' => '25.0000', // From product price
        'total_price' => '50.0000', // 25.00 * 2
    ]);

    assertDatabaseMissing('order_items', [
        'id' => $item2->id,
    ]);

    $product1->refresh();
    $product2->refresh();
    $product3->refresh();

    expect($product1->stock)->toBe(8);
    expect($product2->stock)->toBe(7);
    expect($product3->stock)->toBe(6);

    assertDatabaseHas('stock_movements', [
        'product_id' => $product1->id,
        'quantity' => -2,
        'reason' => StockMovementReason::Sale,
        'reference_type' => Order::class,
        'reference_id' => $order->id,
    ]);

    assertDatabaseHas('stock_movements', [
        'product_id' => $product2->id,
        'quantity' => 2,
        'reason' => StockMovementReason::Return,
        'reference_type' => Order::class,
        'reference_id' => $order->id,
    ]);

    assertDatabaseHas('stock_movements', [
        'product_id' => $product3->id,
        'quantity' => -2,
        'reason' => StockMovementReason::Sale,
        'reference_type' => Order::class,
        'reference_id' => $order->id,
    ]);

    expect($result->tax_total)->not->toBeNull();
});

test('does not restock reduced or removed items when restock is disabled', function (): void {
    actingAsSuperAdmin();
    $order = Order::factory()->fulfilled()->create();

    $product1 = Product::factory()->create([
        'price' => '12.0000',
        'track_stock' => true,
        'stock' => 10,
    ]);
    $product2 = Product::factory()->create([
        'price' => '5.0000',
        'track_stock' => true,
        'stock' => 5,
    ]);
    $product3 = Product::factory()->create([
        'price' => '25.0000',
        'track_stock' => true,
        'stock' => 8,
    ]);

    $item1 = OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product1->id,
        'quantity' => 3,
    ]);

    $item2 = OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product2->id,
        'quantity' => 2,
    ]);

    $item3 = OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product3->id,
        'quantity' => 1,
    ]);

    $data = [
        'restock' => false,
        'items' => [
            [
                'id' => $item1->id,
                'product_id' => $product1->id,
                'quantity' => 1,
            ],
            [
                'id' => $item3->id,
                'product_id' => $product3->id,
                'quantity' => 3,
            ],
        ],
    ];

    app(UpdateOrderAction::class)->handle(auth()->user(), $order, UpdateOrderInput::fromArray($data));

    expect($product1->refresh()->stock)->toBe(10);
    expect($product2->refresh()->stock)->toBe(5);
    expect($product3->refresh()->stock)->toBe(6);

    assertDatabaseMissing('order_items', [
        'id' => $item2->id,
    ]);

    assertDatabaseMissing('stock_movements', [
        'product_id' => $product1->id,
    ]);

    assertDatabaseMissing('stock_movements', [
        'product_id' => $product2->id,
    ]);

    assertDatabaseHas('stock_movements', [
        'product_id' => $product3->id,
        'quantity' => -2,
        'reason' => StockMovementReason::Sale,
        'reference_type' => Order::class,
        'reference_id' => $order->id,
    ]);
});

test('restocks reduced and removed items when restock is enabled', function (): void {
    actingAsSuperAdmin();
    $order = Order::factory()->fulfilled()->create();

    $product1 = Product::factory()->create([
        'price' => '12.0000',
        'track_stock' => true,
        'stock' => 10,
    ]);
    $product2 = Product::factory()->create([
        'price' => '5.0000',
        'track_stock' => true,
        'stock' => 5,
    ]);

    $item1 = OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product1->id,
        'quantity' => 3,
    ]);

    $item2 = OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product2->id,
        'quantity' => 2,
    ]);

    $data = [
        'restock' => true,
        'items' => [
            [
                'id' => $item1->id,
                'product_id' => $product1->id,
                'quantity' => 1,
            ],
        ],
    ];

    app(UpdateOrderAction::class)->handle(auth()->user(), $order, UpdateOrderInput::fromArray($data));

    expect($product1->refresh()->stock)->toBe(12);
    expect($product2->refresh()->stock)->toBe(7);

    assertDatabaseMissing('order_items', [
        'id' => $item2->id,
    ]);

    assertDatabaseHas('stock_movements', [
        'product_id' => $product1->id,
        'quantity' => 2,
        'reason' => StockMovementReason::Return,
        'reference_type' => Order::class,
        'reference_id' => $order->id,
    ]);

    assertDatabaseHas('stock_movements', [
        'product_id' => $product2->id,
        'quantity' => 2,
        'reason' => StockMovementReason::Return,
        'reference_type' => Order::class,
        'reference_id' => $order->id,
    ]);
});

test('removing and re-adding the same product nets to zero stock change', function (): void {
    actingAsSuperAdmin();
    $order = Order::factory()->fulfilled()->create();

    $product = Product::factory()->create([
        'price' => '12.0000',
        'track_stock' => true,
        'stock' => 5,
    ]);

    $item = OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'quantity' => 2,
    ]);

    $data = [
        'restock' => false,
        'items' => [
            [
                'product_id' => $product->id,
                'quantity' => 2,
            ],
        ],
    ];

    app(UpdateOrderAction::class)->handle(auth()->user(), $order, UpdateOrderInput::fromArray($data));

    expect($product->refresh()->stock)->toBe(5);

    assertDatabaseMissing('order_items', [
        'id' => $item->id,
    ]);

    assertDatabaseHas('order_items', [
        'order_id' => $order->id,
        'product_id' => $product->id,
        'quantity' => 2,
    ]);

    assertDatabaseMissing('stock_movements', [
        'product_id' => $product->id,
    ]);
});

test('restocks only the net quantity when a removed product is partially re-added', function (): void {
    actingAsSuperAdmin();
    $order = Order::factory()->fulfilled()->create();

    $product = Product::factory()->create([
        'price' => '12.0000',
        'track_stock' => true,
        'stock' => 5,
    ]);

    OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'quantity' => 3,
    ]);

    $data = [
        'restock' => true,
        'items' => [
            [
                'product_id' => $product->id,
                'quantity' => 2,
            ],
        ],
    ];

    app(UpdateOrderAction::class)->handle(auth()->user(), $order, UpdateOrderInput::fromArray($data));

    expect($product->refresh()->stock)->toBe(6);

    assertDatabaseHas('stock_movements', [
        'product_id' => $product->id,
        'quantity' => 1,
        'reason' => StockMovementReason::Return,
        'reference_type' => Order::class,
        'reference_id' => $order->id,
    ]);
});

test('handles billing address copying and separate billing address correctly', function (): void {
    actingAsSuperAdmin();
    $order = Order::factory()->create();

    $shippingAddress = OrderAddress::factory()->create([
        'order_id' => $order->id,
        'type' => OrderAddressType::Shipping,
        'first_name' => 'Shipping First',
        'last_name' => 'Shipping Last',
    ]);

    $billingAddress = OrderAddress::factory()->create([
        'order_id' => $order->id,
        'type' => OrderAddressType::Billing,
        'first_name' => 'Billing First',
        'last_name' => 'Billing Last',
    ]);

    app(UpdateOrderAction::class)->handle(auth()->user(), $order, UpdateOrderInput::fromArray([
        'different_billing_address' => false,
    ]));

    assertDatabaseHas('order_addresses', [
        'order_id' => $order->id,
        'type' => OrderAddressType::Shipping->value,
        'first_name' => 'Shipping First',
        'last_name' => 'Shipping Last',
    ]);

    assertDatabaseHas('order_addresses', [
        'order_id' => $order->id,
        'type' => OrderAddressType::Billing->value,
        'first_name' => 'Shipping First',
        'last_name' => 'Shipping Last',
    ]);

    app(UpdateOrderAction::class)->handle(auth()->user(), $order, UpdateOrderInput::fromArray([
        'different_billing_address' => true,
        'billing_address' => [
            'first_name' => 'Updated Jane',
            'last_name' => 'Updated Smith',
            'address_line_1' => 'Updated 456 Oak Ave',
            'city' => 'Updated City',
            'state' => 'Updated State',
            'postal_code' => 'U12345',
            'country_code' => 'US',
        ],
    ]));

    assertDatabaseHas('order_addresses', [
        'order_id' => $order->id,
        'type' => OrderAddressType::Billing->value,
        'first_name' => 'Updated Jane',
        'last_name' => 'Updated Smith',
        'address_line_1' => 'Updated 456 Oak Ave',
    ]);
});

test('recalculates subtotal when order items are updated', function (): void {
    actingAsSuperAdmin();
    $order = Order::factory()->create();

    $product1 = Product::factory()->create(['price' => '15.0000', 'track_stock' => false]);
    $product2 = Product::factory()->create(['price' => '20.0000', 'track_stock' => false]);

    $item1 = OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product1->id,
        'quantity' => 1,
    ]);

    $data = [
        'items' => [
            [
                'id' => $item1->id,
                'product_id' => $product1->id,
                'quantity' => 2,
            ],
            [
                'product_id' => $product2->id,
                'quantity' => 1,
            ],
        ],
    ];

    $result = app(UpdateOrderAction::class)->handle(auth()->user(), $order, UpdateOrderInput::fromArray($data));

    expect($result->subtotal)->toBe('50.0000'); // 30.00 + 20.00
    expect($result->items)->toHaveCount(2);

    assertDatabaseHas('orders', [
        'id' => $order->id,
        'subtotal' => '50.0000', // 30.00 + 20.00
    ]);

    assertDatabaseHas('order_items', [
        'id' => $item1->id,
        'order_id' => $order->id,
        'product_id' => $product1->id,
        'quantity' => 2,
        'unit_price' => '15.0000', // From product price
        'total_price' => '30.0000', // 15.00 * 2
    ]);

    assertDatabaseHas('order_items', [
        'order_id' => $order->id,
        'product_id' => $product2->id,
        'quantity' => 1,
        'unit_price' => '20.0000', // From product price
        'total_price' => '20.0000', // 20.00 * 1
    ]);
});

test('recalculates lifetime value for both customers when customer is changed', function (): void {
    actingAsSuperAdmin();

    $oldCustomer = User::factory()->create();
    $newCustomer = User::factory()->create();

    $order = Order::factory()->fulfilled()->create([
        'customer_id' => $oldCustomer->id,
        'customer_email' => $oldCustomer->email,
        'total' => '100.0000',
    ]);

    app(RecalculateCustomerLifetimeValueAction::class)->handle($oldCustomer);

    expect($oldCustomer->refresh()->lifetime_value)->toBe('100.0000');
    expect($newCustomer->refresh()->lifetime_value)->toBe('0.0000');

    $data = [
        'customer_id' => $newCustomer->id,
        'customer_email' => $newCustomer->email,
    ];

    $result = app(UpdateOrderAction::class)->handle(auth()->user(), $order, UpdateOrderInput::fromArray($data));

    expect($result->customer_id)->toBe($newCustomer->id);
    expect($result->customer_email)->toBe($newCustomer->email);

    expect($oldCustomer->refresh()->lifetime_value)->toBe('0.0000');
    expect($newCustomer->refresh()->lifetime_value)->toBe('100.0000');
});

test('increments coupon usage when coupon is applied', function (): void {
    actingAsSuperAdmin();
    $order = Order::factory()->create();

    $coupon = Coupon::factory()->valid()->create([
        'code' => 'TESTCOUPON',
        'type' => CouponType::Flat,
        'value' => '10.0000',
        'is_active' => true,
        'used_count' => 5,
    ]);

    $order->update([
        'coupon_code' => null,
        'coupon_id' => null,
    ]);

    $data = [
        'coupon_code' => 'TESTCOUPON',
        'customer_email' => 'test@example.com',
    ];

    $result = app(UpdateOrderAction::class)->handle(auth()->user(), $order, UpdateOrderInput::fromArray($data));

    expect($result->coupon_code)->toBe('TESTCOUPON');
    expect($result->coupon_id)->toBe($coupon->id);

    $coupon->refresh();
    expect($coupon->used_count)->toBe(6);
});

test('throws validation exception for invalid coupon', function (): void {
    actingAsSuperAdmin();
    $order = Order::factory()->create();

    $data = [
        'coupon_code' => 'INVALID_COUPON',
        'customer_email' => 'test@example.com',
    ];

    app(UpdateOrderAction::class)->handle(auth()->user(), $order, UpdateOrderInput::fromArray($data));
})->throws(Illuminate\Validation\ValidationException::class);

test('skips coupon processing when coupon code is same as existing order coupon', function (): void {
    actingAsSuperAdmin();
    $order = Order::factory()->create();

    $coupon = Coupon::factory()->valid()->create([
        'code' => 'EXISTING',
        'used_count' => 3,
    ]);

    $order->update([
        'coupon_code' => 'EXISTING',
        'coupon_id' => $coupon->id,
    ]);

    $data = [
        'coupon_code' => 'EXISTING',
    ];

    $result = app(UpdateOrderAction::class)->handle(auth()->user(), $order, UpdateOrderInput::fromArray($data));

    $coupon->refresh();
    expect($coupon->used_count)->toBe(3);
    expect($result->coupon_code)->toBe('EXISTING');
});

test('removes coupon when coupon_code is empty', function (): void {
    actingAsSuperAdmin();
    $coupon = Coupon::factory()->valid()->create();
    $order = Order::factory()->create([
        'coupon_code' => 'EXISTING',
        'coupon_id' => $coupon->id,
        'discount_total' => '10.0000',
    ]);

    $data = [
        'coupon_code' => '',
        'customer_email' => 'test@example.com',
    ];

    app(UpdateOrderAction::class)->handle(auth()->user(), $order, UpdateOrderInput::fromArray($data));

    $order->refresh();

    expect($order->coupon_code)->toBeNull()
        ->and($order->coupon_id)->toBeNull()
        ->and($order->discount_total)->toBe('0.0000');
});

test('handles product variant in new order items', function (): void {
    actingAsSuperAdmin();
    $order = Order::factory()->create();
    $product = Product::factory()->create(['price' => '25.0000', 'track_stock' => false]);
    $variant = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'sku' => 'VARIANT-SKU',
        'title' => 'Variant Title',
        'weight' => '2.50',
        'weight_unit' => 'kg',
        'track_stock' => false,
    ]);

    $data = [
        'items' => [
            [
                'product_id' => $product->id,
                'product_variant_id' => $variant->id,
                'quantity' => 1,
                'variant_options' => ['size' => 'Large'],
            ],
        ],
    ];

    app(UpdateOrderAction::class)->handle(auth()->user(), $order, UpdateOrderInput::fromArray($data));

    assertDatabaseHas('order_items', [
        'order_id' => $order->id,
        'product_id' => $product->id,
        'product_variant_id' => $variant->id,
        'product_sku' => 'VARIANT-SKU',
        'variant_title' => 'Variant Title',
        'weight' => '2.50',
        'weight_unit' => 'kg',
    ]);
});

test('decrements old coupon usage when switching to new coupon', function (): void {
    actingAsSuperAdmin();

    $oldCoupon = Coupon::factory()->valid()->create([
        'code' => 'OLDCOUPON',
        'type' => CouponType::Flat,
        'value' => '10.0000',
        'used_count' => 5,
    ]);

    $newCoupon = Coupon::factory()->valid()->create([
        'code' => 'NEWCOUPON',
        'type' => CouponType::Flat,
        'value' => '15.0000',
        'used_count' => 3,
    ]);

    $order = Order::factory()->create([
        'coupon_id' => $oldCoupon->id,
        'coupon_code' => 'OLDCOUPON',
        'discount_total' => '10.0000',
        'subtotal' => '100.0000',
    ]);

    app(UpdateOrderAction::class)->handle(auth()->user(), $order, UpdateOrderInput::fromArray([
        'coupon_code' => 'NEWCOUPON',
    ]));

    $oldCoupon->refresh();
    $newCoupon->refresh();

    expect($oldCoupon->used_count)->toBe(4)
        ->and($newCoupon->used_count)->toBe(4);
});

test('decrements coupon usage when removing coupon', function (): void {
    actingAsSuperAdmin();

    $coupon = Coupon::factory()->valid()->create([
        'code' => 'REMOVEME',
        'type' => CouponType::Flat,
        'value' => '10.0000',
        'used_count' => 5,
    ]);

    $order = Order::factory()->create([
        'coupon_id' => $coupon->id,
        'coupon_code' => 'REMOVEME',
        'discount_total' => '10.0000',
    ]);

    app(UpdateOrderAction::class)->handle(auth()->user(), $order, UpdateOrderInput::fromArray([
        'coupon_code' => '',
    ]));

    $coupon->refresh();
    expect($coupon->used_count)->toBe(4);
});

test('recalculates total when shipping rate changes without items', function (): void {
    actingAsSuperAdmin();

    $shippingCarrier = ShippingCarrier::factory()->create();
    $oldRate = ShippingRate::factory()->create([
        'shipping_carrier_id' => $shippingCarrier->id,
        'rate' => '5.0000',
    ]);
    $newRate = ShippingRate::factory()->create([
        'shipping_carrier_id' => $shippingCarrier->id,
        'rate' => '15.0000',
    ]);

    $order = Order::factory()->create([
        'subtotal' => '100.0000',
        'shipping_total' => '5.0000',
        'tax_total' => '0.0000',
        'discount_total' => '0.0000',
        'total' => '105.0000',
        'shipping_rate_id' => $oldRate->id,
    ]);

    app(UpdateOrderAction::class)->handle(auth()->user(), $order, UpdateOrderInput::fromArray([
        'shipping_rate_id' => $newRate->id,
    ]));

    $order->refresh();

    expect($order->shipping_total)->toBe('15.0000')
        ->and($order->total)->toBe('115.0000');
});

test('recalculates total when coupon is applied without items changing', function (): void {
    actingAsSuperAdmin();

    $coupon = Coupon::factory()->valid()->create([
        'code' => 'SAVE10',
        'type' => CouponType::Flat,
        'value' => '10.0000',
    ]);

    $order = Order::factory()->create([
        'subtotal' => '100.0000',
        'shipping_total' => '5.0000',
        'tax_total' => '0.0000',
        'discount_total' => '0.0000',
        'total' => '105.0000',
        'coupon_id' => null,
        'coupon_code' => null,
    ]);

    app(UpdateOrderAction::class)->handle(auth()->user(), $order, UpdateOrderInput::fromArray([
        'coupon_code' => 'SAVE10',
    ]));

    $order->refresh();

    expect($order->discount_total)->toBe('10.0000')
        ->and($order->total)->toBe('95.0000');
});

test('recalculates percentage coupon discount when items change', function (): void {
    actingAsSuperAdmin();

    $coupon = Coupon::factory()->valid()->create([
        'code' => 'PERCENT10',
        'type' => CouponType::Percentage,
        'value' => '10.0000',
    ]);

    $product = Product::factory()->create(['price' => '50.0000', 'track_stock' => false]);

    $order = Order::factory()->create([
        'subtotal' => '100.0000',
        'shipping_total' => '0.0000',
        'tax_total' => '0.0000',
        'discount_total' => '10.0000',
        'total' => '90.0000',
        'coupon_id' => $coupon->id,
        'coupon_code' => 'PERCENT10',
    ]);

    OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'quantity' => 2,
        'unit_price' => '50.0000',
        'total_price' => '100.0000',
    ]);

    app(UpdateOrderAction::class)->handle(auth()->user(), $order, UpdateOrderInput::fromArray([
        'items' => [
            [
                'product_id' => $product->id,
                'quantity' => 4,
            ],
        ],
    ]));

    $order->refresh();

    expect($order->subtotal)->toBe('200.0000')
        ->and($order->discount_total)->toBe('20.0000')
        ->and($order->total)->toBe('180.0000');
});

test('scales item prices to currency decimal places when items change', function (): void {
    actingAsSuperAdmin();

    Currency::factory()->create([
        'code' => 'JPY',
        'decimal_places' => 0,
        'exchange_rate' => '1.0000',
        'is_active' => true,
    ]);

    $product = Product::factory()->create(['price' => '33.3300', 'track_stock' => false]);
    $order = Order::factory()->create(['currency_code' => 'JPY']);

    $data = [
        'items' => [
            [
                'product_id' => $product->id,
                'quantity' => 2,
            ],
        ],
    ];

    $result = app(UpdateOrderAction::class)->handle(auth()->user(), $order, UpdateOrderInput::fromArray($data));

    expect($result->subtotal)->toBe('66.0000'); // 33 * 2

    $item = $result->items()->first();
    expect($item->unit_price)->toBe('33.0000') // 33.33 rounded to 0 dp
        ->and($item->total_price)->toBe('66.0000'); // 33 * 2
});

test('rescales item prices when currency changes without item changes', function (): void {
    actingAsSuperAdmin();

    Currency::factory()->create([
        'code' => 'JPY',
        'decimal_places' => 0,
        'exchange_rate' => '1.0000',
        'is_active' => true,
    ]);

    $order = Order::factory()->create([
        'currency_code' => 'USD',
        'shipping_total' => '12.5000',
        'discount_total' => '5.7500',
    ]);
    OrderItem::factory()->create([
        'order_id' => $order->id,
        'unit_price' => '33.3300',
        'total_price' => '66.6600',
        'quantity' => 2,
    ]);
    $order->update(['subtotal' => '66.6600']);

    $result = app(UpdateOrderAction::class)->handle(auth()->user(), $order, UpdateOrderInput::fromArray([
        'currency_code' => 'JPY',
    ]));

    $result->refresh();
    expect($result->subtotal)->toBe('66.0000') // 33 * 2
        ->and($result->shipping_total)->toBe('13.0000') // 12.50 rounded to 0 dp
        ->and($result->discount_total)->toBe('6.0000'); // 5.75 rounded to 0 dp

    $item = $result->items()->first();
    expect($item->unit_price)->toBe('33.0000') // 33.33 rounded to 0 dp
        ->and($item->total_price)->toBe('66.0000'); // 33 * 2
});

test('scales items to new currency when both currency and items change', function (): void {
    actingAsSuperAdmin();

    Currency::factory()->create([
        'code' => 'JPY',
        'decimal_places' => 0,
        'exchange_rate' => '1.0000',
        'is_active' => true,
    ]);

    $product = Product::factory()->create(['price' => '33.3300', 'track_stock' => false]);
    $order = Order::factory()->create(['currency_code' => 'USD']);
    OrderItem::factory()->create([
        'order_id' => $order->id,
        'unit_price' => '50.0000',
        'total_price' => '50.0000',
        'quantity' => 1,
    ]);
    $order->update(['subtotal' => '50.0000']);

    $result = app(UpdateOrderAction::class)->handle(auth()->user(), $order, UpdateOrderInput::fromArray([
        'currency_code' => 'JPY',
        'items' => [
            [
                'product_id' => $product->id,
                'quantity' => 2,
            ],
        ],
    ]));

    $result->refresh();
    expect($result->currency_code)->toBe('JPY')
        ->and($result->subtotal)->toBe('66.0000'); // 33 * 2

    $item = $result->items()->first();
    expect($item->unit_price)->toBe('33.0000') // 33.33 rounded to 0 dp
        ->and($item->total_price)->toBe('66.0000'); // 33 * 2
});

test('zeroes shipping total when shipping rate is set to null', function (): void {
    actingAsSuperAdmin();

    $shippingCarrier = ShippingCarrier::factory()->create();
    $shippingRate = ShippingRate::factory()->create([
        'shipping_carrier_id' => $shippingCarrier->id,
        'rate' => '15.0000',
    ]);

    $order = Order::factory()->create([
        'subtotal' => '100.0000',
        'shipping_total' => '15.0000',
        'shipping_rate_id' => $shippingRate->id,
        'shipping_carrier_id' => $shippingCarrier->id,
        'tax_total' => '0.0000',
        'discount_total' => '0.0000',
        'total' => '115.0000',
    ]);

    app(UpdateOrderAction::class)->handle(auth()->user(), $order, UpdateOrderInput::fromArray([
        'shipping_rate_id' => null,
    ]));

    $order->refresh();

    expect($order->shipping_rate_id)->toBeNull()
        ->and($order->shipping_carrier_id)->toBeNull()
        ->and($order->shipping_total)->toBe('0.0000')
        ->and($order->total)->toBe('100.0000');
});

test('recalculates total when coupon is removed without items changing', function (): void {
    actingAsSuperAdmin();

    $coupon = Coupon::factory()->valid()->create([
        'code' => 'REMOVE',
        'type' => CouponType::Flat,
        'value' => '10.0000',
    ]);

    $order = Order::factory()->create([
        'subtotal' => '100.0000',
        'shipping_total' => '5.0000',
        'tax_total' => '0.0000',
        'discount_total' => '10.0000',
        'total' => '95.0000',
        'coupon_id' => $coupon->id,
        'coupon_code' => 'REMOVE',
    ]);

    app(UpdateOrderAction::class)->handle(auth()->user(), $order, UpdateOrderInput::fromArray([
        'coupon_code' => '',
    ]));

    $order->refresh();

    expect($order->discount_total)->toBe('0.0000')
        ->and($order->total)->toBe('105.0000');
});

test('records activity when items are added and removed', function (): void {
    actingAsSuperAdmin();

    $order = Order::factory()->create();
    $product1 = Product::factory()->create(['price' => '10.0000', 'track_stock' => false]);
    $product2 = Product::factory()->create(['price' => '20.0000', 'track_stock' => false]);

    $item = OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product1->id,
        'quantity' => 2,
    ]);

    app(UpdateOrderAction::class)->handle(auth()->user(), $order, UpdateOrderInput::fromArray([
        'items' => [
            ['product_id' => $product2->id, 'quantity' => 1],
        ],
    ]));

    $activity = OrderActivity::query()
        ->where('order_id', $order->id)
        ->where('type', OrderActivityType::OrderEdited)
        ->first();

    expect($activity)->not->toBeNull()
        ->and($activity->user_id)->toBe(auth()->id());

    $changes = collect($activity->metadata['changes']);

    expect($changes->where('type', 'item_added')->count())->toBe(1)
        ->and($changes->where('type', 'item_removed')->count())->toBe(1);
});

test('records activity when item quantity changes', function (): void {
    actingAsSuperAdmin();

    $order = Order::factory()->create();
    $product = Product::factory()->create(['price' => '10.0000', 'track_stock' => false]);

    $item = OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'quantity' => 2,
    ]);

    app(UpdateOrderAction::class)->handle(auth()->user(), $order, UpdateOrderInput::fromArray([
        'items' => [
            ['id' => $item->id, 'product_id' => $product->id, 'quantity' => 5],
        ],
    ]));

    $activity = OrderActivity::query()
        ->where('order_id', $order->id)
        ->where('type', OrderActivityType::OrderEdited)
        ->first();

    $change = collect($activity->metadata['changes'])->firstWhere('type', 'item_quantity_changed');

    expect($change)->not->toBeNull()
        ->and($change['from'])->toBe(2)
        ->and($change['to'])->toBe(5);
});

test('records activity when shipping rate changes', function (): void {
    actingAsSuperAdmin();

    $carrier = ShippingCarrier::factory()->create();
    $oldRate = ShippingRate::factory()->create([
        'shipping_carrier_id' => $carrier->id,
        'name' => ['en' => 'Standard'],
        'rate' => '5.0000',
    ]);
    $newRate = ShippingRate::factory()->create([
        'shipping_carrier_id' => $carrier->id,
        'name' => ['en' => 'Express'],
        'rate' => '15.0000',
    ]);

    $order = Order::factory()->create([
        'shipping_rate_id' => $oldRate->id,
        'shipping_rate_name' => ['en' => 'Standard'],
        'shipping_total' => '5.0000',
    ]);

    app(UpdateOrderAction::class)->handle(auth()->user(), $order, UpdateOrderInput::fromArray([
        'shipping_rate_id' => $newRate->id,
    ]));

    $activity = OrderActivity::query()
        ->where('order_id', $order->id)
        ->where('type', OrderActivityType::OrderEdited)
        ->first();

    $change = collect($activity->metadata['changes'])->firstWhere('type', 'shipping_changed');

    expect($change)->not->toBeNull()
        ->and($change['from'])->toBe(['en' => 'Standard'])
        ->and($change['to'])->toBe(['en' => 'Express']);
});

test('records activity when payment gateway changes', function (): void {
    actingAsSuperAdmin();

    $oldGateway = PaymentGateway::factory()->create([
        'driver' => PaymentGatewayDriver::Cod,
        'name' => ['en' => 'Cash on Delivery'],
    ]);
    $newGateway = PaymentGateway::factory()->create([
        'driver' => PaymentGatewayDriver::Stripe,
        'name' => ['en' => 'Credit Card'],
    ]);

    $order = Order::factory()->create([
        'payment_gateway_id' => $oldGateway->id,
        'payment_gateway_name' => ['en' => 'Cash on Delivery'],
    ]);

    app(UpdateOrderAction::class)->handle(auth()->user(), $order, UpdateOrderInput::fromArray([
        'payment_gateway_id' => $newGateway->id,
    ]));

    $activity = OrderActivity::query()
        ->where('order_id', $order->id)
        ->where('type', OrderActivityType::OrderEdited)
        ->first();

    $change = collect($activity->metadata['changes'])->firstWhere('type', 'payment_gateway_changed');

    expect($change)->not->toBeNull()
        ->and($change['from'])->toBe(['en' => 'Cash on Delivery'])
        ->and($change['to'])->toBe(['en' => 'Credit Card']);
});

test('records activity when coupon is added or removed', function (): void {
    actingAsSuperAdmin();

    $coupon = Coupon::factory()->valid()->create([
        'code' => 'SAVE10',
        'type' => CouponType::Flat,
        'value' => '10.0000',
    ]);

    $order = Order::factory()->create([
        'coupon_id' => null,
        'coupon_code' => null,
        'subtotal' => '100.0000',
    ]);

    app(UpdateOrderAction::class)->handle(auth()->user(), $order, UpdateOrderInput::fromArray([
        'coupon_code' => 'SAVE10',
    ]));

    $activity = OrderActivity::query()
        ->where('order_id', $order->id)
        ->where('type', OrderActivityType::OrderEdited)
        ->first();

    $change = collect($activity->metadata['changes'])->firstWhere('type', 'coupon_changed');

    expect($change)->not->toBeNull()
        ->and($change['from'])->toBeNull()
        ->and($change['to'])->toBe('SAVE10');
});

test('records activity when contact email changes', function (): void {
    actingAsSuperAdmin();

    $order = Order::factory()->create(['customer_email' => 'old@example.com']);

    app(UpdateOrderAction::class)->handle(auth()->user(), $order, UpdateOrderInput::fromArray([
        'customer_email' => 'new@example.com',
    ]));

    $activity = OrderActivity::query()
        ->where('order_id', $order->id)
        ->where('type', OrderActivityType::OrderEdited)
        ->first();

    $change = collect($activity->metadata['changes'])->firstWhere('type', 'contact_email_changed');

    expect($change)->not->toBeNull()
        ->and($change['from'])->toBe('old@example.com')
        ->and($change['to'])->toBe('new@example.com');
});

test('does not record activity when nothing meaningful changes', function (): void {
    actingAsSuperAdmin();

    $order = Order::factory()->create(['notes' => 'old']);

    app(UpdateOrderAction::class)->handle(auth()->user(), $order, UpdateOrderInput::fromArray([
        'notes' => 'updated',
    ]));

    $activity = OrderActivity::query()
        ->where('order_id', $order->id)
        ->where('type', OrderActivityType::OrderEdited)
        ->first();

    expect($activity)->toBeNull();
});
