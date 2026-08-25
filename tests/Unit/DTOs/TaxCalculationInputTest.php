<?php

declare(strict_types=1);

use App\DTOs\AddressLocation;
use App\DTOs\HydratedOrderItem;
use App\DTOs\TaxableItem;
use App\DTOs\TaxCalculationInput;
use App\Enums\TaxCategory;
use App\Enums\WeightUnit;
use App\Models\Order;
use App\Models\OrderAddress;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;

covers(TaxCalculationInput::class);

uses()->group('dtos', 'tax');

test('can be constructed with all fields', function () {
    $items = [
        new TaxableItem(id: 1, totalPrice: '100.0000', isTaxExempt: false, taxCategory: TaxCategory::Standard),
        new TaxableItem(id: 2, totalPrice: '50.0000', isTaxExempt: false, taxCategory: TaxCategory::Standard),
    ];
    $shippingAddress = new AddressLocation(countryCode: 'US', state: 'CA', postalCode: '90210');
    $billingAddress = new AddressLocation(countryCode: 'US', state: 'NY', postalCode: '10001');

    $storeAddress = new AddressLocation(countryCode: 'US', state: 'TX', postalCode: '73301');

    $input = new TaxCalculationInput(
        subtotal: '150.0000',
        discountTotal: '10.0000',
        shippingTotal: '15.0000',
        pricesIncludeTax: false,
        shippingIsTaxable: false,
        taxBasedOn: 'shipping',
        defaultTaxRate: '5.0000',
        storeAddress: $storeAddress,
        items: $items,
        shippingAddress: $shippingAddress,
        billingAddress: $billingAddress,
    );

    expect($input->subtotal)->toBe('150.0000')
        ->and($input->discountTotal)->toBe('10.0000')
        ->and($input->shippingTotal)->toBe('15.0000')
        ->and($input->pricesIncludeTax)->toBeFalse()
        ->and($input->taxBasedOn)->toBe('shipping')
        ->and($input->defaultTaxRate)->toBe('5.0000')
        ->and($input->storeAddress->countryCode)->toBe('US')
        ->and($input->items)->toHaveCount(2)
        ->and($input->shippingAddress->countryCode)->toBe('US')
        ->and($input->billingAddress->state)->toBe('NY');
});

test('addresses default to null', function () {
    $input = new TaxCalculationInput(
        subtotal: '100.0000',
        discountTotal: '0.0000',
        shippingTotal: '0.0000',
        pricesIncludeTax: false,
        shippingIsTaxable: false,
        taxBasedOn: 'shipping',
        defaultTaxRate: null,
        storeAddress: null,
        items: [new TaxableItem(id: 1, totalPrice: '100.0000', isTaxExempt: false, taxCategory: null)],
    );

    expect($input->shippingAddress)->toBeNull()
        ->and($input->billingAddress)->toBeNull();
});

test('properties are readonly', function () {
    $input = new TaxCalculationInput(
        subtotal: '100.0000',
        discountTotal: '0.0000',
        shippingTotal: '0.0000',
        pricesIncludeTax: false,
        shippingIsTaxable: false,
        taxBasedOn: 'shipping',
        defaultTaxRate: null,
        storeAddress: null,
        items: [],
    );

    expect(fn () => $input->subtotal = '200.0000')->toThrow(Error::class);
});

test('fromOrder maps order fields to input', function () {
    $order = Order::factory()->create([
        'subtotal' => '200.0000',
        'discount_total' => '20.0000',
        'shipping_total' => '15.0000',
    ]);

    $product = Product::factory()->create(['is_tax_exempt' => false, 'tax_category' => TaxCategory::Standard]);
    OrderItem::factory()->for($order)->for($product)->create(['total_price' => '200.0000']);

    $input = TaxCalculationInput::fromOrder($order);

    expect($input->subtotal)->toBe('200.0000')
        ->and($input->discountTotal)->toBe('20.0000')
        ->and($input->shippingTotal)->toBe('15.0000')
        ->and($input->items)->toHaveCount(1);
});

test('fromOrder maps prices_include_tax from order', function () {
    $order = Order::factory()->create(['prices_include_tax' => true]);
    $product = Product::factory()->create(['is_tax_exempt' => false]);
    OrderItem::factory()->for($order)->for($product)->create();

    $input = TaxCalculationInput::fromOrder($order);

    expect($input->pricesIncludeTax)->toBeTrue();
});

test('fromOrder maps shipping_is_taxable from order', function () {
    $order = Order::factory()->create(['shipping_is_taxable' => true]);
    $product = Product::factory()->create(['is_tax_exempt' => false]);
    OrderItem::factory()->for($order)->for($product)->create();

    $input = TaxCalculationInput::fromOrder($order);

    expect($input->shippingIsTaxable)->toBeTrue();
});

test('fromOrder maps tax_based_on from order', function () {
    $order = Order::factory()->create(['tax_based_on' => 'billing']);
    $product = Product::factory()->create(['is_tax_exempt' => false]);
    OrderItem::factory()->for($order)->for($product)->create();

    $input = TaxCalculationInput::fromOrder($order);

    expect($input->taxBasedOn)->toBe('billing');
});

test('fromOrder maps default_tax_rate from order', function () {
    $order = Order::factory()->create(['default_tax_rate' => '7.5000']);
    $product = Product::factory()->create(['is_tax_exempt' => false]);
    OrderItem::factory()->for($order)->for($product)->create();

    $input = TaxCalculationInput::fromOrder($order);

    expect($input->defaultTaxRate)->toBe('7.5000');
});

test('fromOrder maps store address from order', function () {
    $order = Order::factory()->create([
        'tax_store_country_code' => 'US',
        'tax_store_state' => 'TX',
        'tax_store_postal_code' => '73301',
    ]);
    $product = Product::factory()->create(['is_tax_exempt' => false]);
    OrderItem::factory()->for($order)->for($product)->create();

    $input = TaxCalculationInput::fromOrder($order);

    expect($input->storeAddress)->not->toBeNull()
        ->and($input->storeAddress->countryCode)->toBe('US')
        ->and($input->storeAddress->state)->toBe('TX')
        ->and($input->storeAddress->postalCode)->toBe('73301');
});

test('fromOrder sets null store address when order has no store country', function () {
    $order = Order::factory()->create(['tax_store_country_code' => null]);
    $product = Product::factory()->create(['is_tax_exempt' => false]);
    OrderItem::factory()->for($order)->for($product)->create();

    $input = TaxCalculationInput::fromOrder($order);

    expect($input->storeAddress)->toBeNull();
});

test('fromOrder maps shipping address', function () {
    $order = Order::factory()->create();
    $product = Product::factory()->create(['is_tax_exempt' => false]);
    OrderItem::factory()->for($order)->for($product)->create();

    OrderAddress::factory()->forOrder($order)->shipping()->create([
        'country_code' => 'US',
        'state' => 'CA',
        'postal_code' => '90210',
    ]);

    $input = TaxCalculationInput::fromOrder($order);

    expect($input->shippingAddress)->not->toBeNull()
        ->and($input->shippingAddress->countryCode)->toBe('US')
        ->and($input->shippingAddress->state)->toBe('CA')
        ->and($input->shippingAddress->postalCode)->toBe('90210');
});

test('fromOrder maps billing address', function () {
    $order = Order::factory()->create();
    $product = Product::factory()->create(['is_tax_exempt' => false]);
    OrderItem::factory()->for($order)->for($product)->create();

    OrderAddress::factory()->forOrder($order)->billing()->create([
        'country_code' => 'GB',
        'state' => 'England',
        'postal_code' => 'SW1A 2AA',
    ]);

    $input = TaxCalculationInput::fromOrder($order);

    expect($input->billingAddress)->not->toBeNull()
        ->and($input->billingAddress->countryCode)->toBe('GB')
        ->and($input->billingAddress->state)->toBe('England');
});

test('fromOrder sets null addresses when order has none', function () {
    $order = Order::factory()->create();
    $product = Product::factory()->create(['is_tax_exempt' => false]);
    OrderItem::factory()->for($order)->for($product)->create();

    $input = TaxCalculationInput::fromOrder($order);

    expect($input->shippingAddress)->toBeNull()
        ->and($input->billingAddress)->toBeNull();
});

test('fromOrder maps tax exempt product', function () {
    $order = Order::factory()->create();
    $product = Product::factory()->create(['is_tax_exempt' => true, 'tax_category' => null]);
    OrderItem::factory()->for($order)->for($product)->create(['total_price' => '100.0000']);

    $input = TaxCalculationInput::fromOrder($order);

    expect($input->items[0]->isTaxExempt)->toBeTrue()
        ->and($input->items[0]->taxCategory)->toBeNull();
});

test('fromOrder maps non-exempt product with tax category', function () {
    $order = Order::factory()->create();
    $product = Product::factory()->create(['is_tax_exempt' => false, 'tax_category' => TaxCategory::Electronics]);
    $item = OrderItem::factory()->for($order)->for($product)->create(['total_price' => '75.0000']);

    $input = TaxCalculationInput::fromOrder($order);

    expect($input->items[0]->isTaxExempt)->toBeFalse()
        ->and($input->items[0]->taxCategory)->toBe(TaxCategory::Electronics)
        ->and($input->items[0]->id)->toBe($item->id)
        ->and($input->items[0]->totalPrice)->toBe('75.0000');
});

test('fromOrder maps multiple items', function () {
    $order = Order::factory()->create(['subtotal' => '250.0000']);
    $product1 = Product::factory()->create(['is_tax_exempt' => false]);
    $product2 = Product::factory()->create(['is_tax_exempt' => true]);
    OrderItem::factory()->for($order)->for($product1)->create(['total_price' => '100.0000']);
    OrderItem::factory()->for($order)->for($product2)->create(['total_price' => '150.0000']);

    $input = TaxCalculationInput::fromOrder($order);

    expect($input->items)->toHaveCount(2);
});

test('fromOrder maps variant item using order item total price', function () {
    $order = Order::factory()->create(['subtotal' => '75.0000']);
    $product = Product::factory()->create(['is_tax_exempt' => false, 'tax_category' => TaxCategory::Standard]);
    $variant = ProductVariant::factory()->for($product)->create(['price' => '75.0000']);
    $item = OrderItem::factory()->for($order)->for($product)->create([
        'total_price' => '75.0000',
        'product_variant_id' => $variant->id,
    ]);

    $input = TaxCalculationInput::fromOrder($order);

    expect($input->items[0]->id)->toBe($item->id)
        ->and($input->items[0]->totalPrice)->toBe('75.0000');
});

test('fromHydratedItems maps items and addresses', function () {
    $product = Product::factory()->create(['is_tax_exempt' => false, 'tax_category' => TaxCategory::Standard]);

    $hydratedItem = new HydratedOrderItem(
        product: $product,
        variant: null,
        unitPrice: '100.0000',
        totalPrice: '100.0000',
        quantity: 1,
        variantOptions: null,
        productTitle: ['en' => 'Test'],
        productSku: 'SKU-001',
        variantTitle: null,
        requiresShipping: true,
        weight: '1.0',
        weightUnit: WeightUnit::Kg,
    );

    $input = TaxCalculationInput::fromHydratedItems(
        hydratedItems: [$hydratedItem],
        subtotal: '100.0000',
        shippingTotal: '10.0000',
        discountTotal: '5.0000',
        shippingAddress: new AddressLocation(countryCode: 'US', state: 'CA', postalCode: '90210'),
        billingAddress: new AddressLocation(countryCode: 'US', state: 'NY', postalCode: '10001'),
    );

    expect($input->subtotal)->toBe('100.0000')
        ->and($input->shippingTotal)->toBe('10.0000')
        ->and($input->discountTotal)->toBe('5.0000')
        ->and($input->items)->toHaveCount(1)
        ->and($input->items[0]->id)->toBeNull()
        ->and($input->items[0]->totalPrice)->toBe('100.0000')
        ->and($input->items[0]->isTaxExempt)->toBeFalse()
        ->and($input->items[0]->taxCategory)->toBe($product->tax_category)
        ->and($input->shippingAddress->countryCode)->toBe('US')
        ->and($input->shippingAddress->state)->toBe('CA')
        ->and($input->billingAddress->state)->toBe('NY');
});

test('fromHydratedItems sets null addresses for empty arrays', function () {
    $product = Product::factory()->create(['is_tax_exempt' => false]);

    $hydratedItem = new HydratedOrderItem(
        product: $product,
        variant: null,
        unitPrice: '50.0000',
        totalPrice: '50.0000',
        quantity: 1,
        variantOptions: null,
        productTitle: ['en' => 'Product'],
        productSku: 'SKU-001',
        variantTitle: null,
        requiresShipping: false,
        weight: null,
        weightUnit: null,
    );

    $input = TaxCalculationInput::fromHydratedItems(
        hydratedItems: [$hydratedItem],
        subtotal: '50.0000',
        shippingTotal: '0.0000',
        discountTotal: '0.0000',
        shippingAddress: null,
        billingAddress: null,
    );

    expect($input->shippingAddress)->toBeNull()
        ->and($input->billingAddress)->toBeNull();
});

test('fromHydratedItems maps tax exempt product', function () {
    $product = Product::factory()->create(['is_tax_exempt' => true, 'tax_category' => null]);

    $hydratedItem = new HydratedOrderItem(
        product: $product,
        variant: null,
        unitPrice: '75.0000',
        totalPrice: '75.0000',
        quantity: 1,
        variantOptions: null,
        productTitle: ['en' => 'Exempt'],
        productSku: 'EXEMPT-001',
        variantTitle: null,
        requiresShipping: true,
        weight: '1.0',
        weightUnit: WeightUnit::Kg,
    );

    $input = TaxCalculationInput::fromHydratedItems(
        hydratedItems: [$hydratedItem],
        subtotal: '75.0000',
        shippingTotal: '0.0000',
        discountTotal: '0.0000',
        shippingAddress: null,
        billingAddress: null,
    );

    expect($input->items[0]->isTaxExempt)->toBeTrue()
        ->and($input->items[0]->taxCategory)->toBeNull();
});

test('fromHydratedItems maps multiple items', function () {
    $product1 = Product::factory()->create(['is_tax_exempt' => false, 'tax_category' => TaxCategory::Standard]);
    $product2 = Product::factory()->create(['is_tax_exempt' => false, 'tax_category' => TaxCategory::Electronics]);

    $makeItem = fn (Product $product, string $price): HydratedOrderItem => new HydratedOrderItem(
        product: $product,
        variant: null,
        unitPrice: $price,
        totalPrice: $price,
        quantity: 1,
        variantOptions: null,
        productTitle: ['en' => 'Product'],
        productSku: 'SKU',
        variantTitle: null,
        requiresShipping: false,
        weight: null,
        weightUnit: null,
    );

    $input = TaxCalculationInput::fromHydratedItems(
        hydratedItems: [$makeItem($product1, '50.0000'), $makeItem($product2, '75.0000')],
        subtotal: '125.0000',
        shippingTotal: '0.0000',
        discountTotal: '0.0000',
        shippingAddress: null,
        billingAddress: null,
    );

    expect($input->items)->toHaveCount(2)
        ->and($input->subtotal)->toBe('125.0000');
});
