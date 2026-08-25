<?php

declare(strict_types=1);

use App\Enums\FulfillmentStatus;
use App\Enums\OrderAddressType;
use App\Enums\PaymentStatus;
use App\Enums\Permission;
use App\Enums\Role as RoleEnum;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Requests\Admin\StoreOrderRequest;
use App\Models\Order;
use App\Models\PaymentGateway;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Region;
use App\Models\Setting;
use App\Models\ShippingCarrier;
use App\Models\ShippingRate;
use App\Models\User;
use App\Notifications\CustomerOrderConfirmedNotification;
use App\Rules\SellingCountryRule;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Laravel\castAsJson;
use function Pest\Laravel\post;

covers(OrderController::class, StoreOrderRequest::class, SellingCountryRule::class, CustomerOrderConfirmedNotification::class);

uses()->group('order');

test('renders the admin order create form', function () {
    PaymentGateway::factory()->cod()->active()->create();

    actingAsSuperAdmin()
        ->get(route('admin.orders.create'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('admin/orders/create'));
});

test('passes the store country code to the admin order create form', function () {
    PaymentGateway::factory()->cod()->active()->create();
    Setting::setValue('store_country_code', 'BD');

    actingAsSuperAdmin()
        ->get(route('admin.orders.create'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('storeCountryCode', 'BD'));
});

test('add_more flag keeps admin on create page after storing', function () {
    $product = Product::factory()->create(['price' => '50.0000', 'track_stock' => false]);
    $shippingCarrier = ShippingCarrier::factory()->create();
    $region = Region::factory()->create(['countries' => []]);
    $shippingRate = ShippingRate::factory()->for($shippingCarrier, 'carrier')->for($region)->create(['rate' => '5.0000']);
    $paymentGateway = PaymentGateway::factory()->cod()->create();

    $response = actingAsSuperAdmin()->post(route('admin.orders.store'), [
        'customer_email' => 'customer@example.com',
        'items' => [['product_id' => $product->id, 'product_variant_id' => null, 'quantity' => 1]],
        'shipping_address' => [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'address_line_1' => '123 Main',
            'city' => 'NY',
            'state' => 'NY',
            'postal_code' => '10001',
            'country_code' => 'US',
        ],
        'different_billing_address' => false,
        'shipping_rate_id' => $shippingRate->id,
        'payment_gateway_id' => $paymentGateway->id,
        'add_more' => true,
    ]);

    $response->assertRedirectBack()->assertSessionHasNoErrors();
});

test('creating an order rejects a country the store does not support', function () {
    Setting::setValue('selling_countries', ['BD']);

    $product = Product::factory()->create(['price' => '50.0000', 'track_stock' => false]);
    $shippingCarrier = ShippingCarrier::factory()->create();
    $region = Region::factory()->create(['countries' => []]);
    $shippingRate = ShippingRate::factory()->for($shippingCarrier, 'carrier')->for($region)->create(['rate' => '5.0000']);
    $paymentGateway = PaymentGateway::factory()->cod()->create();

    actingAsSuperAdmin()
        ->post(route('admin.orders.store'), [
            'customer_email' => 'customer@example.com',
            'items' => [['product_id' => $product->id, 'product_variant_id' => null, 'quantity' => 1]],
            'shipping_address' => [
                'first_name' => 'John',
                'last_name' => 'Doe',
                'address_line_1' => '123 Main',
                'city' => 'NY',
                'state' => 'NY',
                'postal_code' => '10001',
                'country_code' => 'US',
            ],
            'different_billing_address' => false,
            'shipping_rate_id' => $shippingRate->id,
            'payment_gateway_id' => $paymentGateway->id,
        ])
        ->assertInvalid('shipping_address.country_code');
});

test('creates a new order', function () {
    $product = Product::factory()->create(['price' => '50.0000', 'track_stock' => false]);
    $shippingCarrier = ShippingCarrier::factory()->create();
    $region = Region::factory()->create();
    $shippingRate = ShippingRate::factory()->create([
        'shipping_carrier_id' => $shippingCarrier->id,
        'region_id' => $region->id,
        'rate' => '15.0000',
    ]);
    $paymentGateway = PaymentGateway::factory()->create();

    $data = [
        'customer_email' => 'john@example.com',
        'currency_code' => 'USD',
        'shipping_rate_id' => $shippingRate->id,
        'payment_gateway_id' => $paymentGateway->id,
        'notes' => 'Test order notes',
        'items' => [
            [
                'product_id' => $product->id,
                'quantity' => 2,
                'variant_options' => null,
            ],
        ],
        'shipping_address' => [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'address_line_1' => '456 Oak Ave',
            'address_line_2' => null,
            'city' => 'Brooklyn',
            'state' => 'NY',
            'postal_code' => '11201',
            'country_code' => 'US',
            'phone' => '+14155552671',
        ],
        'different_billing_address' => true,
        'billing_address' => [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'address_line_1' => '123 Main St',
            'address_line_2' => 'Apt 4B',
            'city' => 'New York',
            'state' => 'NY',
            'postal_code' => '10001',
            'country_code' => 'US',
            'phone' => '+14155552671',
        ],
    ];

    $response = actingAsSuperAdmin()->post(route('admin.orders.store'), $data);

    $response->assertSessionHasNoErrors();

    $order = Order::where('customer_email', 'john@example.com')->first();
    $response->assertRedirect(route('admin.orders.show', $order));

    assertDatabaseHas('orders', [
        'payment_status' => PaymentStatus::Unpaid->value,
        'fulfillment_status' => FulfillmentStatus::Unfulfilled->value,
        'customer_email' => 'john@example.com',
        'shipping_carrier_id' => $shippingCarrier->id,
        'shipping_carrier_name' => castAsJson($shippingCarrier->getTranslations('name')),
        'shipping_rate_id' => $shippingRate->id,
        'shipping_rate_name' => castAsJson($shippingRate->getTranslations('name')),
        'payment_gateway_id' => $paymentGateway->id,
        'payment_gateway_name' => castAsJson($paymentGateway->getTranslations('name')),
        'subtotal' => '100.0000', // 50.00 * 2
        'shipping_total' => '15.0000', // From shipping rate
    ]);

    $order = Order::where('customer_email', 'john@example.com')->first();

    assertDatabaseHas('order_items', [
        'order_id' => $order->id,
        'product_id' => $product->id,
        'quantity' => 2,
    ]);

    assertDatabaseHas('order_addresses', [
        'order_id' => $order->id,
        'type' => OrderAddressType::Billing->value,
        'first_name' => 'John',
        'address_line_1' => '123 Main St',
        'city' => 'New York',
    ]);

    assertDatabaseHas('order_addresses', [
        'order_id' => $order->id,
        'type' => OrderAddressType::Shipping->value,
        'first_name' => 'John',
        'address_line_1' => '456 Oak Ave',
        'city' => 'Brooklyn',
    ]);

    assertDatabaseHas('orders', [
        'id' => $order->id,
        'subtotal' => '100.0000', // 50.00 * 2
        'shipping_total' => '15.0000', // From shipping rate
    ]);

    assertDatabaseHas('order_items', [
        'order_id' => $order->id,
        'product_id' => $product->id,
        'quantity' => 2,
        'unit_price' => '50.0000', // From product price
        'total_price' => '100.0000', // 50.00 * 2
    ]);
});

test('validates required fields', function () {

    $response = actingAsSuperAdmin()->post(route('admin.orders.store'), [
        'customer_email' => '',
        'items' => [],
        'shipping_address' => [],
    ]);

    $response->assertRedirectBack()
        ->assertInvalid([
            'customer_email',
            'items',
            'shipping_address',
        ]);
});

test('validates customer email field', function () {
    $product = Product::factory()->create(['price' => '10.0000', 'track_stock' => false]);

    $response = actingAsSuperAdmin()->post(route('admin.orders.store'), [
        'customer_email' => 'invalid-email',
        'items' => [
            [
                'product_id' => $product->id,
                'quantity' => 1,
            ],
        ],
        'shipping_address' => [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'address_line_1' => '123 Main St',
            'city' => 'New York',
            'state' => 'NY',
            'postal_code' => '10001',
            'country_code' => 'US',
        ],
    ]);

    $response->assertRedirectBack()
        ->assertInvalid('customer_email');

    $response = actingAsSuperAdmin()->post(route('admin.orders.store'), [
        'customer_email' => str_repeat('a', 250) . '@example.com', // Exceeds max:255
        'items' => [
            [
                'product_id' => $product->id,
                'quantity' => 1,
            ],
        ],
        'shipping_address' => [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'address_line_1' => '123 Main St',
            'city' => 'New York',
            'state' => 'NY',
            'postal_code' => '10001',
            'country_code' => 'US',
        ],
    ]);

    $response->assertRedirectBack()
        ->assertInvalid('customer_email');
});

test('validates currency code field', function () {
    $product = Product::factory()->create(['price' => '10.0000', 'track_stock' => false]);

    $response = actingAsSuperAdmin()->post(route('admin.orders.store'), [
        'customer_email' => 'john@example.com',
        'currency_code' => 'INVALID', // Invalid size, should be 3 characters
        'items' => [
            [
                'product_id' => $product->id,
                'quantity' => 1,
            ],
        ],
        'shipping_address' => [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'address_line_1' => '123 Main St',
            'city' => 'New York',
            'state' => 'NY',
            'postal_code' => '10001',
            'country_code' => 'US',
        ],
    ]);

    $response->assertRedirectBack()
        ->assertInvalid('currency_code');
});

test('validates foreign key relationships', function () {
    $product = Product::factory()->create(['price' => '10.0000', 'track_stock' => false]);

    $response = actingAsSuperAdmin()->post(route('admin.orders.store'), [
        'customer_email' => 'john@example.com',
        'shipping_rate_id' => 99999, // Non-existent shipping rate
        'payment_gateway_id' => 99999, // Non-existent payment gateway
        'items' => [
            [
                'product_id' => $product->id,
                'quantity' => 1,
            ],
        ],
        'shipping_address' => [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'address_line_1' => '123 Main St',
            'city' => 'New York',
            'state' => 'NY',
            'postal_code' => '10001',
            'country_code' => 'US',
        ],
    ]);

    $response->assertRedirectBack()
        ->assertInvalid(['shipping_rate_id', 'payment_gateway_id']);
});

test('validates order items', function () {

    $response = actingAsSuperAdmin()->post(route('admin.orders.store'), [
        'customer_email' => 'john@example.com',
        'items' => [
            [
                'product_id' => 99999, // Non-existent product
                'quantity' => 1,
            ],
        ],
        'shipping_address' => [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'address_line_1' => '123 Main St',
            'city' => 'New York',
            'state' => 'NY',
            'postal_code' => '10001',
            'country_code' => 'US',
        ],
    ]);

    $response->assertRedirectBack()
        ->assertInvalid('items.0.product_id');

    $product = Product::factory()->create(['price' => '10.0000', 'track_stock' => false]);

    $response = actingAsSuperAdmin()->post(route('admin.orders.store'), [
        'customer_email' => 'john@example.com',
        'items' => [
            [
                'product_id' => $product->id,
                'quantity' => 0, // Invalid quantity
            ],
        ],
        'shipping_address' => [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'address_line_1' => '123 Main St',
            'city' => 'New York',
            'state' => 'NY',
            'postal_code' => '10001',
            'country_code' => 'US',
        ],
    ]);

    $response->assertRedirectBack()
        ->assertInvalid('items.0.quantity');
});

test('validates product variant belongs to product', function () {
    $product1 = Product::factory()->create(['price' => '10.0000', 'track_stock' => false]);
    $product2 = Product::factory()->create(['price' => '20.0000', 'track_stock' => false]);
    $variant = ProductVariant::factory()->create(['product_id' => $product2->id, 'track_stock' => false]);

    $response = actingAsSuperAdmin()->post(route('admin.orders.store'), [
        'customer_email' => 'john@example.com',
        'items' => [
            [
                'product_id' => $product1->id,
                'product_variant_id' => $variant->id, // Variant belongs to different product
                'quantity' => 1,
            ],
        ],
        'shipping_address' => [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'address_line_1' => '123 Main St',
            'city' => 'New York',
            'state' => 'NY',
            'postal_code' => '10001',
            'country_code' => 'US',
        ],
    ]);

    $response->assertRedirectBack()
        ->assertInvalid('items.0.product_variant_id');
});

test('calculates amounts using variant price when variant is selected', function () {
    $product = Product::factory()->create(['price' => '50.0000', 'track_stock' => false]);
    $variant = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'price' => '75.0000',
        'track_stock' => false,
    ]);

    $response = actingAsSuperAdmin()->post(route('admin.orders.store'), [
        'customer_email' => 'variant@example.com',
        'items' => [
            [
                'product_id' => $product->id,
                'product_variant_id' => $variant->id,
                'quantity' => 3,
            ],
        ],
        'shipping_address' => [
            'first_name' => 'Variant',
            'last_name' => 'Test',
            'address_line_1' => '123 Main St',
            'city' => 'New York',
            'state' => 'NY',
            'postal_code' => '10001',
            'country_code' => 'US',
        ],
    ]);

    $response->assertSessionHasNoErrors();

    $order = Order::where('customer_email', 'variant@example.com')->first();

    assertDatabaseHas('orders', [
        'id' => $order->id,
        'subtotal' => '225.0000', // 75.00 * 3 (variant price, not product price)
    ]);

    assertDatabaseHas('order_items', [
        'order_id' => $order->id,
        'product_id' => $product->id,
        'product_variant_id' => $variant->id,
        'quantity' => 3,
        'unit_price' => '75.0000', // From variant price, not product price (50.00)
        'total_price' => '225.0000', // 75.00 * 3
    ]);
});

test('validates shipping address fields', function () {
    $product = Product::factory()->create(['price' => '10.0000', 'track_stock' => false]);

    $response = actingAsSuperAdmin()->post(route('admin.orders.store'), [
        'customer_email' => 'john@example.com',
        'items' => [
            [
                'product_id' => $product->id,
                'quantity' => 1,
            ],
        ],
        'shipping_address' => [
            'first_name' => '', // Required field
            'last_name' => 'Doe',
            'address_line_1' => '123 Main St',
            'city' => 'New York',
            'state' => 'NY',
            'postal_code' => '10001',
            'country_code' => 'US',
        ],
    ]);

    $response->assertRedirectBack()
        ->assertInvalid('shipping_address.first_name');

    $response = actingAsSuperAdmin()->post(route('admin.orders.store'), [
        'customer_email' => 'john@example.com',
        'items' => [
            [
                'product_id' => $product->id,
                'quantity' => 1,
            ],
        ],
        'shipping_address' => [
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
        ->assertInvalid('shipping_address.country_code');
});

test('validates billing address fields when provided', function () {
    $product = Product::factory()->create(['price' => '10.0000', 'track_stock' => false]);

    $response = actingAsSuperAdmin()->post(route('admin.orders.store'), [
        'customer_email' => 'john@example.com',
        'items' => [
            [
                'product_id' => $product->id,
                'quantity' => 1,
            ],
        ],
        'shipping_address' => [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'address_line_1' => '123 Main St',
            'city' => 'New York',
            'state' => 'NY',
            'postal_code' => '10001',
            'country_code' => 'US',
        ],
        'different_billing_address' => true,
        'billing_address' => [
            'first_name' => '', // Required when different_billing_address is true
            'last_name' => 'Doe',
            'address_line_1' => '456 Oak Ave',
            'city' => 'Brooklyn',
            'state' => 'NY',
            'postal_code' => '11201',
            'country_code' => 'US',
        ],
    ]);

    $response->assertRedirectBack()
        ->assertInvalid('billing_address.first_name');

    $response = actingAsSuperAdmin()->post(route('admin.orders.store'), [
        'customer_email' => 'john@example.com',
        'items' => [
            [
                'product_id' => $product->id,
                'quantity' => 1,
            ],
        ],
        'shipping_address' => [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'address_line_1' => '123 Main St',
            'city' => 'New York',
            'state' => 'NY',
            'postal_code' => '10001',
            'country_code' => 'US',
        ],
        'different_billing_address' => true,
        'billing_address' => [
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
        ->assertInvalid('billing_address.country_code');
});

test('redirects back when add_more is true', function () {
    $product = Product::factory()->create(['price' => '50.0000', 'track_stock' => false]);

    $data = [
        'customer_email' => 'addmore@example.com',
        'items' => [
            [
                'product_id' => $product->id,
                'quantity' => 1,
            ],
        ],
        'shipping_address' => [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'address_line_1' => '123 Main St',
            'city' => 'New York',
            'state' => 'NY',
            'postal_code' => '10001',
            'country_code' => 'US',
        ],
        'add_more' => true,
    ];

    $response = actingAsSuperAdmin()->post(route('admin.orders.store'), $data);

    $response->assertRedirectBack()
        ->assertSessionHasNoErrors();

    assertDatabaseHas('orders', [
        'customer_email' => 'addmore@example.com',
    ]);
});

test('requires authentication', function () {
    $product = Product::factory()->create(['price' => '100.0000', 'track_stock' => false]);

    $response = post(route('admin.orders.store'), [
        'customer_email' => 'test@example.com',
        'currency_code' => 'USD',
        'items' => [
            [
                'product_id' => $product->id,
                'quantity' => 1,
            ],
        ],
    ]);

    $response->assertRedirect(route('admin.login'));
});

test('requires orders.create permission', function () {
    $role = Role::query()->where(['name' => RoleEnum::Admin])->firstOrFail();
    $product = Product::factory()->create(['price' => '100.0000', 'track_stock' => false]);

    $response = actingAsAdmin()->get(route('admin.orders.create'));

    $response->assertOk();

    $response = actingAsAdmin()->post(route('admin.orders.store'), [
        'customer_email' => 'test@example.com',
        'currency_code' => 'USD',
        'shipping_address' => [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'address_line_1' => '123 Main St',
            'city' => 'New York',
            'state' => 'NY',
            'postal_code' => '10001',
            'country_code' => 'US',
            'phone' => '+14155552671',
        ],
        'items' => [
            [
                'product_id' => $product->id,
                'quantity' => 1,
            ],
        ],
    ]);

    $response->assertSessionHasNoErrors();

    assertDatabaseHas('orders', [
        'customer_email' => 'test@example.com',
    ]);

    $role->revokePermissionTo(Permission::OrdersManage);

    $response = actingAsAdmin()->get(route('admin.orders.create'));

    $response->assertForbidden();

    $response = actingAsAdmin()->post(route('admin.orders.store'), [
        'customer_email' => 'another@example.com',
        'currency_code' => 'USD',
        'shipping_address' => [
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'address_line_1' => '456 Oak Ave',
            'city' => 'Los Angeles',
            'state' => 'CA',
            'postal_code' => '90001',
            'country_code' => 'US',
            'phone' => '+14155552671',
        ],
        'items' => [
            [
                'product_id' => $product->id,
                'quantity' => 2,
            ],
        ],
    ]);

    $response->assertForbidden();

    assertDatabaseMissing('orders', [
        'customer_email' => 'another@example.com',
    ]);
});

test('confirmation email is sent to the customer, not the acting admin', function () {
    Notification::fake();
    Setting::setValue('notification_customer_order_confirmed', true);

    $admin = User::factory()->create();
    $admin->assignRole(Role::query()->firstOrCreate(['name' => RoleEnum::SuperAdmin]));

    $product = Product::factory()->create(['price' => '50.0000', 'track_stock' => false]);
    $shippingCarrier = ShippingCarrier::factory()->create();
    $region = Region::factory()->create();
    $shippingRate = ShippingRate::factory()->create([
        'shipping_carrier_id' => $shippingCarrier->id,
        'region_id' => $region->id,
        'rate' => '5.0000',
    ]);
    $paymentGateway = PaymentGateway::factory()->cod()->create();

    test()->actingAs($admin)->post(route('admin.orders.store'), [
        'customer_email' => 'shopper@example.com',
        'shipping_rate_id' => $shippingRate->id,
        'payment_gateway_id' => $paymentGateway->id,
        'items' => [['product_id' => $product->id, 'product_variant_id' => null, 'quantity' => 1]],
        'shipping_address' => [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'address_line_1' => '123 Main',
            'city' => 'NY',
            'state' => 'NY',
            'postal_code' => '10001',
            'country_code' => 'US',
        ],
        'different_billing_address' => false,
    ])->assertSessionHasNoErrors();

    Notification::assertNotSentTo($admin, CustomerOrderConfirmedNotification::class);
    Notification::assertSentOnDemand(CustomerOrderConfirmedNotification::class);
});
