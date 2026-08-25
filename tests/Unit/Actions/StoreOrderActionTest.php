<?php

declare(strict_types=1);

use App\Actions\StoreOrderAction;
use App\DTOs\StoreOrderInput;
use App\Enums\CouponType;
use App\Enums\FulfillmentStatus;
use App\Enums\OrderActivityType;
use App\Enums\OrderAddressType;
use App\Enums\PaymentStatus;
use App\Enums\StockMovementReason;
use App\Enums\TaxCategory;
use App\Models\Coupon;
use App\Models\Currency;
use App\Models\Order;
use App\Models\OrderActivity;
use App\Models\OrderAddress;
use App\Models\OrderItem;
use App\Models\PaymentGateway;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Region;
use App\Models\Setting;
use App\Models\ShippingCarrier;
use App\Models\ShippingRate;
use App\Models\StockMovement;
use App\Models\TaxRate;
use App\Notifications\AdminNewOrderNotification;
use App\Notifications\CustomerOrderConfirmedNotification;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Notification;

use function Pest\Laravel\assertDatabaseHas;

covers(StoreOrderAction::class, StoreOrderInput::class);

uses()->group('actions', 'order');

test('creates an order with all required fields, multiple items, and variants', function () {
    actingAsSuperAdmin();
    $product1 = Product::factory()->create(['price' => '50.0000', 'track_stock' => false]);
    $product2 = Product::factory()->create(['price' => '75.0000', 'track_stock' => false]);
    $variant = ProductVariant::factory()->create([
        'product_id' => $product2->id,
        'price' => '85.0000',
        'track_stock' => false,
    ]);

    $data = [
        'customer_email' => 'comprehensive@example.com',
        'items' => [
            [
                'product_id' => $product1->id,
                'quantity' => 2,
            ],
            [
                'product_id' => $product2->id,
                'product_variant_id' => $variant->id,
                'quantity' => 1,
            ],
        ],
        'shipping_address' => [
            'first_name' => 'Comprehensive',
            'last_name' => 'Test',
            'address_line_1' => '123 Main St',
            'city' => 'Test City',
            'state' => 'TC',
            'postal_code' => '12345',
            'country_code' => 'US',
        ],
        'different_billing_address' => false,
    ];

    $result = app(StoreOrderAction::class)->handle(StoreOrderInput::fromArray($data), auth()->user());

    expect($result)->toBeInstanceOf(Order::class)
        ->and($result->customer_email)->toBe('comprehensive@example.com')
        ->and($result->subtotal)->toBe('185.0000')
        ->and($result->total)->toBeString()
        ->and($result->fulfillment_status)->toBe(FulfillmentStatus::Unfulfilled)
        ->and($result->payment_status)->toBe(PaymentStatus::Unpaid);

    expect($result->items)->toHaveCount(2);

    $firstItem = $result->items->first();
    expect($firstItem)->toBeInstanceOf(OrderItem::class)
        ->and($firstItem->product_id)->toBe($product1->id)
        ->and($firstItem->product_variant_id)->toBeNull()
        ->and($firstItem->quantity)->toBe(2)
        ->and($firstItem->unit_price)->toBe('50.0000')
        ->and($firstItem->total_price)->toBe('100.0000');

    $secondItem = $result->items->last();
    expect($secondItem)->toBeInstanceOf(OrderItem::class)
        ->and($secondItem->product_id)->toBe($product2->id)
        ->and($secondItem->product_variant_id)->toBe($variant->id)
        ->and($secondItem->quantity)->toBe(1)
        ->and($secondItem->unit_price)->toBe('85.0000')
        ->and($secondItem->total_price)->toBe('85.0000');

    expect($result->addresses)->toHaveCount(2);

    $billingAddress = $result->addresses->where('type', OrderAddressType::Billing)->first();
    expect($billingAddress)->toBeInstanceOf(OrderAddress::class)
        ->and($billingAddress->first_name)->toBe('Comprehensive')
        ->and($billingAddress->last_name)->toBe('Test')
        ->and($billingAddress->address_line_1)->toBe('123 Main St');

    $shippingAddress = $result->addresses->where('type', OrderAddressType::Shipping)->first();
    expect($shippingAddress)->toBeInstanceOf(OrderAddress::class)
        ->and($shippingAddress->first_name)->toBe('Comprehensive')
        ->and($shippingAddress->last_name)->toBe('Test')
        ->and($shippingAddress->address_line_1)->toBe('123 Main St');

    expect($result->activities)->toHaveCount(1);
    $activity = $result->activities->first();
    expect($activity)->toBeInstanceOf(OrderActivity::class)
        ->and($activity->type)->toBe(OrderActivityType::OrderPlaced);

    expect($result->tax_total)->toBeString();

    assertDatabaseHas('orders', [
        'id' => $result->id,
        'subtotal' => '185.0000', // 100.00 + 85.00
        'shipping_total' => '0.0000', // No shipping rate
    ]);

    assertDatabaseHas('order_items', [
        'order_id' => $result->id,
        'product_id' => $product1->id,
        'quantity' => 2,
        'unit_price' => '50.0000', // From product price
        'total_price' => '100.0000', // 50.00 * 2
    ]);

    assertDatabaseHas('order_items', [
        'order_id' => $result->id,
        'product_id' => $product2->id,
        'product_variant_id' => $variant->id,
        'quantity' => 1,
        'unit_price' => '85.0000', // From variant price
        'total_price' => '85.0000', // 85.00 * 1
    ]);
});

test('throws exception for invalid product', function () {
    actingAsSuperAdmin();
    /** @var array<string, mixed> $data */
    $data = [
        'customer_email' => 'test@example.com',
        'items' => [
            [
                'product_id' => 999999,
                'quantity' => 1,
            ],
        ],
    ];

    expect(fn () => app(StoreOrderAction::class)->handle(StoreOrderInput::fromArray($data), auth()->user()))
        ->toThrow(ModelNotFoundException::class);
});

test('throws exception for invalid product variant', function () {
    actingAsSuperAdmin();
    $product = Product::factory()->create(['price' => '100.0000', 'track_stock' => false]);

    $data = [
        'customer_email' => 'test@example.com',
        'items' => [
            [
                'product_id' => $product->id,
                'product_variant_id' => '00000000-0000-0000-0000-000000000000',
                'quantity' => 1,
            ],
        ],
    ];

    expect(fn () => app(StoreOrderAction::class)->handle(StoreOrderInput::fromArray($data), auth()->user()))
        ->toThrow(ModelNotFoundException::class);
});

test('handles currency scenarios correctly', function () {
    actingAsSuperAdmin();
    $product = Product::factory()->create(['price' => '100.0000', 'track_stock' => false]);

    $currency = Currency::factory()->create([
        'code' => 'EUR',
        'symbol' => '€',
        'exchange_rate' => '0.9200',
        'is_active' => true,
    ]);

    $euroData = [
        'customer_email' => 'euro@example.com',
        'items' => [
            [
                'product_id' => $product->id,
                'quantity' => 1,
            ],
        ],
        'shipping_address' => [
            'first_name' => 'Euro',
            'last_name' => 'Buyer',
            'address_line_1' => '123 Euro St',
            'city' => 'Euro City',
            'state' => 'EU',
            'postal_code' => '11111',
            'country_code' => 'DE',
        ],
        'different_billing_address' => false,
        'currency_code' => 'EUR',
    ];

    $euroOrder = app(StoreOrderAction::class)->handle(StoreOrderInput::fromArray($euroData), auth()->user());
    expect($euroOrder->currency_code)->toBe('EUR')
        ->and($euroOrder->exchange_rate)->toBe('0.9200');

    $defaultData = [
        'customer_email' => 'default@example.com',
        'items' => [
            [
                'product_id' => $product->id,
                'quantity' => 1,
            ],
        ],
        'shipping_address' => [
            'first_name' => 'Default',
            'last_name' => 'Currency',
            'address_line_1' => '123 Default St',
            'city' => 'Default City',
            'state' => 'DC',
            'postal_code' => '22222',
            'country_code' => 'US',
        ],
        'different_billing_address' => false,
    ];

    $defaultOrder = app(StoreOrderAction::class)->handle(StoreOrderInput::fromArray($defaultData), auth()->user());
    expect($defaultOrder->currency_code)->toBe('USD')
        ->and($defaultOrder->exchange_rate)->toBe('1.0000');

    $unknownData = [
        'customer_email' => 'unknown@example.com',
        'items' => [
            [
                'product_id' => $product->id,
                'quantity' => 1,
            ],
        ],
        'shipping_address' => [
            'first_name' => 'Unknown',
            'last_name' => 'Currency',
            'address_line_1' => '123 Unknown St',
            'city' => 'Unknown City',
            'state' => 'UC',
            'postal_code' => '33333',
            'country_code' => 'US',
        ],
        'different_billing_address' => false,
        'currency_code' => 'XYZ',
    ];

    $unknownOrder = app(StoreOrderAction::class)->handle(StoreOrderInput::fromArray($unknownData), auth()->user());
    expect($unknownOrder->currency_code)->toBe('XYZ')
        ->and($unknownOrder->exchange_rate)->toBe('1.0000');
});

test('creates an order with shipping and payment gateways', function () {
    actingAsSuperAdmin();
    $product = Product::factory()->create(['price' => '50.0000', 'track_stock' => false]);
    $shippingCarrier = ShippingCarrier::factory()->create(['name' => ['en' => 'Express Shipping', 'fr' => 'Livraison Express']]);
    $region = Region::factory()->create();
    $shippingRate = ShippingRate::factory()->create([
        'shipping_carrier_id' => $shippingCarrier->id,
        'region_id' => $region->id,
        'rate' => '12.5000',
    ]);
    $paymentGateway = PaymentGateway::factory()->create(['name' => ['en' => 'Stripe', 'fr' => 'Stripe FR']]);

    $order = app(StoreOrderAction::class)->handle(StoreOrderInput::fromArray([
        'customer_email' => 'snapshot@example.com',
        'items' => [
            [
                'product_id' => $product->id,
                'quantity' => 1,
            ],
        ],
        'shipping_rate_id' => $shippingRate->id,
        'payment_gateway_id' => $paymentGateway->id,
    ]), auth()->user());

    expect($order->shipping_carrier_id)->toBe($shippingCarrier->id)
        ->and($order->getTranslations('shipping_carrier_name'))->toBe($shippingCarrier->getTranslations('name'))
        ->and($order->shipping_rate_id)->toBe($shippingRate->id)
        ->and($order->getTranslations('shipping_rate_name'))->toBe($shippingRate->getTranslations('name'))
        ->and($order->payment_gateway_id)->toBe($paymentGateway->id)
        ->and($order->getTranslations('payment_gateway_name'))->toBe($paymentGateway->getTranslations('name'));

    assertDatabaseHas('orders', [
        'id' => $order->id,
        'shipping_total' => '12.5000', // Calculated from shipping rate
    ]);
});

test('creates an order with coupon', function () {
    actingAsSuperAdmin();
    $product = Product::factory()->create(['price' => '100.0000', 'track_stock' => false]);
    $coupon = Coupon::factory()->valid()->create([
        'code' => 'SAVE10',
        'type' => CouponType::Flat,
        'value' => '10.0000',
        'is_active' => true,
        'used_count' => 0,
    ]);

    $data = [
        'customer_email' => 'alice@example.com',
        'items' => [
            [
                'product_id' => $product->id,
                'quantity' => 1,
            ],
        ],
        'coupon_code' => $coupon->code,
    ];

    $result = app(StoreOrderAction::class)->handle(StoreOrderInput::fromArray($data), auth()->user());

    expect($result->coupon_id)->toBe($coupon->id);
    expect($result->discount_total)->toBe('10.0000');

    $coupon->refresh();
    expect($coupon->used_count)->toBe(1);

    assertDatabaseHas('orders', [
        'id' => $result->id,
        'subtotal' => '100.0000',
        'discount_total' => '10.0000', // Server calculated from coupon
    ]);
});

test('throws validation exception when invalid coupon provided', function () {
    actingAsSuperAdmin();
    $product = Product::factory()->create(['price' => '100.0000', 'track_stock' => false]);

    $data = [
        'customer_email' => 'test@example.com',
        'items' => [
            [
                'product_id' => $product->id,
                'quantity' => 1,
            ],
        ],
        'coupon_code' => 'INVALID',
    ];

    expect(fn () => app(StoreOrderAction::class)->handle(StoreOrderInput::fromArray($data), auth()->user()))
        ->toThrow(Illuminate\Validation\ValidationException::class);
});

test('handles all address and optional field scenarios', function () {
    actingAsSuperAdmin();
    $product = Product::factory()->create(['price' => '100.0000', 'track_stock' => false]);

    $data = [
        'customer_email' => 'comprehensive@example.com',
        'items' => [
            [
                'product_id' => $product->id,
                'quantity' => 1,
            ],
        ],
        'shipping_address' => [
            'first_name' => 'Comprehensive',
            'last_name' => 'Test',
            'address_line_1' => '456 Shipping St',
            'city' => 'Shipping City',
            'state' => 'SC',
            'postal_code' => '22222',
            'country_code' => 'US',
        ],
        'different_billing_address' => true,
        'billing_address' => [
            'first_name' => 'Comprehensive',
            'last_name' => 'Test',
            'address_line_1' => '123 Main St',
            'address_line_2' => 'Apt 4B',
            'city' => 'Test City',
            'state' => 'TC',
            'postal_code' => '11111',
            'country_code' => 'US',
            'phone' => '555-0888',
        ],
        'notes' => 'Please handle with care - fragile items',
    ];

    $result = app(StoreOrderAction::class)->handle(StoreOrderInput::fromArray($data), auth()->user());

    expect($result)->toBeInstanceOf(Order::class)
        ->and($result->notes)->toBe('Please handle with care - fragile items');

    expect($result->addresses)->toHaveCount(2);

    $billingAddress = $result->addresses->where('type', OrderAddressType::Billing)->first();
    expect($billingAddress)->toBeInstanceOf(OrderAddress::class)
        ->and($billingAddress->address_line_1)->toBe('123 Main St')
        ->and($billingAddress->address_line_2)->toBe('Apt 4B')
        ->and($billingAddress->phone)->toBe('555-0888')
        ->and($billingAddress->city)->toBe('Test City');

    $shippingAddress = $result->addresses->where('type', OrderAddressType::Shipping)->first();
    expect($shippingAddress)->toBeInstanceOf(OrderAddress::class)
        ->and($shippingAddress->address_line_1)->toBe('456 Shipping St')
        ->and($shippingAddress->city)->toBe('Shipping City')
        ->and($shippingAddress->address_line_2)->toBeNull()
        ->and($shippingAddress->phone)->toBeNull();
});

test('creates an order item with variant options', function () {
    actingAsSuperAdmin();
    $product = Product::factory()->create(['price' => '50.0000', 'track_stock' => false]);
    $variant = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'price' => '75.0000',
        'title' => 'Large / Red',
        'track_stock' => false,
    ]);

    $data = [
        'customer_email' => 'variant@example.com',
        'items' => [
            [
                'product_id' => $product->id,
                'product_variant_id' => $variant->id,
                'quantity' => 1,
                'variant_options' => ['size' => 'Large', 'color' => 'Red'],
            ],
        ],
        'shipping_address' => [
            'first_name' => 'Variant',
            'last_name' => 'Options',
            'address_line_1' => '123 Variant St',
            'city' => 'Variant City',
            'state' => 'VC',
            'postal_code' => '55667',
            'country_code' => 'US',
        ],
        'different_billing_address' => false,
    ];

    $result = app(StoreOrderAction::class)->handle(StoreOrderInput::fromArray($data), auth()->user());

    expect($result->items)->toHaveCount(1);
    $orderItem = $result->items->first();
    expect($orderItem->variant_options)->toBe(['size' => 'Large', 'color' => 'Red'])
        ->and($orderItem->variant_title)->toBe('Large / Red');
});

test('decrements stock when product tracks stock', function () {
    actingAsSuperAdmin();
    $product = Product::factory()->create([
        'price' => '100.0000',
        'track_stock' => true,
        'stock' => 10,
    ]);

    $data = [
        'customer_email' => 'stock@example.com',
        'items' => [
            [
                'product_id' => $product->id,
                'quantity' => 2,
            ],
        ],
        'shipping_address' => [
            'first_name' => 'Stock',
            'last_name' => 'Test',
            'address_line_1' => '123 Stock St',
            'city' => 'Stock City',
            'state' => 'SC',
            'postal_code' => '77889',
            'country_code' => 'US',
        ],
        'different_billing_address' => false,
    ];

    $result = app(StoreOrderAction::class)->handle(StoreOrderInput::fromArray($data), auth()->user());

    $product->refresh();
    expect($product->stock)->toBe(8);

    $stockMovement = StockMovement::query()
        ->where('product_id', $product->id)
        ->where('quantity', -2)
        ->where('reason', StockMovementReason::Sale)
        ->where('reference_type', Order::class)
        ->where('reference_id', $result->id)
        ->first();

    expect($stockMovement)->not->toBeNull();
});

test('sets carrier and gateway names to empty when no carriers and gateways specified', function () {
    actingAsSuperAdmin();
    $product = Product::factory()->create(['price' => '100.0000', 'track_stock' => false]);

    $data = [
        'customer_email' => 'nogateway@example.com',
        'items' => [
            [
                'product_id' => $product->id,
                'quantity' => 1,
            ],
        ],
        'shipping_address' => [
            'first_name' => 'No',
            'last_name' => 'Gateway',
            'address_line_1' => '123 Gateway St',
            'city' => 'Gateway City',
            'state' => 'PC',
            'postal_code' => '22222',
            'country_code' => 'US',
        ],
        'different_billing_address' => false,
        // No shipping_rate_id or payment_gateway_id
    ];

    $result = app(StoreOrderAction::class)->handle(StoreOrderInput::fromArray($data), auth()->user());

    expect($result->shipping_carrier_name)->toBe('')
        ->and($result->payment_gateway_name)->toBe('')
        ->and($result->shipping_rate_name)->toBe('')
        ->and($result->shipping_rate_id)->toBeNull()
        ->and($result->shipping_carrier_id)->toBeNull()
        ->and($result->payment_gateway_id)->toBeNull();
});

test('calculates tax correctly when different_billing_address is false', function () {
    actingAsSuperAdmin();

    Setting::setValue('prices_include_tax', false);
    Setting::setValue('tax_based_on', 'shipping');

    $region = Region::factory()->create([
        'countries' => ['US'],
        'states' => ['CA'],
        'postal_codes' => ['90210'],
        'is_active' => true,
    ]);

    $taxCategory = TaxCategory::Standard;
    TaxRate::factory()->withoutConditions()->create([
        'region_id' => $region->id,
        'tax_category' => $taxCategory,
        'name' => 'CA Sales Tax',
        'rate' => '8.2500',
        'is_compound' => false,
        'is_active' => true,
    ]);

    $product = Product::factory()->create([
        'tax_category' => $taxCategory,
        'price' => '100.0000',
        'is_tax_exempt' => false,
        'track_stock' => false,
    ]);

    $data = [
        'customer_email' => 'tax@example.com',
        'items' => [
            [
                'product_id' => $product->id,
                'quantity' => 1,
            ],
        ],
        'shipping_address' => [
            'first_name' => 'Tax',
            'last_name' => 'Test',
            'address_line_1' => '123 Beverly Hills',
            'city' => 'Beverly Hills',
            'state' => 'CA',
            'postal_code' => '90210',
            'country_code' => 'US',
        ],
        'different_billing_address' => false,
    ];

    $result = app(StoreOrderAction::class)->handle(StoreOrderInput::fromArray($data), auth()->user());

    expect($result->addresses)->toHaveCount(2);

    $billingAddress = $result->addresses->where('type', OrderAddressType::Billing)->first();
    $shippingAddress = $result->addresses->where('type', OrderAddressType::Shipping)->first();

    expect($billingAddress)->not->toBeNull()
        ->and($shippingAddress)->not->toBeNull()
        ->and($shippingAddress->state)->toBe('CA')
        ->and($shippingAddress->postal_code)->toBe('90210')
        ->and($shippingAddress->country_code)->toBe('US');

    expect($result->tax_total)->toBe('8.2500');

    expect($result->taxDetails)->toHaveCount(1);
    $taxDetail = $result->taxDetails->first();
    expect($taxDetail->tax_rate)->toBe('8.25')
        ->and($taxDetail->taxable_amount)->toBe('100.0000')
        ->and($taxDetail->tax_amount)->toBe('8.2500');
});

test('scales order and item amounts to currency decimal places', function () {
    actingAsSuperAdmin();

    Currency::factory()->create([
        'code' => 'JPY',
        'symbol' => '¥',
        'exchange_rate' => '1.0000',
        'decimal_places' => 0,
        'is_active' => true,
    ]);

    $shippingCarrier = ShippingCarrier::factory()->create();
    $shippingRate = ShippingRate::factory()->create([
        'shipping_carrier_id' => $shippingCarrier->id,
        'rate' => '12.5000',
    ]);

    $product = Product::factory()->create(['price' => '33.3300', 'track_stock' => false]);

    $data = [
        'customer_email' => 'jpy@example.com',
        'items' => [
            [
                'product_id' => $product->id,
                'quantity' => 2,
            ],
        ],
        'shipping_rate_id' => $shippingRate->id,
        'currency_code' => 'JPY',
    ];

    $result = app(StoreOrderAction::class)->handle(StoreOrderInput::fromArray($data), auth()->user());

    expect($result->currency_code)->toBe('JPY')
        ->and($result->subtotal)->toBe('67.0000') // 66.66 rounded to 0 dp
        ->and($result->shipping_total)->toBe('13.0000'); // 12.5 rounded to 0 decimal places

    $item = $result->items->first();
    expect($item->unit_price)->toBe('33.0000') // 33.33 rounded to 0 dp
        ->and($item->total_price)->toBe('67.0000'); // 66.66 rounded to 0 dp
});

test('does not send customer notification when setting is disabled', function () {
    Notification::fake();
    actingAsSuperAdmin();

    Setting::setValue('notification_customer_order_confirmed', false);
    Setting::setValue('notification_admin_new_order', false);

    $product = Product::factory()->create(['price' => '50.0000', 'track_stock' => false]);

    app(StoreOrderAction::class)->handle(StoreOrderInput::fromArray([
        'customer_email' => 'test@example.com',
        'items' => [['product_id' => $product->id, 'quantity' => 1]],
    ]), auth()->user());

    Notification::assertNothingSent();
});

test('sends customer notification when setting is enabled', function () {
    Notification::fake();
    actingAsSuperAdmin();

    Setting::setValue('notification_customer_order_confirmed', true);
    Setting::setValue('notification_admin_new_order', false);

    $product = Product::factory()->create(['price' => '50.0000', 'track_stock' => false]);

    app(StoreOrderAction::class)->handle(StoreOrderInput::fromArray([
        'customer_email' => 'customer@example.com',
        'items' => [['product_id' => $product->id, 'quantity' => 1]],
    ]), auth()->user());

    Notification::assertSentOnDemand(CustomerOrderConfirmedNotification::class);
    Notification::assertNotSentTo(auth()->user(), CustomerOrderConfirmedNotification::class);
});

test('does not send admin notification when setting is disabled', function () {
    Notification::fake();
    actingAsSuperAdmin();

    Setting::setValue('notification_admin_new_order', false);
    Setting::setValue('notification_customer_order_confirmed', false);

    $product = Product::factory()->create(['price' => '50.0000', 'track_stock' => false]);

    app(StoreOrderAction::class)->handle(StoreOrderInput::fromArray([
        'customer_email' => 'test@example.com',
        'items' => [['product_id' => $product->id, 'quantity' => 1]],
    ]), auth()->user());

    Notification::assertNothingSent();
});

test('sends admin notification when setting is enabled', function () {
    Notification::fake();
    actingAsSuperAdmin();

    Setting::setValue('notification_admin_new_order', true);
    Setting::setValue('store_email', 'admin@store.com');
    Setting::setValue('notification_customer_order_confirmed', false);

    $product = Product::factory()->create(['price' => '50.0000', 'track_stock' => false]);

    app(StoreOrderAction::class)->handle(StoreOrderInput::fromArray([
        'customer_email' => 'test@example.com',
        'items' => [['product_id' => $product->id, 'quantity' => 1]],
    ]), auth()->user());

    Notification::assertSentOnDemand(AdminNewOrderNotification::class);
});
