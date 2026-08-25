<?php

declare(strict_types=1);

use App\Actions\StoreOrderTaxAction;
use App\Models\Currency;
use App\Models\Order;
use App\Models\OrderAddress;
use App\Models\OrderItem;
use App\Models\OrderTaxDetail;
use App\Models\Product;
use App\Models\Region;
use App\Models\Setting;
use App\Models\TaxRate;

covers(StoreOrderTaxAction::class);

uses()->group('actions', 'tax');

test('calculates tax and updates order tax_total', function () {
    Setting::setValue('prices_include_tax', false);
    Setting::setValue('tax_based_on', 'shipping');
    Setting::setValue('shipping_is_taxable', false);

    $region = Region::factory()->create([
        'countries' => ['US'],
        'states' => ['NY'],
        'postal_codes' => [],
        'is_active' => true,
    ]);

    TaxRate::factory()->withoutConditions()->create([
        'region_id' => $region->id,
        'tax_category' => null,
        'rate' => '10.0000',
        'is_active' => true,
        'is_compound' => false,
    ]);

    $product = Product::factory()->create(['is_tax_exempt' => false]);

    $order = Order::factory()->create([
        'subtotal' => '100.0000',
        'tax_total' => '0.0000',
        'shipping_total' => '0.0000',
        'discount_total' => '0.0000',
        'total' => '100.0000',
    ]);

    OrderItem::factory()->for($order)->create([
        'product_id' => $product->id,
        'total_price' => '100.0000',
        'tax_amount' => '0.0000',
    ]);

    OrderAddress::factory()->shipping()->forOrder($order)->create([
        'country_code' => 'US',
        'state' => 'NY',
        'postal_code' => '10001',
    ]);

    $action = app(StoreOrderTaxAction::class);
    $action->handle($order);

    $order->refresh();

    expect($order->tax_total)->toBe('10.0000');
});

test('stores tax details in the database', function () {
    Setting::setValue('prices_include_tax', false);
    Setting::setValue('tax_based_on', 'shipping');
    Setting::setValue('shipping_is_taxable', false);

    $region = Region::factory()->create([
        'countries' => ['US'],
        'states' => ['CA'],
        'postal_codes' => [],
        'is_active' => true,
    ]);

    TaxRate::factory()->withoutConditions()->create([
        'region_id' => $region->id,
        'tax_category' => null,
        'rate' => '8.0000',
        'is_active' => true,
        'is_compound' => false,
    ]);

    $product = Product::factory()->create(['is_tax_exempt' => false]);

    $order = Order::factory()->create([
        'subtotal' => '50.0000',
        'tax_total' => '0.0000',
        'shipping_total' => '0.0000',
        'discount_total' => '0.0000',
        'total' => '50.0000',
    ]);

    OrderItem::factory()->for($order)->create([
        'product_id' => $product->id,
        'total_price' => '50.0000',
        'tax_amount' => '0.0000',
    ]);

    OrderAddress::factory()->shipping()->forOrder($order)->create([
        'country_code' => 'US',
        'state' => 'CA',
        'postal_code' => '90210',
    ]);

    $action = app(StoreOrderTaxAction::class);
    $action->handle($order);

    $taxDetails = OrderTaxDetail::query()->where('order_id', $order->id)->get();

    expect($taxDetails)->toHaveCount(1)
        ->and($taxDetails->first()->tax_amount)->toBe('4.0000')
        ->and($taxDetails->first()->order_id)->toBe($order->id);
});

test('updates order item tax amounts', function () {
    Setting::setValue('prices_include_tax', false);
    Setting::setValue('tax_based_on', 'shipping');
    Setting::setValue('shipping_is_taxable', false);

    $region = Region::factory()->create([
        'countries' => ['US'],
        'states' => ['TX'],
        'postal_codes' => [],
        'is_active' => true,
    ]);

    TaxRate::factory()->withoutConditions()->create([
        'region_id' => $region->id,
        'tax_category' => null,
        'rate' => '10.0000',
        'is_active' => true,
        'is_compound' => false,
    ]);

    $product = Product::factory()->create(['is_tax_exempt' => false]);

    $order = Order::factory()->create([
        'subtotal' => '100.0000',
        'tax_total' => '0.0000',
        'shipping_total' => '0.0000',
        'discount_total' => '0.0000',
        'total' => '100.0000',
    ]);

    $item = OrderItem::factory()->for($order)->create([
        'product_id' => $product->id,
        'total_price' => '100.0000',
        'tax_amount' => '0.0000',
    ]);

    OrderAddress::factory()->shipping()->forOrder($order)->create([
        'country_code' => 'US',
        'state' => 'TX',
        'postal_code' => '75201',
    ]);

    $action = app(StoreOrderTaxAction::class);
    $action->handle($order);

    $item->refresh();

    expect($item->tax_amount)->toBe('10.0000');
});

test('sets zero tax when prices include tax', function () {
    Setting::setValue('prices_include_tax', true);

    $product = Product::factory()->create(['is_tax_exempt' => false]);

    $order = Order::factory()->create([
        'subtotal' => '100.0000',
        'tax_total' => '5.0000',
        'shipping_total' => '0.0000',
        'discount_total' => '0.0000',
        'total' => '105.0000',
    ]);

    OrderItem::factory()->for($order)->create([
        'product_id' => $product->id,
        'total_price' => '100.0000',
        'tax_amount' => '5.0000',
    ]);

    $action = app(StoreOrderTaxAction::class);
    $action->handle($order);

    $order->refresh();

    expect($order->tax_total)->toBe('0.0000');

    expect(OrderTaxDetail::query()->where('order_id', $order->id)->count())->toBe(0);
});

test('uses order prices_include_tax flag instead of global setting when recalculating', function () {
    Setting::setValue('prices_include_tax', false);
    Setting::setValue('tax_based_on', 'shipping');
    Setting::setValue('shipping_is_taxable', false);

    $region = Region::factory()->create([
        'countries' => ['US'],
        'states' => ['NY'],
        'postal_codes' => [],
        'is_active' => true,
    ]);

    TaxRate::factory()->withoutConditions()->create([
        'region_id' => $region->id,
        'tax_category' => null,
        'rate' => '10.0000',
        'is_active' => true,
        'is_compound' => false,
    ]);

    $product = Product::factory()->create(['is_tax_exempt' => false]);

    // Order created when prices_include_tax was false
    $order = Order::factory()->create([
        'prices_include_tax' => false,
        'subtotal' => '100.0000',
        'tax_total' => '0.0000',
        'shipping_total' => '0.0000',
        'discount_total' => '0.0000',
        'total' => '100.0000',
    ]);

    OrderItem::factory()->for($order)->create([
        'product_id' => $product->id,
        'total_price' => '100.0000',
        'tax_amount' => '0.0000',
    ]);

    OrderAddress::factory()->shipping()->forOrder($order)->create([
        'country_code' => 'US',
        'state' => 'NY',
        'postal_code' => '10001',
    ]);

    // Global setting changed to true AFTER order creation
    Setting::setValue('prices_include_tax', true);

    $action = app(StoreOrderTaxAction::class);
    $action->handle($order);

    $order->refresh();

    // Tax should still be calculated because the order's prices_include_tax is false
    expect($order->tax_total)->toBe('10.0000');
});

test('does not add tax when order prices_include_tax is true even if global setting is false', function () {
    Setting::setValue('prices_include_tax', true);

    $product = Product::factory()->create(['is_tax_exempt' => false]);

    // Order created when prices_include_tax was true
    $order = Order::factory()->create([
        'prices_include_tax' => true,
        'subtotal' => '100.0000',
        'tax_total' => '0.0000',
        'shipping_total' => '0.0000',
        'discount_total' => '0.0000',
        'total' => '100.0000',
    ]);

    OrderItem::factory()->for($order)->create([
        'product_id' => $product->id,
        'total_price' => '100.0000',
        'tax_amount' => '0.0000',
    ]);

    // Global setting changed to false AFTER order creation
    Setting::setValue('prices_include_tax', false);

    $action = app(StoreOrderTaxAction::class);
    $action->handle($order);

    $order->refresh();

    // Tax should remain zero because the order's prices_include_tax is true
    expect($order->tax_total)->toBe('0.0000');

    expect(OrderTaxDetail::query()->where('order_id', $order->id)->count())->toBe(0);
});

test('uses order shipping_is_taxable flag instead of global setting when recalculating', function () {
    Setting::setValue('prices_include_tax', false);
    Setting::setValue('tax_based_on', 'shipping');
    Setting::setValue('shipping_is_taxable', false);

    $region = Region::factory()->create([
        'countries' => ['US'],
        'states' => ['NY'],
        'postal_codes' => [],
        'is_active' => true,
    ]);

    TaxRate::factory()->withoutConditions()->create([
        'region_id' => $region->id,
        'tax_category' => null,
        'rate' => '10.0000',
        'is_active' => true,
        'is_compound' => false,
    ]);

    $product = Product::factory()->create(['is_tax_exempt' => false]);

    // Order created when shipping_is_taxable was false
    $order = Order::factory()->create([
        'shipping_is_taxable' => false,
        'subtotal' => '100.0000',
        'tax_total' => '0.0000',
        'shipping_total' => '20.0000',
        'discount_total' => '0.0000',
        'total' => '120.0000',
    ]);

    OrderItem::factory()->for($order)->create([
        'product_id' => $product->id,
        'total_price' => '100.0000',
        'tax_amount' => '0.0000',
    ]);

    OrderAddress::factory()->shipping()->forOrder($order)->create([
        'country_code' => 'US',
        'state' => 'NY',
        'postal_code' => '10001',
    ]);

    // Global setting changed to true AFTER order creation
    Setting::setValue('shipping_is_taxable', true);

    $action = app(StoreOrderTaxAction::class);
    $action->handle($order);

    $order->refresh();

    // Tax should only be on product (10.0000), NOT on shipping, because order's flag is false
    expect($order->tax_total)->toBe('10.0000');
});

test('taxes shipping when order shipping_is_taxable is true even if global setting is false', function () {
    Setting::setValue('prices_include_tax', false);
    Setting::setValue('tax_based_on', 'shipping');
    Setting::setValue('shipping_is_taxable', true);

    $region = Region::factory()->create([
        'countries' => ['US'],
        'states' => ['NY'],
        'postal_codes' => [],
        'is_active' => true,
    ]);

    TaxRate::factory()->withoutConditions()->create([
        'region_id' => $region->id,
        'tax_category' => null,
        'rate' => '10.0000',
        'is_active' => true,
        'is_compound' => false,
    ]);

    $product = Product::factory()->create(['is_tax_exempt' => false]);

    // Order created when shipping_is_taxable was true
    $order = Order::factory()->create([
        'shipping_is_taxable' => true,
        'subtotal' => '100.0000',
        'tax_total' => '0.0000',
        'shipping_total' => '20.0000',
        'discount_total' => '0.0000',
        'total' => '120.0000',
    ]);

    OrderItem::factory()->for($order)->create([
        'product_id' => $product->id,
        'total_price' => '100.0000',
        'tax_amount' => '0.0000',
    ]);

    OrderAddress::factory()->shipping()->forOrder($order)->create([
        'country_code' => 'US',
        'state' => 'NY',
        'postal_code' => '10001',
    ]);

    // Global setting changed to false AFTER order creation
    Setting::setValue('shipping_is_taxable', false);

    $action = app(StoreOrderTaxAction::class);
    $action->handle($order);

    $order->refresh();

    // Tax should include shipping: (100 + 20) * 10% = 12.0000
    expect($order->tax_total)->toBe('12.0000');
});

test('uses order tax_based_on instead of global setting when recalculating', function () {
    Setting::setValue('prices_include_tax', false);
    Setting::setValue('tax_based_on', 'billing');
    Setting::setValue('shipping_is_taxable', false);

    $region = Region::factory()->create([
        'countries' => ['US'],
        'states' => ['NY'],
        'postal_codes' => [],
        'is_active' => true,
    ]);

    TaxRate::factory()->withoutConditions()->create([
        'region_id' => $region->id,
        'tax_category' => null,
        'rate' => '10.0000',
        'is_active' => true,
        'is_compound' => false,
    ]);

    $product = Product::factory()->create(['is_tax_exempt' => false]);

    // Order was created with tax_based_on = shipping, but global changed to billing
    $order = Order::factory()->create([
        'tax_based_on' => 'shipping',
        'subtotal' => '100.0000',
        'tax_total' => '0.0000',
        'shipping_total' => '0.0000',
        'discount_total' => '0.0000',
        'total' => '100.0000',
    ]);

    OrderItem::factory()->for($order)->create([
        'product_id' => $product->id,
        'total_price' => '100.0000',
        'tax_amount' => '0.0000',
    ]);

    // Shipping address matches region, billing does not
    OrderAddress::factory()->shipping()->forOrder($order)->create([
        'country_code' => 'US',
        'state' => 'NY',
        'postal_code' => '10001',
    ]);

    OrderAddress::factory()->billing()->forOrder($order)->create([
        'country_code' => 'CA',
        'state' => 'ON',
        'postal_code' => 'M5V 2T6',
    ]);

    $action = app(StoreOrderTaxAction::class);
    $action->handle($order);

    $order->refresh();

    // Should use shipping address (order's tax_based_on), not billing (global setting)
    expect($order->tax_total)->toBe('10.0000');
});

test('uses order store address instead of global setting when recalculating with store-based tax', function () {
    Setting::setValue('prices_include_tax', false);
    Setting::setValue('shipping_is_taxable', false);
    Setting::setValue('store_country_code', 'CA');
    Setting::setValue('store_state', 'ON');
    Setting::setValue('store_postal_code', 'M5V 2T6');

    $region = Region::factory()->create([
        'countries' => ['US'],
        'states' => ['TX'],
        'postal_codes' => [],
        'is_active' => true,
    ]);

    TaxRate::factory()->withoutConditions()->create([
        'region_id' => $region->id,
        'tax_category' => null,
        'rate' => '8.2500',
        'is_active' => true,
        'is_compound' => false,
    ]);

    $product = Product::factory()->create(['is_tax_exempt' => false]);

    // Order snapshotted store address as US/TX, but global changed to CA/ON
    $order = Order::factory()->create([
        'tax_based_on' => 'store',
        'tax_store_country_code' => 'US',
        'tax_store_state' => 'TX',
        'tax_store_postal_code' => '73301',
        'subtotal' => '100.0000',
        'tax_total' => '0.0000',
        'shipping_total' => '0.0000',
        'discount_total' => '0.0000',
        'total' => '100.0000',
    ]);

    OrderItem::factory()->for($order)->create([
        'product_id' => $product->id,
        'total_price' => '100.0000',
        'tax_amount' => '0.0000',
    ]);

    $action = app(StoreOrderTaxAction::class);
    $action->handle($order);

    $order->refresh();

    // Should use US/TX store address (order's snapshot), matching the region with 8.25% rate
    expect($order->tax_total)->toBe('8.2500');
});

test('scales tax total to currency decimal places for zero-decimal currency', function () {
    Setting::setValue('prices_include_tax', false);
    Setting::setValue('tax_based_on', 'shipping');
    Setting::setValue('shipping_is_taxable', false);

    Currency::factory()->create([
        'code' => 'JPY',
        'decimal_places' => 0,
        'exchange_rate' => '1.0000',
        'is_active' => true,
    ]);

    $region = Region::factory()->create([
        'countries' => ['JP'],
        'states' => ['Tokyo'],
        'postal_codes' => [],
        'is_active' => true,
    ]);

    TaxRate::factory()->withoutConditions()->create([
        'region_id' => $region->id,
        'tax_category' => null,
        'rate' => '7.0000',
        'is_active' => true,
        'is_compound' => false,
    ]);

    $product = Product::factory()->create(['is_tax_exempt' => false]);

    $order = Order::factory()->create([
        'currency_code' => 'JPY',
        'subtotal' => '33.0000',
        'tax_total' => '0.0000',
        'shipping_total' => '0.0000',
        'discount_total' => '0.0000',
        'total' => '33.0000',
    ]);

    OrderItem::factory()->for($order)->create([
        'product_id' => $product->id,
        'total_price' => '33.0000',
        'tax_amount' => '0.0000',
    ]);

    OrderAddress::factory()->shipping()->forOrder($order)->create([
        'country_code' => 'JP',
        'state' => 'Tokyo',
        'postal_code' => '100-0001',
    ]);

    $action = app(StoreOrderTaxAction::class);
    $action->handle($order);

    $order->refresh();

    // 7% of 33 = 2.31, scaled to 0 decimal places = 2.0000
    expect($order->tax_total)->toBe('2.0000');

    $taxDetail = OrderTaxDetail::query()->where('order_id', $order->id)->first();
    expect($taxDetail->tax_amount)->toBe('2.0000')
        ->and($taxDetail->taxable_amount)->toBe('33.0000');

    $item = $order->items()->first();
    expect($item->tax_amount)->toBe('2.0000');
});

test('tax total is always derived from sum of scaled detail amounts', function () {
    Setting::setValue('prices_include_tax', false);
    Setting::setValue('tax_based_on', 'shipping');
    Setting::setValue('shipping_is_taxable', false);

    $region = Region::factory()->create([
        'countries' => ['US'],
        'states' => ['NY'],
        'postal_codes' => [],
        'is_active' => true,
    ]);

    TaxRate::factory()->withoutConditions()->create([
        'region_id' => $region->id,
        'tax_category' => null,
        'rate' => '10.0000',
        'is_active' => true,
        'is_compound' => false,
    ]);

    $product = Product::factory()->create(['is_tax_exempt' => false]);

    $order = Order::factory()->create([
        'subtotal' => '100.0000',
        'tax_total' => '0.0000',
        'shipping_total' => '0.0000',
        'discount_total' => '0.0000',
        'total' => '100.0000',
    ]);

    OrderItem::factory()->for($order)->create([
        'product_id' => $product->id,
        'total_price' => '100.0000',
        'tax_amount' => '0.0000',
    ]);

    OrderAddress::factory()->shipping()->forOrder($order)->create([
        'country_code' => 'US',
        'state' => 'NY',
        'postal_code' => '10001',
    ]);

    $action = app(StoreOrderTaxAction::class);
    $action->handle($order);

    $order->refresh();

    expect($order->tax_total)->toBe('10.0000');
});

test('tax total equals sum of scaled tax details avoiding penny rounding mismatch', function () {
    Setting::setValue('prices_include_tax', false);
    Setting::setValue('tax_based_on', 'shipping');
    Setting::setValue('shipping_is_taxable', false);

    Currency::factory()->create([
        'code' => 'JPY',
        'decimal_places' => 0,
        'exchange_rate' => '1.0000',
        'is_active' => true,
    ]);

    $region = Region::factory()->create([
        'countries' => ['JP'],
        'states' => ['Tokyo'],
        'postal_codes' => [],
        'is_active' => true,
    ]);

    TaxRate::factory()->withoutConditions()->create([
        'region_id' => $region->id,
        'tax_category' => null,
        'rate' => '3.3300',
        'is_active' => true,
        'is_compound' => false,
        'priority' => 1,
    ]);

    TaxRate::factory()->withoutConditions()->create([
        'region_id' => $region->id,
        'tax_category' => null,
        'rate' => '3.3400',
        'is_active' => true,
        'is_compound' => false,
        'priority' => 2,
    ]);

    $product = Product::factory()->create(['is_tax_exempt' => false]);

    $order = Order::factory()->create([
        'currency_code' => 'JPY',
        'subtotal' => '100.0000',
        'tax_total' => '0.0000',
        'shipping_total' => '0.0000',
        'discount_total' => '0.0000',
        'total' => '100.0000',
    ]);

    OrderItem::factory()->for($order)->create([
        'product_id' => $product->id,
        'total_price' => '100.0000',
        'tax_amount' => '0.0000',
    ]);

    OrderAddress::factory()->shipping()->forOrder($order)->create([
        'country_code' => 'JP',
        'state' => 'Tokyo',
        'postal_code' => '100-0001',
    ]);

    $action = app(StoreOrderTaxAction::class);
    $action->handle($order);

    $order->refresh();

    $taxDetails = OrderTaxDetail::query()->where('order_id', $order->id)->get();
    $detailSum = $taxDetails->sum(fn ($d) => (float) $d->tax_amount);

    // Each detail: 3.33 and 3.34 scaled to 0dp = 3.0000 each, sum = 6.0000
    // Without the fix, independently scaling 6.67 to 0dp would give 7.0000
    expect($taxDetails)->toHaveCount(2)
        ->and($order->tax_total)->toBe('6.0000')
        ->and((string) number_format($detailSum, 4, '.', ''))->toBe($order->tax_total);
});

test('replaces existing tax details instead of duplicating them', function () {
    Setting::setValue('prices_include_tax', false);
    Setting::setValue('tax_based_on', 'shipping');
    Setting::setValue('shipping_is_taxable', false);

    $region = Region::factory()->create([
        'countries' => ['US'],
        'states' => ['NY'],
        'postal_codes' => [],
        'is_active' => true,
    ]);

    TaxRate::factory()->withoutConditions()->create([
        'region_id' => $region->id,
        'tax_category' => null,
        'rate' => '10.0000',
        'is_active' => true,
        'is_compound' => false,
    ]);

    $product = Product::factory()->create(['is_tax_exempt' => false]);

    $order = Order::factory()->create([
        'subtotal' => '100.0000',
        'tax_total' => '0.0000',
        'shipping_total' => '0.0000',
        'discount_total' => '0.0000',
        'total' => '100.0000',
    ]);

    OrderItem::factory()->for($order)->create([
        'product_id' => $product->id,
        'total_price' => '100.0000',
        'tax_amount' => '0.0000',
    ]);

    OrderAddress::factory()->shipping()->forOrder($order)->create([
        'country_code' => 'US',
        'state' => 'NY',
        'postal_code' => '10001',
    ]);

    $action = app(StoreOrderTaxAction::class);
    $action->handle($order);
    $action->handle($order);

    $taxDetails = OrderTaxDetail::query()->where('order_id', $order->id)->get();

    expect($taxDetails)->toHaveCount(1)
        ->and($taxDetails->first()->tax_amount)->toBe('10.0000');
});
