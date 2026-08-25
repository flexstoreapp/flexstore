<?php

declare(strict_types=1);

use App\Enums\DisplayTaxTotals;
use App\Enums\SettingGroup;
use App\Enums\SettingType;
use App\Models\Media;
use App\Models\Order;
use App\Models\OrderAddress;
use App\Models\OrderItem;
use App\Models\PaymentGateway;
use App\Models\Product;
use App\Models\Setting;
use App\Models\ShippingCarrier;
use App\Models\ShippingRate;
use App\Utilities\MoneyFormatter;
use Illuminate\Support\Facades\Storage;

uses()->group('order');

function getStoreArray(array $overrides = []): array
{
    return array_merge([
        'name' => 'Test Store',
        'email' => null,
        'phone' => null,
        'street_address' => null,
        'city' => null,
        'state' => null,
        'postal_code' => null,
        'country' => null,
        'logo' => null,
    ], $overrides);
}

beforeEach(function () {
    Setting::query()->updateOrCreate(
        ['key' => 'base_currency'],
        [
            'value' => 'USD',
            'group' => SettingGroup::Currency,
            'type' => SettingType::Text,
        ]
    );

    Setting::query()->updateOrCreate(
        ['key' => 'display_tax_totals'],
        [
            'value' => DisplayTaxTotals::Single->value,
            'group' => SettingGroup::Tax,
            'type' => SettingType::Text,
        ]
    );

    Cache::flush();
});

test('invoice view shows store name and address', function () {
    $order = Order::factory()->create();
    OrderItem::factory()->create(['order_id' => $order->id]);

    $view = view('invoice', [
        'order' => $order,
        'store' => getStoreArray([
            'name' => 'My Awesome Shop',
            'email' => 'contact@shop.com',
            'phone' => '+1-555-0000',
            'street_address' => '123 Main St',
            'city' => 'New York',
            'state' => 'NY',
            'postal_code' => '10001',
            'country' => 'US',
        ]),
        'displayTaxTotals' => DisplayTaxTotals::Single,
    ])->render();

    expect($view)
        ->toContain('My Awesome Shop')
        ->toContain('123 Main St')
        ->toContain('New York')
        ->toContain('NY')
        ->toContain('10001')
        ->toContain('contact@shop.com')
        ->toContain('+1-555-0000');
});

test('invoice view embeds logo from disk when media is stored locally', function () {
    Storage::fake('public');
    Storage::disk('public')->put('images/logo.png', 'fake-logo-bytes');

    $media = Media::factory()->uploaded()->create([
        'disk' => 'public',
        'path' => 'images/logo.png',
    ]);

    $order = Order::factory()->create();
    OrderItem::factory()->create(['order_id' => $order->id]);

    $view = view('invoice', [
        'order' => $order,
        'store' => getStoreArray(['logo' => $media]),
        'displayTaxTotals' => DisplayTaxTotals::Single,
    ])->render();

    expect($view)
        ->toContain('base64,' . base64_encode('fake-logo-bytes'))
        ->not->toContain('class="store-name"');
});

test('invoice view falls back to store name when no logo is set', function () {
    $order = Order::factory()->create();
    OrderItem::factory()->create(['order_id' => $order->id]);

    $view = view('invoice', [
        'order' => $order,
        'store' => getStoreArray(['name' => 'No Logo Shop', 'logo' => null]),
        'displayTaxTotals' => DisplayTaxTotals::Single,
    ])->render();

    expect($view)
        ->toContain('class="store-name"')
        ->toContain('No Logo Shop')
        ->not->toContain('base64,');
});

test('invoice view falls back to store name when logo media is external-only', function () {
    $media = Media::factory()->create([
        'disk' => 'public',
        'path' => null,
        'external_url' => 'https://example.com/logo.png',
    ]);

    $order = Order::factory()->create();
    OrderItem::factory()->create(['order_id' => $order->id]);

    $view = view('invoice', [
        'order' => $order,
        'store' => getStoreArray(['name' => 'External Logo Shop', 'logo' => $media]),
        'displayTaxTotals' => DisplayTaxTotals::Single,
    ])->render();

    expect($view)
        ->toContain('class="store-name"')
        ->toContain('External Logo Shop')
        ->not->toContain('base64,');
});

test('invoice view shows invoice number and date', function () {
    $order = Order::factory()->create();
    OrderItem::factory()->create(['order_id' => $order->id]);

    $view = view('invoice', [
        'order' => $order,
        'store' => getStoreArray(),
        'displayTaxTotals' => DisplayTaxTotals::Single,
    ])->render();

    expect($view)
        ->toContain('Invoice')
        ->toContain("#{$order->id}")
        ->toContain($order->created_at->format('F j, Y'));
});

test('invoice view displays billing address details', function () {
    $order = Order::factory()->create([
        'customer_email' => 'jane@example.com',
    ]);
    OrderItem::factory()->create(['order_id' => $order->id]);

    OrderAddress::factory()->billing()->create([
        'order_id' => $order->id,
        'first_name' => 'Jane',
        'last_name' => 'Smith',
        'address_line_1' => '456 Elm Street',
        'address_line_2' => 'Suite 100',
        'city' => 'Chicago',
        'state' => 'IL',
        'postal_code' => '60601',
        'country_code' => 'US',
    ]);

    $order->load(['items', 'billingAddress']);

    $view = view('invoice', [
        'order' => $order,
        'store' => getStoreArray(),
        'displayTaxTotals' => DisplayTaxTotals::Single,
    ])->render();

    expect($view)
        ->toContain('Billing Address')
        ->toContain('Jane Smith')
        ->toContain('456 Elm Street')
        ->toContain('Suite 100')
        ->toContain('Chicago')
        ->toContain('Illinois')
        ->toContain('60601');
});

test('invoice view shows payment gateway', function () {
    $paymentGateway = PaymentGateway::factory()->create(['name' => 'Stripe']);
    $order = Order::factory()->create([
        'payment_gateway_id' => $paymentGateway->id,
        'payment_gateway_name' => $paymentGateway->getTranslations('name'),
    ]);
    OrderItem::factory()->create(['order_id' => $order->id]);

    $view = view('invoice', [
        'order' => $order,
        'store' => getStoreArray(),
        'displayTaxTotals' => DisplayTaxTotals::Single,
    ])->render();

    expect($view)
        ->toContain('Stripe')
        ->toContain('Payment:');
});

test('invoice view shows shipping rate name and carrier', function () {
    $shippingCarrier = ShippingCarrier::factory()->create(['name' => 'Fast Delivery']);
    $shippingRate = ShippingRate::factory()->create(['name' => 'Express Shipping (1-2 days)']);
    $order = Order::factory()->create([
        'shipping_carrier_id' => $shippingCarrier->id,
        'shipping_carrier_name' => $shippingCarrier->getTranslations('name'),
        'shipping_rate_id' => $shippingRate->id,
        'shipping_rate_name' => $shippingRate->getTranslations('name'),
    ]);
    OrderItem::factory()->create(['order_id' => $order->id]);

    $view = view('invoice', [
        'order' => $order,
        'store' => getStoreArray(),
        'displayTaxTotals' => DisplayTaxTotals::Single,
    ])->render();

    expect($view)
        ->toContain('Express Shipping (1-2 days)')
        ->toContain('Fast Delivery')
        ->toContain('Shipping:');
});

test('invoice view shows shipping carrier when rate name not available', function () {
    $shippingCarrier = ShippingCarrier::factory()->create(['name' => 'Ground Shipping']);
    $order = Order::factory()->create([
        'shipping_carrier_id' => $shippingCarrier->id,
        'shipping_carrier_name' => $shippingCarrier->getTranslations('name'),
        'shipping_rate_id' => null,
        'shipping_rate_name' => null,
    ]);
    OrderItem::factory()->create(['order_id' => $order->id]);

    $view = view('invoice', [
        'order' => $order,
        'store' => getStoreArray(),
        'displayTaxTotals' => DisplayTaxTotals::Single,
    ])->render();

    expect($view)
        ->toContain('Ground Shipping')
        ->toContain('Shipping:');
});

test('invoice view displays shipping address when different from billing', function () {
    $order = Order::factory()->create();
    OrderItem::factory()->create(['order_id' => $order->id]);

    OrderAddress::factory()->billing()->create([
        'order_id' => $order->id,
        'first_name' => 'John',
        'last_name' => 'Doe',
    ]);

    OrderAddress::factory()->shipping()->create([
        'order_id' => $order->id,
        'first_name' => 'Jane',
        'last_name' => 'Doe',
        'address_line_1' => '789 Oak Lane',
        'city' => 'Seattle',
        'state' => 'WA',
        'postal_code' => '98101',
        'country_code' => 'US',
    ]);

    $order->load(['items', 'billingAddress', 'shippingAddress']);

    $view = view('invoice', [
        'order' => $order,
        'store' => getStoreArray(),
        'displayTaxTotals' => DisplayTaxTotals::Single,
    ])->render();

    expect($view)
        ->toContain('Shipping Address')
        ->toContain('789 Oak Lane')
        ->toContain('Seattle')
        ->toContain('Washington');
});

test('invoice view hides billing address section when billing address is missing', function () {
    $order = Order::factory()->create();
    OrderItem::factory()->create(['order_id' => $order->id]);

    OrderAddress::factory()->shipping()->create([
        'order_id' => $order->id,
        'first_name' => 'John',
        'last_name' => 'Doe',
    ]);

    $order->load(['items', 'shippingAddress']);

    $view = view('invoice', [
        'order' => $order,
        'store' => getStoreArray(),
        'displayTaxTotals' => DisplayTaxTotals::Single,
    ])->render();

    expect($view)->not->toContain('Billing Address');
});

test('invoice view hides shipping address section when shipping address is missing', function () {
    $order = Order::factory()->create();
    OrderItem::factory()->create(['order_id' => $order->id]);

    OrderAddress::factory()->billing()->create([
        'order_id' => $order->id,
        'first_name' => 'John',
        'last_name' => 'Doe',
    ]);

    $order->load(['items', 'billingAddress']);

    $view = view('invoice', [
        'order' => $order,
        'store' => getStoreArray(),
        'displayTaxTotals' => DisplayTaxTotals::Single,
    ])->render();

    expect($view)->not->toContain('Shipping Address');
});

test('invoice view displays order items', function () {
    $product = Product::factory()->create();
    $order = Order::factory()->create(['currency_code' => 'USD']);

    OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'product_title' => castAsTranslatableArray('Awesome Product'),
        'quantity' => 3,
        'unit_price' => '29.9900',
        'total_price' => '89.9700',
    ]);

    $view = view('invoice', [
        'order' => $order,
        'store' => getStoreArray(),
        'displayTaxTotals' => DisplayTaxTotals::Single,
    ])->render();

    expect($view)
        ->toContain('Awesome Product')
        ->toContain('3')
        ->toContain(MoneyFormatter::format('29.9900', 'USD'))
        ->toContain(MoneyFormatter::format('89.9700', 'USD'));
});

test('invoice view displays item variant title when present', function () {
    $product = Product::factory()->create();
    $order = Order::factory()->create(['currency_code' => 'USD']);

    OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'product_title' => castAsTranslatableArray('T-Shirt'),
        'variant_title' => 'Red / Large',
        'quantity' => 1,
        'unit_price' => '25.0000',
        'total_price' => '25.0000',
    ]);

    $view = view('invoice', [
        'order' => $order,
        'store' => getStoreArray(),
        'displayTaxTotals' => DisplayTaxTotals::Single,
    ])->render();

    expect($view)
        ->toContain('T-Shirt')
        ->toContain('Red / Large');
});

test('invoice view displays product sku when present', function () {
    $product = Product::factory()->create();
    $order = Order::factory()->create(['currency_code' => 'USD']);

    OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'product_title' => castAsTranslatableArray('Widget'),
        'product_sku' => 'WDG-001-BLK',
        'quantity' => 1,
        'unit_price' => '15.0000',
        'total_price' => '15.0000',
    ]);

    $view = view('invoice', [
        'order' => $order,
        'store' => getStoreArray(),
        'displayTaxTotals' => DisplayTaxTotals::Single,
    ])->render();

    expect($view)
        ->toContain('WDG-001-BLK');
});

test('invoice view shows order notes', function () {
    $order = Order::factory()->create(['notes' => 'Please leave package at the back door']);
    OrderItem::factory()->create(['order_id' => $order->id]);

    $view = view('invoice', [
        'order' => $order,
        'store' => getStoreArray(),
        'displayTaxTotals' => DisplayTaxTotals::Single,
    ])->render();

    expect($view)
        ->toContain('Order notes')
        ->toContain('Please leave package at the back door');
});

test('invoice view does not show notes when notes empty', function () {
    $order = Order::factory()->create(['notes' => null]);
    OrderItem::factory()->create(['order_id' => $order->id]);

    $view = view('invoice', [
        'order' => $order,
        'store' => getStoreArray(),
        'displayTaxTotals' => DisplayTaxTotals::Single,
    ])->render();

    expect($view)->not->toContain('Order notes');
});

test('invoice view shows balance due when payment is incomplete', function () {
    $order = Order::factory()->unpaid()->create([
        'currency_code' => 'USD',
        'subtotal' => '100.0000',
        'shipping_total' => '0.0000',
        'tax_total' => '0.0000',
        'discount_total' => '0.0000',
        'total' => '100.0000',
        'refund_total' => '0.0000',
    ]);
    OrderItem::factory()->create(['order_id' => $order->id]);

    $view = view('invoice', [
        'order' => $order,
        'store' => getStoreArray(),
        'displayTaxTotals' => DisplayTaxTotals::Single,
    ])->render();

    expect($view)
        ->toContain('Balance due')
        ->toContain(MoneyFormatter::format('100.0000', 'USD'))
        ->not->toContain('Refunded')
        ->not->toContain('Net payment');
});

test('invoice view shows paid line when there are refunds', function () {
    $order = Order::factory()->paid()->create([
        'currency_code' => 'USD',
        'subtotal' => '200.0000',
        'shipping_total' => '15.0000',
        'tax_total' => '20.0000',
        'discount_total' => '0.0000',
        'total' => '235.0000',
        'refund_total' => '50.0000',
    ]);
    OrderItem::factory()->create(['order_id' => $order->id]);

    $view = view('invoice', [
        'order' => $order,
        'store' => getStoreArray(),
        'displayTaxTotals' => DisplayTaxTotals::Single,
    ])->render();

    expect($view)
        ->toContain('Paid')
        ->toContain('Refunded')
        ->toContain('Net payment')
        ->toContain(MoneyFormatter::format('235.0000', 'USD'))
        ->toContain('-' . MoneyFormatter::format('50.0000', 'USD'))
        ->toContain(MoneyFormatter::format('185.0000', 'USD'));
});

test('invoice view does not show paid stamp', function () {
    $order = Order::factory()->paid()->create();
    OrderItem::factory()->create(['order_id' => $order->id]);

    $view = view('invoice', [
        'order' => $order,
        'store' => getStoreArray(),
        'displayTaxTotals' => DisplayTaxTotals::Single,
    ])->render();

    expect($view)->not->toContain('PAID');
});

test('invoice view formats currency amounts correctly', function () {
    $order = Order::factory()->create([
        'currency_code' => 'USD',
        'subtotal' => '150.0000',
        'shipping_total' => '12.5000',
        'tax_total' => '13.2500',
        'discount_total' => '0.0000',
        'total' => '175.7500',
    ]);
    OrderItem::factory()->create(['order_id' => $order->id]);

    $view = view('invoice', [
        'order' => $order,
        'store' => getStoreArray(),
        'displayTaxTotals' => DisplayTaxTotals::Single,
    ])->render();

    expect($view)
        ->toContain(MoneyFormatter::format('150.0000', 'USD'))
        ->toContain(MoneyFormatter::format('12.5000', 'USD'))
        ->toContain(MoneyFormatter::format('13.2500', 'USD'))
        ->toContain(MoneyFormatter::format('175.7500', 'USD'));
});

test('invoice view formats currency amounts correctly with refunds', function () {
    $order = Order::factory()->paid()->create([
        'currency_code' => 'USD',
        'subtotal' => '200.0000',
        'shipping_total' => '15.0000',
        'tax_total' => '20.0000',
        'discount_total' => '0.0000',
        'total' => '235.0000',
        'refund_total' => '50.0000',
    ]);
    OrderItem::factory()->create(['order_id' => $order->id]);

    $view = view('invoice', [
        'order' => $order,
        'store' => getStoreArray(),
        'displayTaxTotals' => DisplayTaxTotals::Single,
    ])->render();

    expect($view)
        ->toContain(MoneyFormatter::format('200.0000', 'USD'))
        ->toContain(MoneyFormatter::format('15.0000', 'USD'))
        ->toContain(MoneyFormatter::format('20.0000', 'USD'))
        ->toContain(MoneyFormatter::format('235.0000', 'USD'))
        ->toContain('-' . MoneyFormatter::format('50.0000', 'USD'))
        ->toContain(MoneyFormatter::format('185.0000', 'USD'));
});

test('invoice view does not show net payment when refund_total is zero', function () {
    $order = Order::factory()->fulfilled()->create([
        'currency_code' => 'USD',
        'subtotal' => '200.0000',
        'shipping_total' => '15.0000',
        'tax_total' => '20.0000',
        'discount_total' => '0.0000',
        'total' => '235.0000',
        'refund_total' => '0.0000',
    ]);
    OrderItem::factory()->create(['order_id' => $order->id]);

    $view = view('invoice', [
        'order' => $order,
        'store' => getStoreArray(),
        'displayTaxTotals' => DisplayTaxTotals::Single,
    ])->render();

    expect($view)
        ->toContain(MoneyFormatter::format('200.0000', 'USD'))
        ->toContain(MoneyFormatter::format('15.0000', 'USD'))
        ->toContain(MoneyFormatter::format('20.0000', 'USD'))
        ->toContain(MoneyFormatter::format('235.0000', 'USD'))
        ->not->toContain('Refunded')
        ->not->toContain('Net payment');
});

test('invoice view displays discount with coupon code', function () {
    $order = Order::factory()->create([
        'currency_code' => 'USD',
        'coupon_code' => 'SUMMER20',
        'discount_total' => '20.0000',
        'subtotal' => '100.0000',
        'total' => '80.0000',
    ]);
    OrderItem::factory()->create(['order_id' => $order->id]);

    $view = view('invoice', [
        'order' => $order,
        'store' => getStoreArray(),
        'displayTaxTotals' => DisplayTaxTotals::Single,
    ])->render();

    expect($view)
        ->toContain('SUMMER20')
        ->toContain(MoneyFormatter::format('20.0000', 'USD'))
        ->toContain('Discount')
        ->toContain('Coupon:');
});
