<?php

declare(strict_types=1);

use App\Actions\SendCustomerNotificationAction;
use App\Actions\UpdateOrderAction;
use App\Enums\CouponType;
use App\Enums\FulfillmentStatus;
use App\Enums\OrderAddressType;
use App\Enums\Permission;
use App\Enums\Role as RoleEnum;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Requests\Admin\UpdateOrderRequest;
use App\Models\Coupon;
use App\Models\Currency;
use App\Models\Order;
use App\Models\OrderAddress;
use App\Models\OrderItem;
use App\Models\OrderShipment;
use App\Models\OrderShipmentItem;
use App\Models\PaymentGateway;
use App\Models\Product;
use App\Models\Region;
use App\Models\ShippingCarrier;
use App\Models\ShippingRate;
use App\Models\User;
use App\Notifications\CustomerOrderUpdatedNotification;
use App\Queries\ActivePaymentGatewayListQuery;
use App\Rules\ShippedItemsPreserved;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Laravel\get;
use function Pest\Laravel\patch;

covers([
    OrderController::class,
    UpdateOrderRequest::class,
    UpdateOrderAction::class,
    SendCustomerNotificationAction::class,
    ActivePaymentGatewayListQuery::class,
    ShippedItemsPreserved::class,
]);

uses()->group('order');

test('updates an existing order', function () {
    $order = Order::factory()->create();
    $newShippingCarrier = ShippingCarrier::factory()->create();
    $region = Region::factory()->create();
    $newShippingRate = ShippingRate::factory()->create([
        'shipping_carrier_id' => $newShippingCarrier->id,
        'region_id' => $region->id,
        'rate' => '20.0000',
    ]);
    $newPaymentGateway = PaymentGateway::first();

    $data = [
        'customer_email' => 'updated@example.com',
        'shipping_rate_id' => $newShippingRate->id,
        'payment_gateway_id' => $newPaymentGateway->id,
        'notes' => 'Updated order notes',
        'shipping_address' => [
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'address_line_1' => '321 Shipping Ave',
            'address_line_2' => null,
            'city' => 'San Francisco',
            'state' => 'CA',
            'postal_code' => '94102',
            'country_code' => 'US',
            'phone' => '+14155552671',
        ],
        'different_billing_address' => true,
        'billing_address' => [
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'address_line_1' => '789 Updated St',
            'address_line_2' => 'Suite 100',
            'city' => 'Los Angeles',
            'state' => 'CA',
            'postal_code' => '90210',
            'country_code' => 'US',
            'phone' => '+14155552671',
        ],
    ];

    $response = actingAsSuperAdmin()->patch(route('admin.orders.update', $order), $data);

    $response->assertRedirect()
        ->assertSessionHasNoErrors();

    assertDatabaseHas('orders', [
        'id' => $order->id,
        'customer_email' => 'updated@example.com',
        'shipping_carrier_id' => $newShippingCarrier->id, // Resolved from shipping rate
        'shipping_rate_id' => $newShippingRate->id,
        'payment_gateway_id' => $newPaymentGateway->id,
        'notes' => 'Updated order notes',
    ]);

    assertDatabaseHas('order_addresses', [
        'order_id' => $order->id,
        'type' => OrderAddressType::Billing->value,
        'first_name' => 'Jane',
        'address_line_1' => '789 Updated St',
        'city' => 'Los Angeles',
    ]);

    assertDatabaseHas('order_addresses', [
        'order_id' => $order->id,
        'type' => OrderAddressType::Shipping->value,
        'first_name' => 'Jane',
        'address_line_1' => '321 Shipping Ave',
        'city' => 'San Francisco',
    ]);
});

test('preserves shipping_total when same shipping_rate_id is resubmitted', function () {
    $shippingCarrier = ShippingCarrier::factory()->create();
    $region = Region::factory()->create();
    $shippingRate = ShippingRate::factory()->create([
        'shipping_carrier_id' => $shippingCarrier->id,
        'region_id' => $region->id,
        'rate' => '15.0000',
    ]);
    $order = Order::factory()->create([
        'shipping_rate_id' => $shippingRate->id,
        'shipping_carrier_id' => $shippingCarrier->id,
        'shipping_total' => '15.0000',
    ]);

    $response = actingAsSuperAdmin()->patch(route('admin.orders.update', $order), [
        'shipping_rate_id' => $shippingRate->id,
    ]);

    $response->assertRedirect()->assertSessionHasNoErrors();

    assertDatabaseHas('orders', [
        'id' => $order->id,
        'shipping_rate_id' => $shippingRate->id,
        'shipping_total' => '15.0000',
    ]);
});

test('updates order with partial data', function () {
    $order = Order::factory()->create([
        'customer_email' => 'original@example.com',
    ]);

    $data = [
        'customer_email' => 'updated@example.com',
    ];

    $response = actingAsSuperAdmin()->patch(route('admin.orders.update', $order), $data);

    $response->assertRedirect()
        ->assertSessionHasNoErrors();

    assertDatabaseHas('orders', [
        'id' => $order->id,
        'customer_email' => 'updated@example.com',
    ]);
});

test('validates customer email field during update', function () {
    $order = Order::factory()->create();

    $response = actingAsSuperAdmin()->patch(route('admin.orders.update', $order), [
        'customer_email' => 'invalid-email',
    ]);

    $response->assertRedirectBack()
        ->assertInvalid('customer_email');

    $response = actingAsSuperAdmin()->patch(route('admin.orders.update', $order), [
        'customer_email' => str_repeat('a', 250) . '@example.com', // Exceeds max:255
    ]);

    $response->assertRedirectBack()
        ->assertInvalid('customer_email');
});

test('validates foreign key relationships during update', function () {
    $order = Order::factory()->create();

    $response = actingAsSuperAdmin()->patch(route('admin.orders.update', $order), [
        'shipping_rate_id' => 99999, // Non-existent shipping rate
    ]);

    $response->assertRedirectBack()
        ->assertInvalid('shipping_rate_id');

    $response = actingAsSuperAdmin()->patch(route('admin.orders.update', $order), [
        'payment_gateway_id' => 99999, // Non-existent payment gateway
    ]);

    $response->assertRedirectBack()
        ->assertInvalid('payment_gateway_id');
});

test('validates notes field during update', function () {
    $order = Order::factory()->create();

    $response = actingAsSuperAdmin()->patch(route('admin.orders.update', $order), [
        'notes' => str_repeat('a', 1001), // Exceeds max:1000
    ]);

    $response->assertRedirectBack()
        ->assertInvalid('notes');
});

test('validates billing address fields during update', function () {
    $order = Order::factory()->create();

    $response = actingAsSuperAdmin()->patch(route('admin.orders.update', $order), [
        'billing_address' => [
            'first_name' => '', // Required when billing_address is provided
            'last_name' => 'Doe',
            'address_line_1' => '123 Main St',
            'city' => 'New York',
            'state' => 'NY',
            'postal_code' => '10001',
            'country_code' => 'US',
        ],
    ]);

    $response->assertRedirectBack()
        ->assertInvalid('billing_address.first_name');

    $response = actingAsSuperAdmin()->patch(route('admin.orders.update', $order), [
        'billing_address' => [
            'first_name' => str_repeat('a', 256), // Exceeds max:255
            'last_name' => 'Doe',
            'address_line_1' => '123 Main St',
            'city' => 'New York',
            'state' => 'NY',
            'postal_code' => '10001',
            'country_code' => 'US',
        ],
    ]);

    $response->assertRedirectBack()
        ->assertInvalid('billing_address.first_name');

    $response = actingAsSuperAdmin()->patch(route('admin.orders.update', $order), [
        'billing_address' => [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'address_line_1' => '123 Main St',
            'city' => 'New York',
            'state' => 'NY',
            'postal_code' => '10001',
            'country_code' => 'INVALID', // Invalid country code size
        ],
    ]);

    $response->assertRedirectBack()
        ->assertInvalid('billing_address.country_code');
});

test('validates shipping address fields during update when provided', function () {
    $order = Order::factory()->create();

    $response = actingAsSuperAdmin()->patch(route('admin.orders.update', $order), [
        'shipping_address' => [
            'first_name' => '', // Required when shipping_address is provided
            'last_name' => 'Doe',
            'address_line_1' => '456 Oak Ave',
            'city' => 'Brooklyn',
            'state' => 'NY',
            'postal_code' => '11201',
            'country_code' => 'US',
        ],
    ]);

    $response->assertRedirectBack()
        ->assertInvalid('shipping_address.first_name');

    $response = actingAsSuperAdmin()->patch(route('admin.orders.update', $order), [
        'shipping_address' => [
            'first_name' => str_repeat('a', 256), // Exceeds max:255
            'last_name' => 'Doe',
            'address_line_1' => '456 Oak Ave',
            'city' => 'Brooklyn',
            'state' => 'NY',
            'postal_code' => '11201',
            'country_code' => 'US',
        ],
    ]);

    $response->assertRedirectBack()
        ->assertInvalid('shipping_address.first_name');

    $response = actingAsSuperAdmin()->patch(route('admin.orders.update', $order), [
        'shipping_address' => [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'address_line_1' => '456 Oak Ave',
            'city' => 'Brooklyn',
            'state' => 'NY',
            'postal_code' => '11201',
            'country_code' => 'INVALID', // Invalid country code size
        ],
    ]);

    $response->assertRedirectBack()
        ->assertInvalid('shipping_address.country_code');
});

test('validates address line length during update', function () {
    $order = Order::factory()->create();

    $response = actingAsSuperAdmin()->patch(route('admin.orders.update', $order), [
        'billing_address' => [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'address_line_1' => str_repeat('a', 256), // Exceeds max:255
            'city' => 'New York',
            'state' => 'NY',
            'postal_code' => '10001',
            'country_code' => 'US',
        ],
    ]);

    $response->assertRedirectBack()
        ->assertInvalid('billing_address.address_line_1');

    $response = actingAsSuperAdmin()->patch(route('admin.orders.update', $order), [
        'billing_address' => [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'address_line_1' => '123 Main St',
            'address_line_2' => str_repeat('a', 256), // Exceeds max:255
            'city' => 'New York',
            'state' => 'NY',
            'postal_code' => '10001',
            'country_code' => 'US',
        ],
    ]);

    $response->assertRedirectBack()
        ->assertInvalid('billing_address.address_line_2');
});

test('validates city and state length during update', function () {
    $order = Order::factory()->create();

    $response = actingAsSuperAdmin()->patch(route('admin.orders.update', $order), [
        'billing_address' => [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'address_line_1' => '123 Main St',
            'city' => str_repeat('a', 256), // Exceeds max:255
            'state' => 'NY',
            'postal_code' => '10001',
            'country_code' => 'US',
        ],
    ]);

    $response->assertRedirectBack()
        ->assertInvalid('billing_address.city');

    $response = actingAsSuperAdmin()->patch(route('admin.orders.update', $order), [
        'billing_address' => [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'address_line_1' => '123 Main St',
            'city' => 'Paris',
            'state' => str_repeat('a', 256), // Exceeds max:255 (free-text state country)
            'postal_code' => '75001',
            'country_code' => 'FR',
        ],
    ]);

    $response->assertRedirectBack()
        ->assertInvalid('billing_address.state');
});

test('validates postal_code and phone length during update', function () {
    $order = Order::factory()->create();

    $response = actingAsSuperAdmin()->patch(route('admin.orders.update', $order), [
        'billing_address' => [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'address_line_1' => '123 Main St',
            'city' => 'New York',
            'state' => 'NY',
            'postal_code' => str_repeat('1', 21), // Exceeds max:20
            'country_code' => 'US',
        ],
    ]);

    $response->assertRedirectBack()
        ->assertInvalid('billing_address.postal_code');

    $response = actingAsSuperAdmin()->patch(route('admin.orders.update', $order), [
        'billing_address' => [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'address_line_1' => '123 Main St',
            'city' => 'New York',
            'state' => 'NY',
            'postal_code' => '10001',
            'country_code' => 'US',
            'phone' => str_repeat('1', 21), // Exceeds max:20
        ],
    ]);

    $response->assertRedirectBack()
        ->assertInvalid('billing_address.phone');
});

test('preserves shipping address when not provided in update', function () {
    $order = Order::factory()->create();

    // Create both billing and shipping addresses
    OrderAddress::factory()->create([
        'order_id' => $order->id,
        'type' => OrderAddressType::Billing,
    ]);

    $shippingAddress = OrderAddress::factory()->create([
        'order_id' => $order->id,
        'type' => OrderAddressType::Shipping,
    ]);

    $data = [
        'customer_email' => 'updated@example.com',
        'billing_address' => [
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'address_line_1' => '789 Updated St',
            'city' => 'Los Angeles',
            'state' => 'CA',
            'postal_code' => '90210',
            'country_code' => 'US',
        ],
        // shipping_address is not provided, should remain unchanged
    ];

    $response = actingAsSuperAdmin()->patch(route('admin.orders.update', $order), $data);

    $response->assertRedirect()
        ->assertSessionHasNoErrors();

    // Billing address should still exist
    assertDatabaseHas('order_addresses', [
        'order_id' => $order->id,
        'type' => OrderAddressType::Billing->value,
    ]);

    // Shipping address should remain unchanged
    assertDatabaseHas('order_addresses', [
        'id' => $shippingAddress->id,
        'order_id' => $order->id,
        'type' => OrderAddressType::Shipping->value,
    ]);
});

test('updates order without addresses when none provided', function () {
    $order = Order::factory()->create([
        'customer_email' => 'original@example.com',
    ]);

    $data = [
        'customer_email' => 'updated@example.com',
        'notes' => 'Updated notes only',
    ];

    $response = actingAsSuperAdmin()->patch(route('admin.orders.update', $order), $data);

    $response->assertRedirect()
        ->assertSessionHasNoErrors();

    assertDatabaseHas('orders', [
        'id' => $order->id,
        'customer_email' => 'updated@example.com',
        'notes' => 'Updated notes only',
    ]);
});

test('updates order currency fields', function () {
    Currency::factory()->create([
        'code' => 'EUR',
        'exchange_rate' => '0.8500',
    ]);

    $order = Order::factory()->create([
        'currency_code' => 'USD',
        'exchange_rate' => '1.0',
    ]);

    $data = [
        'currency_code' => 'EUR',
    ];

    $response = actingAsSuperAdmin()->patch(route('admin.orders.update', $order), $data);

    $response->assertRedirect()
        ->assertSessionHasNoErrors();

    assertDatabaseHas('orders', [
        'id' => $order->id,
        'currency_code' => 'EUR',
        'exchange_rate' => '0.8500',
    ]);
});

test('updates order coupon code', function () {
    $order = Order::factory()->create([
        'coupon_code' => 'OLDCODE',
    ]);

    Coupon::factory()->valid()->create([
        'code' => 'NEWCODE2024',
        'type' => CouponType::Flat,
        'value' => '10.0000',
        'is_active' => true,
    ]);

    $data = [
        'coupon_code' => 'NEWCODE2024',
        'customer_email' => $order->customer_email,
    ];

    $response = actingAsSuperAdmin()->patch(route('admin.orders.update', $order), $data);

    $response->assertRedirect()
        ->assertSessionHasNoErrors();

    assertDatabaseHas('orders', [
        'id' => $order->id,
        'coupon_code' => 'NEWCODE2024',
    ]);
});

test('updates order with new items', function () {
    $order = Order::factory()->create();
    $product1 = Product::factory()->create(['price' => '2.5000', 'track_stock' => false]);
    $product2 = Product::factory()->create(['price' => '3.0000', 'track_stock' => false]);

    $data = [
        'items' => [
            [
                'product_id' => $product1->id,
                'quantity' => 2,
            ],
            [
                'product_id' => $product2->id,
                'quantity' => 1,
            ],
        ],
    ];

    $response = actingAsSuperAdmin()->patch(route('admin.orders.update', $order), $data);

    $response->assertRedirect()
        ->assertSessionHasNoErrors();

    assertDatabaseHas('order_items', [
        'order_id' => $order->id,
        'product_id' => $product1->id,
        'quantity' => 2,
    ]);

    assertDatabaseHas('order_items', [
        'order_id' => $order->id,
        'product_id' => $product2->id,
        'quantity' => 1,
    ]);

    $order->refresh();

    assertDatabaseHas('orders', [
        'id' => $order->id,
        'subtotal' => '8.0000', // (2.50 * 2) + (3.00 * 1) = 5.00 + 3.00
    ]);

    assertDatabaseHas('order_items', [
        'order_id' => $order->id,
        'product_id' => $product1->id,
        'quantity' => 2,
        'unit_price' => '2.5000', // From product price
        'total_price' => '5.0000', // 2.50 * 2
    ]);

    assertDatabaseHas('order_items', [
        'order_id' => $order->id,
        'product_id' => $product2->id,
        'quantity' => 1,
        'unit_price' => '3.0000', // From product price
        'total_price' => '3.0000', // 3.00 * 1
    ]);
});

test('updates existing order items and manages deletions', function () {
    $order = Order::factory()->create();
    $product1 = Product::factory()->create(['price' => '2.0000', 'track_stock' => false]);
    $product2 = Product::factory()->create(['price' => '1.5000', 'track_stock' => false]);
    $product3 = Product::factory()->create(['price' => '4.0000', 'track_stock' => false]);

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
        'items' => [
            [
                'id' => $item1->id, // Update existing item
                'product_id' => $product1->id,
                'quantity' => 3, // Changed quantity
            ],
            [
                // New item (no ID provided)
                'product_id' => $product3->id,
                'quantity' => 1,
            ],
            // item2 is not included, so it should be deleted
        ],
    ];

    $response = actingAsSuperAdmin()->patch(route('admin.orders.update', $order), $data);

    $response->assertRedirect()
        ->assertSessionHasNoErrors();

    assertDatabaseHas('order_items', [
        'id' => $item1->id,
        'order_id' => $order->id,
        'product_id' => $product1->id,
        'quantity' => 3,
        'unit_price' => '2.0000', // From product price
        'total_price' => '6.0000', // 2.00 * 3
    ]);

    assertDatabaseHas('order_items', [
        'order_id' => $order->id,
        'product_id' => $product3->id,
        'quantity' => 1,
        'unit_price' => '4.0000', // From product price
        'total_price' => '4.0000', // 4.00 * 1
    ]);

    assertDatabaseMissing('order_items', [
        'id' => $item2->id,
    ]);

    $order->refresh();

    assertDatabaseHas('orders', [
        'id' => $order->id,
        'subtotal' => '10.0000', // (2.00 * 3) + (4.00 * 1) = 6.00 + 4.00
    ]);
});

test('restocks reduced and removed items when restock is checked', function () {
    $order = Order::factory()->create();
    $product1 = Product::factory()->create(['price' => '2.0000', 'track_stock' => true, 'stock' => 5]);
    $product2 = Product::factory()->create(['price' => '1.5000', 'track_stock' => true, 'stock' => 5]);

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

    $response = actingAsSuperAdmin()->patch(route('admin.orders.update', $order), [
        'restock' => true,
        'items' => [
            [
                'id' => $item1->id,
                'product_id' => $product1->id,
                'quantity' => 1,
            ],
            // item2 is not included, so it should be deleted
        ],
    ]);

    $response->assertRedirect()
        ->assertSessionHasNoErrors();

    expect($product1->refresh()->stock)->toBe(7);
    expect($product2->refresh()->stock)->toBe(7);

    assertDatabaseMissing('order_items', [
        'id' => $item2->id,
    ]);
});

test('does not restock reduced and removed items when restock is unchecked', function () {
    $order = Order::factory()->create();
    $product1 = Product::factory()->create(['price' => '2.0000', 'track_stock' => true, 'stock' => 5]);
    $product2 = Product::factory()->create(['price' => '1.5000', 'track_stock' => true, 'stock' => 5]);

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

    $response = actingAsSuperAdmin()->patch(route('admin.orders.update', $order), [
        'restock' => false,
        'items' => [
            [
                'id' => $item1->id,
                'product_id' => $product1->id,
                'quantity' => 1,
            ],
        ],
    ]);

    $response->assertRedirect()
        ->assertSessionHasNoErrors();

    expect($product1->refresh()->stock)->toBe(5);
    expect($product2->refresh()->stock)->toBe(5);

    assertDatabaseMissing('order_items', [
        'id' => $item2->id,
    ]);
});

test('validates restock field during update', function () {
    $order = Order::factory()->create();

    $response = actingAsSuperAdmin()->patch(route('admin.orders.update', $order), [
        'restock' => 'invalid',
    ]);

    $response->assertRedirectBack()
        ->assertInvalid(['restock']);
});

test('validates order items structure', function () {
    $order = Order::factory()->create();
    $product = Product::factory()->create(['price' => '10.0000', 'track_stock' => false]);

    $response = actingAsSuperAdmin()->patch(route('admin.orders.update', $order), [
        'items' => [
            [
                'product_id' => $product->id,
                // Missing quantity
            ],
        ],
    ]);

    $response->assertRedirectBack()
        ->assertInvalid(['items.0.quantity']);

    $response = actingAsSuperAdmin()->patch(route('admin.orders.update', $order), [
        'items' => [
            [
                'product_id' => $product->id,
                'quantity' => 'invalid', // Should be integer
            ],
        ],
    ]);

    $response->assertRedirectBack()
        ->assertInvalid(['items.0.quantity']);
});

test('rejects item id belonging to a different order', function () {
    $order = Order::factory()->create();
    $otherOrder = Order::factory()->create();
    $product = Product::factory()->create(['price' => '10.0000', 'track_stock' => false]);

    $otherItem = OrderItem::factory()->create([
        'order_id' => $otherOrder->id,
        'product_id' => $product->id,
        'quantity' => 1,
    ]);

    $response = actingAsSuperAdmin()->patch(route('admin.orders.update', $order), [
        'items' => [
            [
                'id' => $otherItem->id,
                'product_id' => $product->id,
                'quantity' => 1,
            ],
        ],
    ]);

    $response->assertRedirectBack()
        ->assertInvalid(['items.0.id']);
});

test('sends customer notification when notify_customer is true', function () {
    Notification::fake();

    $customer = User::factory()->create();
    $order = Order::factory()->create([
        'customer_id' => $customer->id,
        'customer_email' => $customer->email,
    ]);

    $response = actingAsSuperAdmin()->patch(route('admin.orders.update', $order), [
        'customer_email' => $customer->email,
        'notify_customer' => true,
    ]);

    $response->assertRedirect()
        ->assertSessionHasNoErrors();

    Notification::assertSentTo($customer, CustomerOrderUpdatedNotification::class);
});

test('does not send customer notification when notify_customer is false', function () {
    Notification::fake();

    $customer = User::factory()->create();
    $order = Order::factory()->create([
        'customer_id' => $customer->id,
        'customer_email' => $customer->email,
    ]);

    $response = actingAsSuperAdmin()->patch(route('admin.orders.update', $order), [
        'customer_email' => $customer->email,
        'notify_customer' => false,
    ]);

    $response->assertRedirect()
        ->assertSessionHasNoErrors();

    Notification::assertNotSentTo($customer, CustomerOrderUpdatedNotification::class);
});

test('does not send customer notification when notify_customer is not provided', function () {
    Notification::fake();

    $order = Order::factory()->create();

    $response = actingAsSuperAdmin()->patch(route('admin.orders.update', $order), [
        'customer_email' => 'updated@example.com',
    ]);

    $response->assertRedirect()
        ->assertSessionHasNoErrors();

    Notification::assertNothingSent();
});

test('sends notification to guest email when order has no customer account', function () {
    Notification::fake();

    $order = Order::factory()->create([
        'customer_id' => null,
        'customer_email' => 'guest@example.com',
    ]);

    $response = actingAsSuperAdmin()->patch(route('admin.orders.update', $order), [
        'notes' => 'Updated notes',
        'notify_customer' => true,
    ]);

    $response->assertRedirect()
        ->assertSessionHasNoErrors();

    Notification::assertSentOnDemand(CustomerOrderUpdatedNotification::class);
});

test('exposes shipped quantity per item on the edit page', function () {
    $order = Order::factory()->create();
    $product = Product::factory()->create(['price' => '5.0000', 'track_stock' => false]);
    $shippedItem = OrderItem::factory()->forOrder($order)->create(['product_id' => $product->id, 'quantity' => 4]);
    $unshippedItem = OrderItem::factory()->forOrder($order)->create(['product_id' => $product->id, 'quantity' => 2]);

    $shipment = OrderShipment::factory()->create(['order_id' => $order->id]);
    OrderShipmentItem::factory()->create([
        'order_shipment_id' => $shipment->id,
        'order_item_id' => $shippedItem->id,
        'quantity' => 3,
    ]);

    $shippedIndex = $order->items()->orderBy('id')->pluck('id')->search($shippedItem->id);
    $unshippedIndex = $order->items()->orderBy('id')->pluck('id')->search($unshippedItem->id);

    actingAsSuperAdmin()->get(route('admin.orders.edit', $order))
        ->assertOk()
        ->assertInertia(fn (Assert $page): Assert => $page
            ->where("order.items.{$shippedIndex}.shipped_quantity", 3)
            ->where("order.items.{$unshippedIndex}.shipped_quantity", 0));
});

test('cannot reduce an item below its shipped quantity', function () {
    $order = Order::factory()->create();
    $product = Product::factory()->create(['price' => '5.0000', 'track_stock' => false]);
    $item = OrderItem::factory()->forOrder($order)->create(['product_id' => $product->id, 'quantity' => 5]);

    $shipment = OrderShipment::factory()->create(['order_id' => $order->id]);
    OrderShipmentItem::factory()->create([
        'order_shipment_id' => $shipment->id,
        'order_item_id' => $item->id,
        'quantity' => 3,
    ]);

    $response = actingAsSuperAdmin()->patch(route('admin.orders.update', $order), [
        'items' => [['id' => $item->id, 'product_id' => $product->id, 'quantity' => 2]],
    ]);

    $response->assertRedirectBack()->assertInvalid('items');

    assertDatabaseHas('order_items', ['id' => $item->id, 'quantity' => 5]);
});

test('cannot remove an item that has shipped units', function () {
    $order = Order::factory()->create();
    $product = Product::factory()->create(['price' => '5.0000', 'track_stock' => false]);
    $item = OrderItem::factory()->forOrder($order)->create(['product_id' => $product->id, 'quantity' => 2]);

    $shipment = OrderShipment::factory()->create(['order_id' => $order->id]);
    OrderShipmentItem::factory()->create([
        'order_shipment_id' => $shipment->id,
        'order_item_id' => $item->id,
        'quantity' => 2,
    ]);

    $other = Product::factory()->create(['price' => '3.0000', 'track_stock' => false]);

    $response = actingAsSuperAdmin()->patch(route('admin.orders.update', $order), [
        'items' => [['product_id' => $other->id, 'quantity' => 1]],
    ]);

    $response->assertRedirectBack()->assertInvalid('items');

    assertDatabaseHas('order_items', ['id' => $item->id]);
});

test('allows increasing the quantity of a shipped item', function () {
    $order = Order::factory()->create();
    $product = Product::factory()->create(['price' => '5.0000', 'track_stock' => false]);
    $item = OrderItem::factory()->forOrder($order)->create(['product_id' => $product->id, 'quantity' => 3]);

    $shipment = OrderShipment::factory()->create(['order_id' => $order->id]);
    OrderShipmentItem::factory()->create([
        'order_shipment_id' => $shipment->id,
        'order_item_id' => $item->id,
        'quantity' => 3,
    ]);

    $response = actingAsSuperAdmin()->patch(route('admin.orders.update', $order), [
        'items' => [['id' => $item->id, 'product_id' => $product->id, 'quantity' => 5]],
    ]);

    $response->assertRedirect()->assertSessionHasNoErrors();

    assertDatabaseHas('order_items', ['id' => $item->id, 'quantity' => 5]);
});

test('allows removing an unshipped item while keeping shipped items', function () {
    $order = Order::factory()->create();
    $shippedProduct = Product::factory()->create(['price' => '5.0000', 'track_stock' => false]);
    $unshippedProduct = Product::factory()->create(['price' => '3.0000', 'track_stock' => false]);

    $shippedItem = OrderItem::factory()->forOrder($order)->create(['product_id' => $shippedProduct->id, 'quantity' => 2]);
    $unshippedItem = OrderItem::factory()->forOrder($order)->create(['product_id' => $unshippedProduct->id, 'quantity' => 4]);

    $shipment = OrderShipment::factory()->create(['order_id' => $order->id]);
    OrderShipmentItem::factory()->create([
        'order_shipment_id' => $shipment->id,
        'order_item_id' => $shippedItem->id,
        'quantity' => 2,
    ]);

    $response = actingAsSuperAdmin()->patch(route('admin.orders.update', $order), [
        'items' => [['id' => $shippedItem->id, 'product_id' => $shippedProduct->id, 'quantity' => 2]],
    ]);

    $response->assertRedirect()->assertSessionHasNoErrors();

    assertDatabaseHas('order_items', ['id' => $shippedItem->id]);
    assertDatabaseMissing('order_items', ['id' => $unshippedItem->id]);
});

test('reconciles fulfillment status when items are added to a fulfilled order', function () {
    $order = Order::factory()->create(['fulfillment_status' => FulfillmentStatus::Fulfilled]);
    $product = Product::factory()->create(['price' => '5.0000', 'track_stock' => false]);
    $item = OrderItem::factory()->forOrder($order)->create([
        'product_id' => $product->id,
        'quantity' => 1,
    ]);

    $shipment = OrderShipment::factory()->create(['order_id' => $order->id]);
    OrderShipmentItem::factory()->create([
        'order_shipment_id' => $shipment->id,
        'order_item_id' => $item->id,
        'quantity' => 1,
    ]);

    $newProduct = Product::factory()->create(['price' => '3.0000', 'track_stock' => false]);

    $response = actingAsSuperAdmin()->patch(route('admin.orders.update', $order), [
        'items' => [
            ['id' => $item->id, 'product_id' => $product->id, 'quantity' => 1],
            ['product_id' => $newProduct->id, 'quantity' => 1],
        ],
    ]);

    $response->assertRedirect()->assertSessionHasNoErrors();

    expect($order->fresh()->fulfillment_status)->toBe(FulfillmentStatus::InProgress);
});

test('requires authentication', function () {
    $order = Order::factory()->create();

    $response = get(route('admin.orders.edit', $order));

    $response->assertRedirect(route('admin.login'));

    $response = patch(route('admin.orders.update', $order), [
        'customer_email' => 'updated@example.com',
    ]);

    $response->assertRedirect(route('admin.login'));
});

test('requires orders.update permission', function () {
    $role = Role::query()->where(['name' => RoleEnum::Admin])->firstOrFail();
    $order = Order::factory()->create(['customer_email' => 'original@example.com']);

    $response = actingAsAdmin()->get(route('admin.orders.edit', $order));

    $response->assertOk();

    $response = actingAsAdmin()->patch(route('admin.orders.update', $order), [
        'customer_email' => 'updated@example.com',
    ]);

    $response->assertRedirectBack()
        ->assertSessionHasNoErrors();

    assertDatabaseHas('orders', [
        'id' => $order->id,
        'customer_email' => 'updated@example.com',
    ]);

    $role->revokePermissionTo(Permission::OrdersManage);

    $anotherOrder = Order::factory()->create(['customer_email' => 'another@example.com']);

    $response = actingAsAdmin()->get(route('admin.orders.edit', $anotherOrder));

    $response->assertForbidden();

    $response = actingAsAdmin()->patch(route('admin.orders.update', $anotherOrder), [
        'customer_email' => 'forbidden@example.com',
    ]);

    $response->assertForbidden();

    assertDatabaseMissing('orders', [
        'customer_email' => 'forbidden@example.com',
    ]);
});
