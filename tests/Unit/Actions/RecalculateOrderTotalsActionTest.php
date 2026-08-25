<?php

declare(strict_types=1);

use App\Actions\RecalculateOrderTotalsAction;
use App\Models\Order;
use App\Models\OrderAddress;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Region;
use App\Models\Setting;
use App\Models\TaxRate;

covers(RecalculateOrderTotalsAction::class);

uses()->group('actions', 'orders');

test('recalculates tax, total, and balance due', function () {
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
        'shipping_total' => '5.0000',
        'discount_total' => '0.0000',
        'total' => '0.0000',
        'balance_due_total' => '0.0000',
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

    app(RecalculateOrderTotalsAction::class)->handle($order);

    $order->refresh();

    expect($order->tax_total)->toBe('10.0000')
        ->and($order->total)->toBe('115.0000')
        ->and($order->balance_due_total)->toBe('115.0000');
});

test('sets balance due to zero when order is fully paid', function () {
    Setting::setValue('prices_include_tax', false);

    $product = Product::factory()->create(['is_tax_exempt' => false]);

    $order = Order::factory()->create([
        'subtotal' => '50.0000',
        'tax_total' => '0.0000',
        'shipping_total' => '0.0000',
        'discount_total' => '0.0000',
        'total' => '0.0000',
        'paid_total' => '50.0000',
        'balance_due_total' => '0.0000',
    ]);

    OrderItem::factory()->for($order)->create([
        'product_id' => $product->id,
        'total_price' => '50.0000',
        'tax_amount' => '0.0000',
    ]);

    app(RecalculateOrderTotalsAction::class)->handle($order);

    $order->refresh();

    expect($order->total)->toBe('50.0000')
        ->and($order->balance_due_total)->toBe('0.0000');
});
