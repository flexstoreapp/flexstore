<?php

declare(strict_types=1);

use App\Enums\OrderAddressType;
use App\Models\Order;
use App\Models\OrderAddress;
use App\Models\OrderItem;
use App\Models\OrderShipment;
use App\Utilities\EmailSchemaBuilder;

covers(EmailSchemaBuilder::class);

uses()->group('utilities', 'email');

test('forOrder produces a schema.org Order with merchant and totals', function () {
    $order = Order::factory()->create([
        'currency_code' => 'USD',
        'total' => '199.0000',
    ]);

    $schema = EmailSchemaBuilder::forOrder($order, 'https://schema.org/OrderProcessing');

    expect($schema['@context'])->toBe('https://schema.org')
        ->and($schema['@type'])->toBe('Order')
        ->and($schema['orderNumber'])->toBe((string) $order->id)
        ->and($schema['orderStatus'])->toBe('https://schema.org/OrderProcessing')
        ->and($schema['priceCurrency'])->toBe('USD')
        ->and($schema['price'])->toBe('199.0000')
        ->and($schema['merchant']['@type'])->toBe('Organization')
        ->and($schema['orderDate'])->toBeString();
});

test('forOrder includes ordered items with product details', function () {
    $order = Order::factory()->create();
    OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_title' => ['en' => 'Test Product'],
        'product_sku' => 'SKU-1',
        'quantity' => 2,
    ]);
    $order->load('items');

    $schema = EmailSchemaBuilder::forOrder($order, 'https://schema.org/OrderProcessing');

    expect($schema['acceptedOffer'])->toHaveCount(1)
        ->and($schema['acceptedOffer'][0]['@type'])->toBe('OrderItem')
        ->and($schema['acceptedOffer'][0]['orderQuantity'])->toBe(2)
        ->and($schema['acceptedOffer'][0]['orderedItem']['name'])->toBe('Test Product')
        ->and($schema['acceptedOffer'][0]['orderedItem']['sku'])->toBe('SKU-1');
});

test('forOrder uses provided view URL when set', function () {
    $order = Order::factory()->create();

    $schema = EmailSchemaBuilder::forOrder($order, 'https://schema.org/OrderProcessing', 'https://example.test/orders/42');

    expect($schema['url'])->toBe('https://example.test/orders/42');
});

test('forOrder includes customer info when email is set', function () {
    $order = Order::factory()->create(['customer_email' => 'buyer@example.com']);

    $schema = EmailSchemaBuilder::forOrder($order, 'https://schema.org/OrderProcessing');

    expect($schema['customer']['@type'])->toBe('Person')
        ->and($schema['customer']['email'])->toBe('buyer@example.com');
});

test('forShipment produces a ParcelDelivery wrapping the order', function () {
    $order = Order::factory()->create();
    $shipment = OrderShipment::factory()->create([
        'order_id' => $order->id,
        'tracking_number' => 'TRACK-100',
        'tracking_url' => 'https://carrier.example/TRACK-100',
    ]);

    $schema = EmailSchemaBuilder::forShipment($order, $shipment);

    expect($schema['@context'])->toBe('https://schema.org')
        ->and($schema['@type'])->toBe('ParcelDelivery')
        ->and($schema['trackingNumber'])->toBe('TRACK-100')
        ->and($schema['trackingUrl'])->toBe('https://carrier.example/TRACK-100')
        ->and($schema['partOfOrder']['@type'])->toBe('Order')
        ->and($schema['partOfOrder']['orderNumber'])->toBe((string) $order->id);
});

test('forShipment omits tracking fields when shipment has none', function () {
    $order = Order::factory()->create();
    $shipment = OrderShipment::factory()->create([
        'order_id' => $order->id,
        'tracking_number' => null,
        'tracking_url' => null,
    ]);

    $schema = EmailSchemaBuilder::forShipment($order, $shipment);

    expect($schema)->not->toHaveKey('trackingNumber')
        ->and($schema)->not->toHaveKey('trackingUrl');
});

test('forShipment includes delivery address when shipping address exists', function () {
    $order = Order::factory()->create();
    OrderAddress::factory()->create([
        'order_id' => $order->id,
        'type' => OrderAddressType::Shipping,
        'address_line_1' => '1453 Mission St',
        'city' => 'San Francisco',
        'state' => 'CA',
        'postal_code' => '94103',
        'country_code' => 'US',
    ]);
    $order->load('shippingAddress');
    $shipment = OrderShipment::factory()->create(['order_id' => $order->id]);

    $schema = EmailSchemaBuilder::forShipment($order, $shipment);

    expect($schema['deliveryAddress']['@type'])->toBe('PostalAddress')
        ->and($schema['deliveryAddress']['streetAddress'])->toContain('1453 Mission St')
        ->and($schema['deliveryAddress']['addressLocality'])->toBe('San Francisco')
        ->and($schema['deliveryAddress']['postalCode'])->toBe('94103')
        ->and($schema['deliveryAddress']['addressCountry'])->toBe('US');
});
