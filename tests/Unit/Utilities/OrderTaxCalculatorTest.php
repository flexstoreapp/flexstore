<?php

declare(strict_types=1);

use App\DTOs\AddressLocation;
use App\DTOs\HydratedOrderItem;
use App\DTOs\TaxCalculationInput;
use App\DTOs\TaxCalculationResult;
use App\Enums\OrderAddressType;
use App\Enums\TaxCategory;
use App\Models\Order;
use App\Models\OrderAddress;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Region;
use App\Models\Setting;
use App\Models\TaxRate;
use App\Utilities\OrderTaxCalculator;

covers(OrderTaxCalculator::class, TaxCalculationInput::class, TaxCalculationResult::class);

function makeHydratedItem(Product $product, string $totalPrice): HydratedOrderItem
{
    return new HydratedOrderItem(
        product: $product,
        variant: null,
        unitPrice: $totalPrice,
        totalPrice: $totalPrice,
        quantity: 1,
        variantOptions: null,
        productTitle: [],
        productSku: '',
        variantTitle: null,
        requiresShipping: false,
        weight: null,
        weightUnit: null,
    );
}

uses()->group('utilities', 'order', 'tax');

test('calculates complete order with items, discount, and shipping tax', function (): void {
    Setting::setValue('shipping_is_taxable', true);
    Setting::setValue('prices_include_tax', false);
    Setting::setValue('tax_based_on', 'shipping');

    $region = Region::factory()->create([
        'countries' => ['US'],
        'states' => ['CA'],
        'postal_codes' => ['90210'],
        'is_active' => true,
    ]);

    $taxCategory = TaxCategory::Standard;
    $taxRate = TaxRate::factory()->withoutConditions()->create([
        'region_id' => $region->id,
        'tax_category' => null, // Applies to everything
        'rate' => '8.25',
        'is_active' => true,
        'is_compound' => false,
    ]);

    $product1 = Product::factory()->create([
        'tax_category' => $taxCategory,
        'is_tax_exempt' => false,
    ]);

    $product2 = Product::factory()->create([
        'tax_category' => $taxCategory,
        'is_tax_exempt' => false,
    ]);

    $order = Order::factory()->create([
        'subtotal' => '200.0000',      // $100 + $100
        'discount_total' => '20.0000',  // 10% discount
        'shipping_total' => '15.0000',
    ]);

    $orderItem1 = OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product1->id,
        'total_price' => '100.0000',
    ]);

    $orderItem2 = OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product2->id,
        'total_price' => '100.0000',
    ]);

    // Create shipping address that matches the region
    $shippingAddress = OrderAddress::factory()->create([
        'order_id' => $order->id,
        'type' => OrderAddressType::Shipping,
        'country_code' => 'US',
        'state' => 'CA',
        'postal_code' => '90210',
    ]);

    $order->setRelation('items', collect([$orderItem1, $orderItem2]));
    $order->setRelation('shippingAddress', $shippingAddress);
    $orderItem1->setRelation('product', $product1);
    $orderItem2->setRelation('product', $product2);

    $calculator = new OrderTaxCalculator();
    $result = $calculator->calculate(TaxCalculationInput::fromOrder($order));

    // Expected calculations:
    // Discount ratio: $20/$200 = 0.1 (10%)
    // Item 1 discounted: $100 - ($100 * 0.1) = $90, tax = $90 * 8.25% = $7.4250
    // Item 2 discounted: $100 - ($100 * 0.1) = $90, tax = $90 * 8.25% = $7.4250
    // Shipping tax: $15 * 8.25% = $1.2375
    // Total tax: $7.4250 + $7.4250 + $1.2375 = $16.0875
    expect($result->taxTotal)->toBe('16.0875');
    expect($result->taxDetails)->toHaveCount(3); // 2 items + 1 shipping
});

test('calculates tax for order items without discount', function (): void {
    Setting::setValue('prices_include_tax', false);
    Setting::setValue('tax_based_on', 'shipping');

    $region = Region::factory()->create([
        'countries' => ['US'],
        'states' => ['CA'],
        'postal_codes' => ['90210'],
        'is_active' => true,
    ]);

    $taxCategory = TaxCategory::Standard;
    $taxRate = TaxRate::factory()->withoutConditions()->create([
        'region_id' => $region->id,
        'tax_category' => $taxCategory,
        'rate' => '10.00',
        'is_active' => true,
        'is_compound' => false,
    ]);

    $product = Product::factory()->create([
        'tax_category' => $taxCategory,
        'is_tax_exempt' => false,
    ]);

    $order = Order::factory()->create([
        'subtotal' => '100.0000',
        'discount_total' => '0.0000',
        'shipping_total' => '0.0000',
    ]);

    $orderItem = OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'total_price' => '100.0000',
    ]);

    $shippingAddress = OrderAddress::factory()->create([
        'order_id' => $order->id,
        'type' => OrderAddressType::Shipping,
        'country_code' => 'US',
        'state' => 'CA',
        'postal_code' => '90210',
    ]);

    $order->setRelation('items', collect([$orderItem]));
    $order->setRelation('shippingAddress', $shippingAddress);
    $orderItem->setRelation('product', $product);

    $calculator = new OrderTaxCalculator();
    $result = $calculator->calculate(TaxCalculationInput::fromOrder($order));

    expect($result->taxTotal)->toBe('10.0000');
    expect($result->taxDetails)->toHaveCount(1);
    expect($result->taxDetails[0]->taxAmount)->toBe('10.0000');
    expect($result->taxDetails[0]->taxableAmount)->toBe('100.0000');
});

test('calculates tax considering order-level discount', function (): void {
    Setting::setValue('prices_include_tax', false);
    Setting::setValue('tax_based_on', 'shipping');

    $region = Region::factory()->create([
        'countries' => ['US'],
        'states' => ['CA'],
        'postal_codes' => ['90210'],
        'is_active' => true,
    ]);

    $taxCategory = TaxCategory::Standard;
    $taxRate = TaxRate::factory()->withoutConditions()->create([
        'region_id' => $region->id,
        'tax_category' => $taxCategory,
        'rate' => '10.00',
        'is_active' => true,
        'is_compound' => false,
    ]);

    $product = Product::factory()->create([
        'tax_category' => $taxCategory,
        'is_tax_exempt' => false,
    ]);

    $order = Order::factory()->create([
        'subtotal' => '100.0000',
        'discount_total' => '20.0000', // 20% discount
        'shipping_total' => '0.0000',
    ]);

    $orderItem = OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'total_price' => '100.0000',
    ]);

    $shippingAddress = OrderAddress::factory()->create([
        'order_id' => $order->id,
        'type' => OrderAddressType::Shipping,
        'country_code' => 'US',
        'state' => 'CA',
        'postal_code' => '90210',
    ]);

    $order->setRelation('items', collect([$orderItem]));
    $order->setRelation('shippingAddress', $shippingAddress);
    $orderItem->setRelation('product', $product);

    $calculator = new OrderTaxCalculator();
    $result = $calculator->calculate(TaxCalculationInput::fromOrder($order));

    // Tax should be calculated on discounted amount: ($100 - $20) * 10% = $8.00
    expect($result->taxTotal)->toBe('8.0000');
    expect($result->taxDetails[0]->taxableAmount)->toBe('80.0000');
    expect($result->taxDetails[0]->taxAmount)->toBe('8.0000');
});

test('calculates tax for multiple items with proportional discount', function (): void {
    Setting::setValue('prices_include_tax', false);
    Setting::setValue('tax_based_on', 'shipping');

    $region = Region::factory()->create([
        'countries' => ['US'],
        'states' => ['CA'],
        'postal_codes' => ['90210'],
        'is_active' => true,
    ]);

    $taxCategory = TaxCategory::Standard;
    $taxRate = TaxRate::factory()->withoutConditions()->create([
        'region_id' => $region->id,
        'tax_category' => $taxCategory,
        'rate' => '10.00',
        'is_active' => true,
        'is_compound' => false,
    ]);

    $product1 = Product::factory()->create([
        'tax_category' => $taxCategory,
        'is_tax_exempt' => false,
    ]);

    $product2 = Product::factory()->create([
        'tax_category' => $taxCategory,
        'is_tax_exempt' => false,
    ]);

    $order = Order::factory()->create([
        'subtotal' => '150.0000', // 100 + 50
        'discount_total' => '30.0000', // 20% discount
        'shipping_total' => '0.0000',
    ]);

    $orderItem1 = OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product1->id,
        'total_price' => '100.0000',
    ]);

    $orderItem2 = OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product2->id,
        'total_price' => '50.0000',
    ]);

    $shippingAddress = OrderAddress::factory()->create([
        'order_id' => $order->id,
        'type' => OrderAddressType::Shipping,
        'country_code' => 'US',
        'state' => 'CA',
        'postal_code' => '90210',
    ]);

    $order->setRelation('items', collect([$orderItem1, $orderItem2]));
    $order->setRelation('shippingAddress', $shippingAddress);
    $orderItem1->setRelation('product', $product1);
    $orderItem2->setRelation('product', $product2);

    $calculator = new OrderTaxCalculator();
    $result = $calculator->calculate(TaxCalculationInput::fromOrder($order));

    // Discount ratio: 30/150 = 0.2 (20%)
    // Item 1 discounted: $100 - ($100 * 0.2) = $80, tax = $8.00
    // Item 2 discounted: $50 - ($50 * 0.2) = $40, tax = $4.00
    // Total tax: $8.00 + $4.00 = $12.00
    expect($result->taxTotal)->toBe('12.0000');
    expect($result->taxDetails)->toHaveCount(2);
});

test('handles full discount correctly', function (): void {
    Setting::setValue('prices_include_tax', false);
    Setting::setValue('tax_based_on', 'shipping');

    $region = Region::factory()->create([
        'countries' => ['US'],
        'states' => ['CA'],
        'postal_codes' => ['90210'],
        'is_active' => true,
    ]);

    $taxCategory = TaxCategory::Standard;
    $taxRate = TaxRate::factory()->withoutConditions()->create([
        'region_id' => $region->id,
        'tax_category' => $taxCategory,
        'rate' => '10.00',
        'is_active' => true,
        'is_compound' => false,
    ]);

    $product = Product::factory()->create([
        'tax_category' => $taxCategory,
        'is_tax_exempt' => false,
    ]);

    $order = Order::factory()->create([
        'subtotal' => '100.0000',
        'discount_total' => '100.0000', // Full discount
        'shipping_total' => '0.0000',
    ]);

    $orderItem = OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'total_price' => '100.0000',
    ]);

    $shippingAddress = OrderAddress::factory()->create([
        'order_id' => $order->id,
        'type' => OrderAddressType::Shipping,
        'country_code' => 'US',
        'state' => 'CA',
        'postal_code' => '90210',
    ]);

    $order->setRelation('items', collect([$orderItem]));
    $order->setRelation('shippingAddress', $shippingAddress);
    $orderItem->setRelation('product', $product);

    $calculator = new OrderTaxCalculator();
    $result = $calculator->calculate(TaxCalculationInput::fromOrder($order));

    expect($result->taxTotal)->toBe('0.0000');
    expect($result->taxDetails)->toBeEmpty();
});

test('calculates shipping tax when shipping is taxable using item tax rates', function (): void {
    Setting::setValue('shipping_is_taxable', true);
    Setting::setValue('prices_include_tax', false);
    Setting::setValue('tax_based_on', 'shipping');

    $region = Region::factory()->create([
        'countries' => ['US'],
        'states' => ['CA'],
        'postal_codes' => ['90210'],
        'is_active' => true,
    ]);

    $taxCategory = TaxCategory::Standard;
    $taxRate = TaxRate::factory()->withoutConditions()->create([
        'region_id' => $region->id,
        'tax_category' => $taxCategory,
        'rate' => '10.00',
        'is_active' => true,
        'is_compound' => false,
    ]);

    $product = Product::factory()->create([
        'tax_category' => $taxCategory,
        'is_tax_exempt' => false,
    ]);

    $order = Order::factory()->create([
        'subtotal' => '100.0000',
        'discount_total' => '0.0000',
        'shipping_total' => '15.0000',
    ]);

    $orderItem = OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'total_price' => '100.0000',
    ]);

    $shippingAddress = OrderAddress::factory()->create([
        'order_id' => $order->id,
        'type' => OrderAddressType::Shipping,
        'country_code' => 'US',
        'state' => 'CA',
        'postal_code' => '90210',
    ]);

    $order->setRelation('items', collect([$orderItem]));
    $order->setRelation('shippingAddress', $shippingAddress);
    $orderItem->setRelation('product', $product);

    $calculator = new OrderTaxCalculator();
    $result = $calculator->calculate(TaxCalculationInput::fromOrder($order));

    // Item tax: $100 * 10% = $10.00
    // Shipping tax: $15 * 10% = $1.50
    // Total: $11.50
    expect($result->taxTotal)->toBe('11.5000');
    expect($result->taxDetails)->toHaveCount(2); // 1 item + 1 shipping
});

test('uses default tax rate when no specific tax rates match', function (): void {
    Setting::setValue('prices_include_tax', false);
    Setting::setValue('tax_based_on', 'shipping');
    Setting::setValue('default_tax_rate', '5.00');

    $product = Product::factory()->create(['is_tax_exempt' => false]);

    $order = Order::factory()->create([
        'subtotal' => '100.0000',
        'discount_total' => '0.0000',
        'shipping_total' => '0.0000',
    ]);

    $orderItem = OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'total_price' => '100.0000',
    ]);

    // Create address that doesn't match any region
    $shippingAddress = OrderAddress::factory()->create([
        'order_id' => $order->id,
        'type' => OrderAddressType::Shipping,
        'country_code' => 'ZZ',
        'state' => 'NOMATCH',
        'postal_code' => 'NOMATCH',
    ]);

    $order->setRelation('items', collect([$orderItem]));
    $order->setRelation('shippingAddress', $shippingAddress);
    $orderItem->setRelation('product', $product);

    $calculator = new OrderTaxCalculator();
    $result = $calculator->calculate(TaxCalculationInput::fromOrder($order));

    expect($result->taxTotal)->toBe('5.0000');
    expect($result->taxDetails[0]->taxName)->toBe([app()->getLocale() => 'Default Tax']);
});

test('does not use default tax rate when null', function (): void {
    Setting::setValue('prices_include_tax', false);
    Setting::setValue('tax_based_on', 'shipping');
    Setting::setValue('default_tax_rate', null);

    $product = Product::factory()->create(['is_tax_exempt' => false]);

    $order = Order::factory()->create([
        'subtotal' => '100.0000',
        'discount_total' => '0.0000',
        'shipping_total' => '0.0000',
    ]);

    $orderItem = OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'total_price' => '100.0000',
    ]);

    $shippingAddress = OrderAddress::factory()->create([
        'order_id' => $order->id,
        'type' => OrderAddressType::Shipping,
        'country_code' => 'ZZ',
        'state' => 'NOMATCH',
        'postal_code' => 'NOMATCH',
    ]);

    $order->setRelation('items', collect([$orderItem]));
    $order->setRelation('shippingAddress', $shippingAddress);
    $orderItem->setRelation('product', $product);

    $calculator = new OrderTaxCalculator();
    $result = $calculator->calculate(TaxCalculationInput::fromOrder($order));

    expect($result->taxTotal)->toBe('0.0000');
    expect($result->taxDetails)->toBeEmpty();
});

test('skips tax-exempt products', function (): void {
    Setting::setValue('prices_include_tax', false);
    Setting::setValue('tax_based_on', 'shipping');

    $region = Region::factory()->create([
        'countries' => ['US'],
        'states' => ['CA'],
        'postal_codes' => ['90210'],
        'is_active' => true,
    ]);

    $taxCategory = TaxCategory::Standard;
    $taxRate = TaxRate::factory()->withoutConditions()->create([
        'region_id' => $region->id,
        'tax_category' => $taxCategory,
        'rate' => '10.00',
        'is_active' => true,
        'is_compound' => false,
    ]);

    $product = Product::factory()->create([
        'tax_category' => $taxCategory,
        'is_tax_exempt' => true, // Tax exempt
    ]);

    $order = Order::factory()->create([
        'subtotal' => '100.0000',
        'discount_total' => '0.0000',
        'shipping_total' => '0.0000',
    ]);

    $orderItem = OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'total_price' => '100.0000',
    ]);

    $shippingAddress = OrderAddress::factory()->create([
        'order_id' => $order->id,
        'type' => OrderAddressType::Shipping,
        'country_code' => 'US',
        'state' => 'CA',
        'postal_code' => '90210',
    ]);

    $order->setRelation('items', collect([$orderItem]));
    $order->setRelation('shippingAddress', $shippingAddress);
    $orderItem->setRelation('product', $product);

    $calculator = new OrderTaxCalculator();
    $result = $calculator->calculate(TaxCalculationInput::fromOrder($order));

    expect($result->taxTotal)->toBe('0.0000');
    expect($result->taxDetails)->toBeEmpty();
});

test('resolves region from shipping address when tax_based_on is shipping', function (): void {
    Setting::setValue('prices_include_tax', false);
    Setting::setValue('tax_based_on', 'shipping');

    $region = Region::factory()->create([
        'countries' => ['US'],
        'states' => ['CA'],
        'postal_codes' => ['90210'],
        'is_active' => true,
    ]);

    $taxCategory = TaxCategory::Standard;
    $taxRate = TaxRate::factory()->withoutConditions()->create([
        'region_id' => $region->id,
        'tax_category' => $taxCategory,
        'rate' => '8.25',
        'is_active' => true,
        'is_compound' => false,
    ]);

    $product = Product::factory()->create([
        'tax_category' => $taxCategory,
        'is_tax_exempt' => false,
    ]);

    $order = Order::factory()->create([
        'subtotal' => '100.0000',
        'discount_total' => '0.0000',
        'shipping_total' => '0.0000',
    ]);

    $orderItem = OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'total_price' => '100.0000',
    ]);

    $shippingAddress = OrderAddress::factory()->create([
        'order_id' => $order->id,
        'type' => OrderAddressType::Shipping,
        'country_code' => 'US',
        'state' => 'CA',
        'postal_code' => '90210',
    ]);

    $billingAddress = OrderAddress::factory()->create([
        'order_id' => $order->id,
        'type' => OrderAddressType::Billing,
        'country_code' => 'XX',
        'state' => 'DIFFERENT',
        'postal_code' => 'DIFFERENT',
    ]);

    $order->setRelation('items', collect([$orderItem]));
    $order->setRelation('shippingAddress', $shippingAddress);
    $order->setRelation('billingAddress', $billingAddress);
    $orderItem->setRelation('product', $product);

    $calculator = new OrderTaxCalculator();
    $result = $calculator->calculate(TaxCalculationInput::fromOrder($order));

    // Should use shipping address and find the matching region
    expect($result->taxTotal)->toBe('8.2500');
});

test('resolves region from billing address when tax_based_on is billing', function (): void {
    Setting::setValue('prices_include_tax', false);
    Setting::setValue('tax_based_on', 'billing');

    $region = Region::factory()->create([
        'countries' => ['US'],
        'states' => ['NY'],
        'postal_codes' => ['10001'],
        'is_active' => true,
    ]);

    $taxCategory = TaxCategory::Standard;
    $taxRate = TaxRate::factory()->withoutConditions()->create([
        'region_id' => $region->id,
        'tax_category' => $taxCategory,
        'rate' => '8.25',
        'is_active' => true,
        'is_compound' => false,
    ]);

    $product = Product::factory()->create([
        'tax_category' => $taxCategory,
        'is_tax_exempt' => false,
    ]);

    $order = Order::factory()->create([
        'subtotal' => '100.0000',
        'discount_total' => '0.0000',
        'shipping_total' => '0.0000',
    ]);

    $orderItem = OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'total_price' => '100.0000',
    ]);

    $shippingAddress = OrderAddress::factory()->create([
        'order_id' => $order->id,
        'type' => OrderAddressType::Shipping,
        'country_code' => 'XX',
        'state' => 'DIFFERENT',
        'postal_code' => 'DIFFERENT',
    ]);

    $billingAddress = OrderAddress::factory()->create([
        'order_id' => $order->id,
        'type' => OrderAddressType::Billing,
        'country_code' => 'US',
        'state' => 'NY',
        'postal_code' => '10001',
    ]);

    $order->setRelation('items', collect([$orderItem]));
    $order->setRelation('shippingAddress', $shippingAddress);
    $order->setRelation('billingAddress', $billingAddress);
    $orderItem->setRelation('product', $product);

    $calculator = new OrderTaxCalculator();
    $result = $calculator->calculate(TaxCalculationInput::fromOrder($order));

    // Should use billing address and find the matching region
    expect($result->taxTotal)->toBe('8.2500');
});

test('resolves region from store address when tax_based_on is store', function (): void {
    Setting::setValue('prices_include_tax', false);
    Setting::setValue('tax_based_on', 'store');
    Setting::setValue('store_country_code', 'US');
    Setting::setValue('store_state', 'TX');
    Setting::setValue('store_postal_code', '75001');

    $region = Region::factory()->create([
        'countries' => ['US'],
        'states' => ['TX'],
        'postal_codes' => ['75001'],
        'is_active' => true,
    ]);

    $taxCategory = TaxCategory::Standard;
    $taxRate = TaxRate::factory()->withoutConditions()->create([
        'region_id' => $region->id,
        'tax_category' => $taxCategory,
        'rate' => '8.25',
        'is_active' => true,
        'is_compound' => false,
    ]);

    $product = Product::factory()->create([
        'tax_category' => $taxCategory,
        'is_tax_exempt' => false,
    ]);

    $order = Order::factory()->create([
        'subtotal' => '100.0000',
        'discount_total' => '0.0000',
        'shipping_total' => '0.0000',
    ]);

    $orderItem = OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'total_price' => '100.0000',
    ]);

    // Order addresses are different from store address
    $shippingAddress = OrderAddress::factory()->create([
        'order_id' => $order->id,
        'type' => OrderAddressType::Shipping,
        'country_code' => 'XX',
        'state' => 'DIFFERENT',
        'postal_code' => 'DIFFERENT',
    ]);

    $order->setRelation('items', collect([$orderItem]));
    $order->setRelation('shippingAddress', $shippingAddress);
    $orderItem->setRelation('product', $product);

    $calculator = new OrderTaxCalculator();
    $result = $calculator->calculate(TaxCalculationInput::fromOrder($order));

    // Should use store address and find the matching region
    expect($result->taxTotal)->toBe('8.2500');
});

test('falls back to default tax rate when no region matches address', function (): void {
    Setting::setValue('prices_include_tax', false);
    Setting::setValue('tax_based_on', 'shipping');
    Setting::setValue('default_tax_rate', '5.00');

    $product = Product::factory()->create(['is_tax_exempt' => false]);

    $order = Order::factory()->create([
        'subtotal' => '100.0000',
        'discount_total' => '0.0000',
        'shipping_total' => '0.0000',
    ]);

    $orderItem = OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'total_price' => '100.0000',
    ]);

    $shippingAddress = OrderAddress::factory()->create([
        'order_id' => $order->id,
        'type' => OrderAddressType::Shipping,
        'country_code' => 'ZZ',
        'state' => 'NOMATCH',
        'postal_code' => 'NOMATCH',
    ]);

    $order->setRelation('items', collect([$orderItem]));
    $order->setRelation('shippingAddress', $shippingAddress);
    $orderItem->setRelation('product', $product);

    $calculator = new OrderTaxCalculator();
    $result = $calculator->calculate(TaxCalculationInput::fromOrder($order));

    expect($result->taxTotal)->toBe('5.0000');
    expect($result->taxDetails[0]->taxName)->toBe([app()->getLocale() => 'Default Tax']);
});

test('matches all addresses when region fields are empty', function (): void {
    Setting::setValue('prices_include_tax', false);
    Setting::setValue('tax_based_on', 'shipping');

    // Create a universal region (all fields empty)
    $region = Region::factory()->create([
        'countries' => [],
        'states' => [],
        'postal_codes' => [],
        'is_active' => true,
    ]);

    $taxCategory = TaxCategory::Standard;
    $taxRate = TaxRate::factory()->withoutConditions()->create([
        'region_id' => $region->id,
        'tax_category' => $taxCategory,
        'rate' => '5.00',
        'is_active' => true,
        'is_compound' => false,
    ]);

    $product = Product::factory()->create([
        'tax_category' => $taxCategory,
        'is_tax_exempt' => false,
    ]);

    $order = Order::factory()->create([
        'subtotal' => '100.0000',
        'discount_total' => '0.0000',
        'shipping_total' => '0.0000',
    ]);

    $orderItem = OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'total_price' => '100.0000',
    ]);

    // Any address should match the universal region
    $shippingAddress = OrderAddress::factory()->create([
        'order_id' => $order->id,
        'type' => OrderAddressType::Shipping,
        'country_code' => 'YY',
        'state' => 'ANY_STATE',
        'postal_code' => 'ANY_POSTAL_CODE',
    ]);

    $order->setRelation('items', collect([$orderItem]));
    $order->setRelation('shippingAddress', $shippingAddress);
    $orderItem->setRelation('product', $product);

    $calculator = new OrderTaxCalculator();
    $result = $calculator->calculate(TaxCalculationInput::fromOrder($order));

    expect($result->taxTotal)->toBe('5.0000');
});

test('matches an address whose postal_code falls in a wildcard or range pattern', function (string $postal_code, string $expectedTaxTotal): void {
    Setting::setValue('prices_include_tax', false);
    Setting::setValue('tax_based_on', 'shipping');

    $region = Region::factory()->create([
        'countries' => [],
        'states' => [],
        'postal_codes' => ['902*', '60601..60699'],
        'is_active' => true,
    ]);

    $taxCategory = TaxCategory::Standard;
    TaxRate::factory()->withoutConditions()->create([
        'region_id' => $region->id,
        'tax_category' => $taxCategory,
        'rate' => '5.00',
        'is_active' => true,
        'is_compound' => false,
    ]);

    $product = Product::factory()->create([
        'tax_category' => $taxCategory,
        'is_tax_exempt' => false,
    ]);

    $order = Order::factory()->create([
        'subtotal' => '100.0000',
        'discount_total' => '0.0000',
        'shipping_total' => '0.0000',
    ]);

    $orderItem = OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'total_price' => '100.0000',
    ]);

    $shippingAddress = OrderAddress::factory()->create([
        'order_id' => $order->id,
        'type' => OrderAddressType::Shipping,
        'country_code' => 'US',
        'state' => 'CA',
        'postal_code' => $postal_code,
    ]);

    $order->setRelation('items', collect([$orderItem]));
    $order->setRelation('shippingAddress', $shippingAddress);
    $orderItem->setRelation('product', $product);

    $calculator = new OrderTaxCalculator();
    $result = $calculator->calculate(TaxCalculationInput::fromOrder($order));

    expect($result->taxTotal)->toBe($expectedTaxTotal);
})->with([
    'wildcard match' => ['90250', '5.0000'],
    'range match' => ['60650', '5.0000'],
    'no match' => ['30303', '0.0000'],
]);

test('prioritizes more specific region when multiple regions match', function (): void {
    Setting::setValue('prices_include_tax', false);
    Setting::setValue('tax_based_on', 'shipping');

    // Create a general region (country only)
    $generalRegion = Region::factory()->create([
        'countries' => ['US'],
        'states' => [],
        'postal_codes' => [],
        'is_active' => true,
    ]);

    // Create a more specific region (country + state + postal_code)
    $specificRegion = Region::factory()->create([
        'countries' => ['US'],
        'states' => ['CA'],
        'postal_codes' => ['90210'],
        'is_active' => true,
    ]);

    $taxCategory = TaxCategory::Standard;

    $generalTaxRate = TaxRate::factory()->withoutConditions()->create([
        'region_id' => $generalRegion->id,
        'tax_category' => $taxCategory,
        'rate' => '5.00',
        'is_active' => true,
        'is_compound' => false,
    ]);

    $specificTaxRate = TaxRate::factory()->withoutConditions()->create([
        'region_id' => $specificRegion->id,
        'tax_category' => $taxCategory,
        'rate' => '8.25',
        'is_active' => true,
        'is_compound' => false,
    ]);

    $product = Product::factory()->create([
        'tax_category' => $taxCategory,
        'is_tax_exempt' => false,
    ]);

    $order = Order::factory()->create([
        'subtotal' => '100.0000',
        'discount_total' => '0.0000',
        'shipping_total' => '0.0000',
    ]);

    $orderItem = OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'total_price' => '100.0000',
    ]);

    $shippingAddress = OrderAddress::factory()->create([
        'order_id' => $order->id,
        'type' => OrderAddressType::Shipping,
        'country_code' => 'US',
        'state' => 'CA',
        'postal_code' => '90210',
    ]);

    $order->setRelation('items', collect([$orderItem]));
    $order->setRelation('shippingAddress', $shippingAddress);
    $orderItem->setRelation('product', $product);

    $calculator = new OrderTaxCalculator();
    $result = $calculator->calculate(TaxCalculationInput::fromOrder($order));

    // Should use the more specific region's tax rate (8.25%)
    expect($result->taxTotal)->toBe('8.2500');
});

test('handles regions with same specificity by using first match', function (): void {
    Setting::setValue('prices_include_tax', false);
    Setting::setValue('tax_based_on', 'shipping');

    // Create two regions with same specificity (both have country only)
    $region1 = Region::factory()->create([
        'countries' => ['US'],
        'states' => [],
        'postal_codes' => [],
        'is_active' => true,
    ]);

    $region2 = Region::factory()->create([
        'countries' => ['US'],
        'states' => [],
        'postal_codes' => [],
        'is_active' => true,
    ]);

    $taxCategory = TaxCategory::Standard;

    $taxRate1 = TaxRate::factory()->withoutConditions()->create([
        'region_id' => $region1->id,
        'tax_category' => $taxCategory,
        'rate' => '5.00',
        'is_active' => true,
        'is_compound' => false,
    ]);

    $taxRate2 = TaxRate::factory()->withoutConditions()->create([
        'region_id' => $region2->id,
        'tax_category' => $taxCategory,
        'rate' => '7.00',
        'is_active' => true,
        'is_compound' => false,
    ]);

    $product = Product::factory()->create([
        'tax_category' => $taxCategory,
        'is_tax_exempt' => false,
    ]);

    $order = Order::factory()->create([
        'subtotal' => '100.0000',
        'discount_total' => '0.0000',
        'shipping_total' => '0.0000',
    ]);

    $orderItem = OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'total_price' => '100.0000',
    ]);

    $shippingAddress = OrderAddress::factory()->create([
        'order_id' => $order->id,
        'type' => OrderAddressType::Shipping,
        'country_code' => 'US',
        'state' => 'CA',
        'postal_code' => '90210',
    ]);

    $order->setRelation('items', collect([$orderItem]));
    $order->setRelation('shippingAddress', $shippingAddress);
    $orderItem->setRelation('product', $product);

    $calculator = new OrderTaxCalculator();
    $result = $calculator->calculate(TaxCalculationInput::fromOrder($order));

    // Should use one of the matching regions (the first one found)
    expect($result->taxTotal)->toBeIn(['5.0000', '7.0000']);
});

// === ADDITIONAL COMPREHENSIVE TESTS ===

test('calculates proportional shipping tax when items have different tax rates', function (): void {
    Setting::setValue('shipping_is_taxable', true);
    Setting::setValue('prices_include_tax', false);
    Setting::setValue('tax_based_on', 'shipping');

    $region = Region::factory()->create([
        'countries' => ['US'],
        'states' => ['CA'],
        'postal_codes' => ['90210'],
        'is_active' => true,
    ]);

    $taxCategory1 = TaxCategory::Standard;
    $taxCategory2 = TaxCategory::Electronics;

    $taxRate1 = TaxRate::factory()->withoutConditions()->create([
        'region_id' => $region->id,
        'tax_category' => $taxCategory1,
        'rate' => '10.00',
        'is_active' => true,
        'is_compound' => false,
    ]);

    $taxRate2 = TaxRate::factory()->withoutConditions()->create([
        'region_id' => $region->id,
        'tax_category' => $taxCategory2,
        'rate' => '5.00',
        'is_active' => true,
        'is_compound' => false,
    ]);

    $product1 = Product::factory()->create([
        'tax_category' => $taxCategory1,
        'is_tax_exempt' => false,
    ]);

    $product2 = Product::factory()->create([
        'tax_category' => $taxCategory2,
        'is_tax_exempt' => false,
    ]);

    $order = Order::factory()->create([
        'subtotal' => '150.0000', // 100 + 50
        'discount_total' => '0.0000',
        'shipping_total' => '20.0000',
    ]);

    $orderItem1 = OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product1->id,
        'total_price' => '100.0000',
    ]);

    $orderItem2 = OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product2->id,
        'total_price' => '50.0000',
    ]);

    $shippingAddress = OrderAddress::factory()->create([
        'order_id' => $order->id,
        'type' => OrderAddressType::Shipping,
        'country_code' => 'US',
        'state' => 'CA',
        'postal_code' => '90210',
    ]);

    $order->setRelation('items', collect([$orderItem1, $orderItem2]));
    $order->setRelation('shippingAddress', $shippingAddress);
    $orderItem1->setRelation('product', $product1);
    $orderItem2->setRelation('product', $product2);

    $calculator = new OrderTaxCalculator();
    $result = $calculator->calculate(TaxCalculationInput::fromOrder($order));

    // Item 1 tax: $100 * 10% = $10.0000
    // Item 2 tax: $50 * 5% = $2.5000
    // Shipping proportions: Item1 = 100/150 = 0.6667, Item2 = 50/150 = 0.3333
    // Shipping tax 1: $20 * 0.6667 * 10% = $1.3333
    // Shipping tax 2: $20 * 0.3333 * 5% = $0.3333
    // Total: $10.0000 + $2.5000 + $1.3333 + $0.3333 = $14.1667 (4-decimal rounding)
    expect($result->taxTotal)->toBe('14.1667');
    expect($result->taxDetails)->toHaveCount(4); // 2 items + 2 shipping
});

test('does not calculate shipping tax when shipping is not taxable', function (): void {
    Setting::setValue('shipping_is_taxable', false);
    Setting::setValue('prices_include_tax', false);
    Setting::setValue('tax_based_on', 'shipping');

    $region = Region::factory()->create([
        'countries' => ['US'],
        'states' => ['CA'],
        'postal_codes' => ['90210'],
        'is_active' => true,
    ]);

    $taxCategory = TaxCategory::Standard;
    $taxRate = TaxRate::factory()->withoutConditions()->create([
        'region_id' => $region->id,
        'tax_category' => $taxCategory,
        'rate' => '10.00',
        'is_active' => true,
        'is_compound' => false,
    ]);

    $product = Product::factory()->create([
        'tax_category' => $taxCategory,
        'is_tax_exempt' => false,
    ]);

    $order = Order::factory()->create([
        'subtotal' => '100.0000',
        'discount_total' => '0.0000',
        'shipping_total' => '15.0000',
    ]);

    $orderItem = OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'total_price' => '100.0000',
    ]);

    $shippingAddress = OrderAddress::factory()->create([
        'order_id' => $order->id,
        'type' => OrderAddressType::Shipping,
        'country_code' => 'US',
        'state' => 'CA',
        'postal_code' => '90210',
    ]);

    $order->setRelation('items', collect([$orderItem]));
    $order->setRelation('shippingAddress', $shippingAddress);
    $orderItem->setRelation('product', $product);

    $calculator = new OrderTaxCalculator();
    $result = $calculator->calculate(TaxCalculationInput::fromOrder($order));

    // Only item tax: $100 * 10% = $10.00
    expect($result->taxTotal)->toBe('10.0000');
    expect($result->taxDetails)->toHaveCount(1); // Only 1 item, no shipping
});

test('does not calculate shipping tax when no items exist', function (): void {
    Setting::setValue('shipping_is_taxable', true);
    Setting::setValue('prices_include_tax', false);
    Setting::setValue('tax_based_on', 'shipping');

    $order = Order::factory()->create([
        'subtotal' => '0.0000',
        'discount_total' => '0.0000',
        'shipping_total' => '15.0000',
    ]);

    $shippingAddress = OrderAddress::factory()->create([
        'order_id' => $order->id,
        'type' => OrderAddressType::Shipping,
        'country_code' => 'US',
        'state' => 'CA',
        'postal_code' => '90210',
    ]);

    $order->setRelation('items', collect([])); // No items
    $order->setRelation('shippingAddress', $shippingAddress);

    $calculator = new OrderTaxCalculator();
    $result = $calculator->calculate(TaxCalculationInput::fromOrder($order));

    expect($result->taxTotal)->toBe('0.0000');
    expect($result->taxDetails)->toBeEmpty();
});

test('calculates tax correctly when prices include tax', function (): void {
    Setting::setValue('prices_include_tax', true);
    Setting::setValue('tax_based_on', 'shipping');

    $region = Region::factory()->create([
        'countries' => ['US'],
        'states' => ['CA'],
        'postal_codes' => ['90210'],
        'is_active' => true,
    ]);

    $taxCategory = TaxCategory::Standard;
    $taxRate = TaxRate::factory()->withoutConditions()->create([
        'region_id' => $region->id,
        'tax_category' => $taxCategory,
        'rate' => '10.00',
        'is_active' => true,
        'is_compound' => false,
    ]);

    $product = Product::factory()->create([
        'tax_category' => $taxCategory,
        'is_tax_exempt' => false,
    ]);

    $order = Order::factory()->create([
        'subtotal' => '110.0000', // Includes tax
        'discount_total' => '0.0000',
        'shipping_total' => '0.0000',
    ]);

    $orderItem = OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'total_price' => '110.0000',
    ]);

    $shippingAddress = OrderAddress::factory()->create([
        'order_id' => $order->id,
        'type' => OrderAddressType::Shipping,
        'country_code' => 'US',
        'state' => 'CA',
        'postal_code' => '90210',
    ]);

    $order->setRelation('items', collect([$orderItem]));
    $order->setRelation('shippingAddress', $shippingAddress);
    $orderItem->setRelation('product', $product);

    $calculator = new OrderTaxCalculator();
    $result = $calculator->calculate(TaxCalculationInput::fromOrder($order));

    // When prices include tax, no additional tax is calculated
    expect($result->taxTotal)->toBe('0.0000');
});

test('calculates shipping tax correctly when prices include tax', function (): void {
    Setting::setValue('shipping_is_taxable', true);
    Setting::setValue('prices_include_tax', true);
    Setting::setValue('tax_based_on', 'shipping');

    $region = Region::factory()->create([
        'countries' => ['US'],
        'states' => ['CA'],
        'postal_codes' => ['90210'],
        'is_active' => true,
    ]);

    $taxCategory = TaxCategory::Standard;
    $taxRate = TaxRate::factory()->withoutConditions()->create([
        'region_id' => $region->id,
        'tax_category' => $taxCategory,
        'rate' => '10.00',
        'is_active' => true,
        'is_compound' => false,
    ]);

    $product = Product::factory()->create([
        'tax_category' => $taxCategory,
        'is_tax_exempt' => false,
    ]);

    $order = Order::factory()->create([
        'subtotal' => '110.0000', // Includes tax
        'discount_total' => '0.0000',
        'shipping_total' => '22.0000', // Includes tax
    ]);

    $orderItem = OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'total_price' => '110.0000',
    ]);

    $shippingAddress = OrderAddress::factory()->create([
        'order_id' => $order->id,
        'type' => OrderAddressType::Shipping,
        'country_code' => 'US',
        'state' => 'CA',
        'postal_code' => '90210',
    ]);

    $order->setRelation('items', collect([$orderItem]));
    $order->setRelation('shippingAddress', $shippingAddress);
    $orderItem->setRelation('product', $product);

    $calculator = new OrderTaxCalculator();
    $result = $calculator->calculate(TaxCalculationInput::fromOrder($order));

    // When prices include tax, no additional tax is calculated
    expect($result->taxTotal)->toBe('0.0000');
});

test('calculates compound taxes correctly', function (): void {
    Setting::setValue('prices_include_tax', false);
    Setting::setValue('tax_based_on', 'shipping');

    $region = Region::factory()->create([
        'countries' => ['US'],
        'states' => ['CA'],
        'postal_codes' => ['90210'],
        'is_active' => true,
    ]);

    $taxCategory = TaxCategory::Standard;

    // First tax rate (non-compound)
    $taxRate1 = TaxRate::factory()->withoutConditions()->create([
        'region_id' => $region->id,
        'tax_category' => $taxCategory,
        'rate' => '5.00',
        'is_active' => true,
        'is_compound' => false,
        'priority' => 1,
    ]);

    // Second tax rate (compound)
    $taxRate2 = TaxRate::factory()->withoutConditions()->create([
        'region_id' => $region->id,
        'tax_category' => $taxCategory,
        'rate' => '10.00',
        'is_active' => true,
        'is_compound' => true,
        'priority' => 2,
    ]);

    $product = Product::factory()->create([
        'tax_category' => $taxCategory,
        'is_tax_exempt' => false,
    ]);

    $order = Order::factory()->create([
        'subtotal' => '100.0000',
        'discount_total' => '0.0000',
        'shipping_total' => '0.0000',
    ]);

    $orderItem = OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'total_price' => '100.0000',
    ]);

    $shippingAddress = OrderAddress::factory()->create([
        'order_id' => $order->id,
        'type' => OrderAddressType::Shipping,
        'country_code' => 'US',
        'state' => 'CA',
        'postal_code' => '90210',
    ]);

    $order->setRelation('items', collect([$orderItem]));
    $order->setRelation('shippingAddress', $shippingAddress);
    $orderItem->setRelation('product', $product);

    $calculator = new OrderTaxCalculator();
    $result = $calculator->calculate(TaxCalculationInput::fromOrder($order));

    // First tax: $100 * 5% = $5.00
    // Second tax (compound): ($100 + $5) * 10% = $10.50
    // Total: $5.00 + $10.50 = $15.50
    expect($result->taxTotal)->toBe('15.5000');
    expect($result->taxDetails)->toHaveCount(2);
});

test('applies tax rate only when order meets minimum value requirement', function (): void {
    Setting::setValue('prices_include_tax', false);
    Setting::setValue('tax_based_on', 'shipping');

    $region = Region::factory()->create([
        'countries' => ['US'],
        'states' => ['CA'],
        'postal_codes' => ['90210'],
        'is_active' => true,
    ]);

    $taxCategory = TaxCategory::Standard;
    $taxRate = TaxRate::factory()->create([
        'region_id' => $region->id,
        'tax_category' => $taxCategory,
        'rate' => '10.00',
        'min_order_value' => '50.0000', // Minimum order value
        'max_order_value' => null,
        'is_active' => true,
        'is_compound' => false,
    ]);

    $product = Product::factory()->create([
        'tax_category' => $taxCategory,
        'is_tax_exempt' => false,
    ]);

    $order = Order::factory()->create([
        'subtotal' => '30.0000', // Below minimum
        'discount_total' => '0.0000',
        'shipping_total' => '0.0000',
    ]);

    $orderItem = OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'total_price' => '30.0000',
    ]);

    $shippingAddress = OrderAddress::factory()->create([
        'order_id' => $order->id,
        'type' => OrderAddressType::Shipping,
        'country_code' => 'US',
        'state' => 'CA',
        'postal_code' => '90210',
    ]);

    $order->setRelation('items', collect([$orderItem]));
    $order->setRelation('shippingAddress', $shippingAddress);
    $orderItem->setRelation('product', $product);

    $calculator = new OrderTaxCalculator();
    $result = $calculator->calculate(TaxCalculationInput::fromOrder($order));

    // Order doesn't meet minimum value, so no tax
    expect($result->taxTotal)->toBe('0.0000');
    expect($result->taxDetails)->toBeEmpty();
});

test('applies tax rate only when order is below maximum value requirement', function (): void {
    Setting::setValue('prices_include_tax', false);
    Setting::setValue('tax_based_on', 'shipping');

    $region = Region::factory()->create([
        'countries' => ['US'],
        'states' => ['CA'],
        'postal_codes' => ['90210'],
        'is_active' => true,
    ]);

    $taxCategory = TaxCategory::Standard;
    $taxRate = TaxRate::factory()->create([
        'region_id' => $region->id,
        'tax_category' => $taxCategory,
        'rate' => '10.00',
        'min_order_value' => null,
        'max_order_value' => '100.0000', // Maximum order value
        'is_active' => true,
        'is_compound' => false,
    ]);

    $product = Product::factory()->create([
        'tax_category' => $taxCategory,
        'is_tax_exempt' => false,
    ]);

    $order = Order::factory()->create([
        'subtotal' => '150.0000', // Above maximum
        'discount_total' => '0.0000',
        'shipping_total' => '0.0000',
    ]);

    $orderItem = OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'total_price' => '150.0000',
    ]);

    $shippingAddress = OrderAddress::factory()->create([
        'order_id' => $order->id,
        'type' => OrderAddressType::Shipping,
        'country_code' => 'US',
        'state' => 'CA',
        'postal_code' => '90210',
    ]);

    $order->setRelation('items', collect([$orderItem]));
    $order->setRelation('shippingAddress', $shippingAddress);
    $orderItem->setRelation('product', $product);

    $calculator = new OrderTaxCalculator();
    $result = $calculator->calculate(TaxCalculationInput::fromOrder($order));

    // Order exceeds maximum value, so no tax
    expect($result->taxTotal)->toBe('0.0000');
    expect($result->taxDetails)->toBeEmpty();
});

test('applies tax rate when order meets both min and max value requirements', function (): void {
    Setting::setValue('prices_include_tax', false);
    Setting::setValue('tax_based_on', 'shipping');

    $region = Region::factory()->create([
        'countries' => ['US'],
        'states' => ['CA'],
        'postal_codes' => ['90210'],
        'is_active' => true,
    ]);

    $taxCategory = TaxCategory::Standard;
    $taxRate = TaxRate::factory()->create([
        'region_id' => $region->id,
        'tax_category' => $taxCategory,
        'rate' => '10.00',
        'min_order_value' => '50.0000',
        'max_order_value' => '200.0000',
        'is_active' => true,
        'is_compound' => false,
    ]);

    $product = Product::factory()->create([
        'tax_category' => $taxCategory,
        'is_tax_exempt' => false,
    ]);

    $order = Order::factory()->create([
        'subtotal' => '100.0000', // Within range
        'discount_total' => '0.0000',
        'shipping_total' => '0.0000',
    ]);

    $orderItem = OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'total_price' => '100.0000',
    ]);

    $shippingAddress = OrderAddress::factory()->create([
        'order_id' => $order->id,
        'type' => OrderAddressType::Shipping,
        'country_code' => 'US',
        'state' => 'CA',
        'postal_code' => '90210',
    ]);

    $order->setRelation('items', collect([$orderItem]));
    $order->setRelation('shippingAddress', $shippingAddress);
    $orderItem->setRelation('product', $product);

    $calculator = new OrderTaxCalculator();
    $result = $calculator->calculate(TaxCalculationInput::fromOrder($order));

    // Order meets both requirements, so tax applies
    expect($result->taxTotal)->toBe('10.0000');
    expect($result->taxDetails)->toHaveCount(1);
});

test('considers discounted order total for min/max value requirements', function (): void {
    Setting::setValue('prices_include_tax', false);
    Setting::setValue('tax_based_on', 'shipping');

    $region = Region::factory()->create([
        'countries' => ['US'],
        'states' => ['CA'],
        'postal_codes' => ['90210'],
        'is_active' => true,
    ]);

    $taxCategory = TaxCategory::Standard;
    $taxRate = TaxRate::factory()->create([
        'region_id' => $region->id,
        'tax_category' => $taxCategory,
        'rate' => '10.00',
        'min_order_value' => '50.0000',
        'max_order_value' => null,
        'is_active' => true,
        'is_compound' => false,
    ]);

    $product = Product::factory()->create([
        'tax_category' => $taxCategory,
        'is_tax_exempt' => false,
    ]);

    $order = Order::factory()->create([
        'subtotal' => '60.0000',
        'discount_total' => '20.0000', // Discounted total: $40 (below minimum)
        'shipping_total' => '0.0000',
    ]);

    $orderItem = OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'total_price' => '60.0000',
    ]);

    $shippingAddress = OrderAddress::factory()->create([
        'order_id' => $order->id,
        'type' => OrderAddressType::Shipping,
        'country_code' => 'US',
        'state' => 'CA',
        'postal_code' => '90210',
    ]);

    $order->setRelation('items', collect([$orderItem]));
    $order->setRelation('shippingAddress', $shippingAddress);
    $orderItem->setRelation('product', $product);

    $calculator = new OrderTaxCalculator();
    $result = $calculator->calculate(TaxCalculationInput::fromOrder($order));

    // Discounted total ($40) is below minimum ($50), so no tax
    expect($result->taxTotal)->toBe('0.0000');
    expect($result->taxDetails)->toBeEmpty();
});

test('uses default tax rate for shipping when no item tax rates exist', function (): void {
    Setting::setValue('shipping_is_taxable', true);
    Setting::setValue('prices_include_tax', false);
    Setting::setValue('tax_based_on', 'shipping');
    Setting::setValue('default_tax_rate', '8.00');

    $product = Product::factory()->create(['is_tax_exempt' => false]);

    $order = Order::factory()->create([
        'subtotal' => '100.0000',
        'discount_total' => '0.0000',
        'shipping_total' => '20.0000',
    ]);

    $orderItem = OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'total_price' => '100.0000',
    ]);

    // Address that doesn't match any region
    $shippingAddress = OrderAddress::factory()->create([
        'order_id' => $order->id,
        'type' => OrderAddressType::Shipping,
        'country_code' => 'ZZ',
        'state' => 'NOMATCH',
        'postal_code' => 'NOMATCH',
    ]);

    $order->setRelation('items', collect([$orderItem]));
    $order->setRelation('shippingAddress', $shippingAddress);
    $orderItem->setRelation('product', $product);

    $calculator = new OrderTaxCalculator();
    $result = $calculator->calculate(TaxCalculationInput::fromOrder($order));

    // Item tax: $100 * 8% = $8.00
    // Shipping tax: $20 * 8% = $1.60
    // Total: $9.60
    expect($result->taxTotal)->toBe('9.6000');
    expect($result->taxDetails)->toHaveCount(2); // 1 item + 1 shipping
});

// === EDGE CASE TESTS ===

test('handles empty address fields gracefully', function (): void {
    Setting::setValue('prices_include_tax', false);
    Setting::setValue('tax_based_on', 'shipping');
    Setting::setValue('default_tax_rate', '10.00');

    $product = Product::factory()->create(['is_tax_exempt' => false]);
    $order = Order::factory()->create([
        'subtotal' => '100.0000',
        'discount_total' => '0.0000',
        'shipping_total' => '0.0000',
    ]);
    $orderItem = OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'total_price' => '100.0000',
    ]);

    // Address with empty fields
    $emptyAddress = OrderAddress::factory()->create([
        'order_id' => $order->id,
        'type' => OrderAddressType::Shipping,
        'country_code' => '',
        'state' => '',
        'postal_code' => '',
    ]);

    $order->setRelation('items', collect([$orderItem]));
    $order->setRelation('shippingAddress', $emptyAddress);
    $orderItem->setRelation('product', $product);

    $calculator = new OrderTaxCalculator();
    $result = $calculator->calculate(TaxCalculationInput::fromOrder($order));

    // Should fall back to default tax rate
    expect($result->taxTotal)->toBe('10.0000');
});

test('handles inactive regions correctly', function (): void {
    Setting::setValue('prices_include_tax', false);
    Setting::setValue('tax_based_on', 'shipping');
    Setting::setValue('default_tax_rate', '5.00');

    $inactiveRegion = Region::factory()->inactive()->create([
        'countries' => ['US'],
        'states' => ['CA'],
        'postal_codes' => ['90210'],
    ]);

    $taxCategory = TaxCategory::Standard;
    $taxRate = TaxRate::factory()->withoutConditions()->create([
        'region_id' => $inactiveRegion->id,
        'tax_category' => $taxCategory,
        'rate' => '20.00',
        'is_compound' => false,
        'is_active' => true,
    ]);

    $product = Product::factory()->create([
        'tax_category' => $taxCategory,
        'is_tax_exempt' => false,
    ]);

    $order = Order::factory()->create([
        'subtotal' => '100.0000',
        'discount_total' => '0.0000',
        'shipping_total' => '0.0000',
    ]);
    $orderItem = OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'total_price' => '100.0000',
    ]);
    $address = OrderAddress::factory()->create([
        'order_id' => $order->id,
        'type' => OrderAddressType::Shipping,
        'country_code' => 'US',
        'state' => 'CA',
        'postal_code' => '90210',
    ]);

    $order->setRelation('items', collect([$orderItem]));
    $order->setRelation('shippingAddress', $address);
    $orderItem->setRelation('product', $product);

    $calculator = new OrderTaxCalculator();
    $result = $calculator->calculate(TaxCalculationInput::fromOrder($order));

    // Should use default tax rate since region is inactive
    expect($result->taxTotal)->toBe('5.0000');
});

test('handles missing address gracefully', function (): void {
    Setting::setValue('prices_include_tax', false);
    Setting::setValue('tax_based_on', 'shipping');
    Setting::setValue('default_tax_rate', '5.00');

    $product = Product::factory()->create(['is_tax_exempt' => false]);
    $order = Order::factory()->create([
        'subtotal' => '100.0000',
        'discount_total' => '0.0000',
        'shipping_total' => '0.0000',
    ]);
    $orderItem = OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'total_price' => '100.0000',
    ]);

    // No address set
    $order->setRelation('items', collect([$orderItem]));
    $order->setRelation('shippingAddress', null);
    $orderItem->setRelation('product', $product);

    $calculator = new OrderTaxCalculator();
    $result = $calculator->calculate(TaxCalculationInput::fromOrder($order));

    // Should fall back to default tax rate
    expect($result->taxTotal)->toBe('5.0000');
});

test('handles zero-value orders', function (): void {
    Setting::setValue('prices_include_tax', false);
    Setting::setValue('tax_based_on', 'shipping');
    Setting::setValue('default_tax_rate', '10.00');

    $product = Product::factory()->create(['is_tax_exempt' => false]);
    $order = Order::factory()->create([
        'subtotal' => '0.0000',
        'discount_total' => '0.0000',
        'shipping_total' => '0.0000',
    ]);
    $orderItem = OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'total_price' => '0.0000',
    ]);

    $address = OrderAddress::factory()->create([
        'order_id' => $order->id,
        'type' => OrderAddressType::Shipping,
        'country_code' => 'US',
        'state' => 'CA',
        'postal_code' => '90210',
    ]);

    $order->setRelation('items', collect([$orderItem]));
    $order->setRelation('shippingAddress', $address);
    $orderItem->setRelation('product', $product);

    $calculator = new OrderTaxCalculator();
    $result = $calculator->calculate(TaxCalculationInput::fromOrder($order));

    expect($result->taxTotal)->toBe('0.0000');
    expect($result->taxDetails)->toBeEmpty();
});

test('handles precision and rounding correctly', function (): void {
    Setting::setValue('prices_include_tax', false);
    Setting::setValue('tax_based_on', 'shipping');
    Setting::setValue('default_tax_rate', '7.333'); // Unusual tax rate

    $product = Product::factory()->create(['is_tax_exempt' => false]);
    $order = Order::factory()->create([
        'subtotal' => '100.0000',
        'discount_total' => '0.0000',
        'shipping_total' => '0.0000',
    ]);
    $orderItem = OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'total_price' => '100.0000',
    ]);

    $address = OrderAddress::factory()->create([
        'order_id' => $order->id,
        'type' => OrderAddressType::Shipping,
        'country_code' => 'ZZ',
        'state' => 'NOMATCH',
        'postal_code' => 'NOMATCH',
    ]);

    $order->setRelation('items', collect([$orderItem]));
    $order->setRelation('shippingAddress', $address);
    $orderItem->setRelation('product', $product);

    $calculator = new OrderTaxCalculator();
    $result = $calculator->calculate(TaxCalculationInput::fromOrder($order));

    // Should round to 4 decimal places: 100 * 7.333% = 7.3300
    expect($result->taxTotal)->toBe('7.3300');
    expect($result->taxDetails[0]->taxAmount)->toBe('7.3300');
});

test('handles large orders with many items', function (): void {
    Setting::setValue('prices_include_tax', false);
    Setting::setValue('tax_based_on', 'shipping');
    Setting::setValue('default_tax_rate', '8.00');

    $product = Product::factory()->create(['is_tax_exempt' => false]);
    $order = Order::factory()->create([
        'subtotal' => '1000.0000',
        'discount_total' => '0.0000',
        'shipping_total' => '0.0000',
    ]);

    // Create 10 items
    $items = [];
    for ($i = 0; $i < 10; $i++) {
        $items[] = OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'total_price' => '100.0000',
        ]);
    }

    $address = OrderAddress::factory()->create([
        'order_id' => $order->id,
        'type' => OrderAddressType::Shipping,
        'country_code' => 'ZZ',
        'state' => 'NOMATCH',
        'postal_code' => 'NOMATCH',
    ]);

    $order->setRelation('items', collect($items));
    $order->setRelation('shippingAddress', $address);
    foreach ($items as $item) {
        $item->setRelation('product', $product);
    }

    $calculator = new OrderTaxCalculator();
    $result = $calculator->calculate(TaxCalculationInput::fromOrder($order));

    // Should calculate tax for all items: 1000 * 8% = 80
    expect($result->taxTotal)->toBe('80.0000');
    expect($result->taxDetails)->toHaveCount(10); // One tax detail per item
});

test('does not calculate shipping tax when no matching item tax rates', function (): void {
    Setting::setValue('shipping_is_taxable', true);
    Setting::setValue('prices_include_tax', false);
    Setting::setValue('tax_based_on', 'shipping');
    Setting::setValue('default_tax_rate', null);

    $region = Region::factory()->create([
        'countries' => ['US'],
        'states' => null,
        'postal_codes' => null,
        'is_active' => true,
    ]);

    TaxRate::factory()->inactive()->create([
        'region_id' => $region->id,
        'rate' => '5.00',
    ]);

    $product = Product::factory()->create(['is_tax_exempt' => false]);

    $order = Order::factory()->create([
        'subtotal' => '100.0000',
        'discount_total' => '0.0000',
        'shipping_total' => '20.0000',
    ]);

    $orderItem = OrderItem::factory()->create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'total_price' => '100.0000',
    ]);

    $address = OrderAddress::factory()->create([
        'order_id' => $order->id,
        'type' => OrderAddressType::Shipping,
        'country_code' => 'US',
        'state' => 'CA',
        'postal_code' => '90210',
    ]);

    $order->setRelation('items', collect([$orderItem]));
    $order->setRelation('shippingAddress', $address);
    $orderItem->setRelation('product', $product);

    $calculator = new OrderTaxCalculator();
    $result = $calculator->calculate(TaxCalculationInput::fromOrder($order));

    expect($result->taxTotal)->toBe('0.0000');
    expect($result->taxDetails)->toBeEmpty();
});

test('calculates tax from hydrated items via fromHydratedItems', function (): void {
    Setting::setValue('shipping_is_taxable', true);
    Setting::setValue('prices_include_tax', false);
    Setting::setValue('tax_based_on', 'shipping');

    $region = Region::factory()->create([
        'countries' => ['US'],
        'states' => ['CA'],
        'postal_codes' => ['90210'],
        'is_active' => true,
    ]);

    TaxRate::factory()->withoutConditions()->create([
        'region_id' => $region->id,
        'tax_category' => null,
        'rate' => '10.00',
        'is_active' => true,
        'is_compound' => false,
    ]);

    $product = Product::factory()->create([
        'is_tax_exempt' => false,
    ]);

    $hydratedItems = [
        makeHydratedItem($product, '100.0000'),
        makeHydratedItem($product, '50.0000'),
    ];

    $input = TaxCalculationInput::fromHydratedItems(
        hydratedItems: $hydratedItems,
        subtotal: '150.0000',
        shippingTotal: '10.0000',
        discountTotal: '0.0000',
        shippingAddress: new AddressLocation(countryCode: 'US', state: 'CA', postalCode: '90210'),
        billingAddress: new AddressLocation(countryCode: 'US', state: 'CA', postalCode: '90210'),
    );

    $calculator = new OrderTaxCalculator();
    $result = $calculator->calculate($input);

    // Items: ($100 + $50) * 10% = $15.00
    // Shipping: $10 * 10% = $1.00
    // Total: $16.00
    expect($result->taxTotal)->toBe('16.0000');
});

test('fromHydratedItems applies discount ratio correctly', function (): void {
    Setting::setValue('shipping_is_taxable', false);
    Setting::setValue('prices_include_tax', false);
    Setting::setValue('tax_based_on', 'shipping');

    $region = Region::factory()->create([
        'countries' => ['US'],
        'states' => ['NY'],
        'postal_codes' => [],
        'is_active' => true,
    ]);

    TaxRate::factory()->withoutConditions()->create([
        'region_id' => $region->id,
        'tax_category' => null,
        'rate' => '8.00',
        'is_active' => true,
        'is_compound' => false,
    ]);

    $product = Product::factory()->create([
        'is_tax_exempt' => false,
    ]);

    $input = TaxCalculationInput::fromHydratedItems(
        hydratedItems: [makeHydratedItem($product, '200.0000')],
        subtotal: '200.0000',
        shippingTotal: '0.0000',
        discountTotal: '20.0000',
        shippingAddress: new AddressLocation(countryCode: 'US', state: 'NY', postalCode: '10001'),
        billingAddress: new AddressLocation(countryCode: 'US', state: 'NY', postalCode: '10001'),
    );

    $calculator = new OrderTaxCalculator();
    $result = $calculator->calculate($input);

    // Discount ratio: $20/$200 = 0.10
    // Discounted item: $200 - ($200 * 0.10) = $180
    // Tax: $180 * 8% = $14.40
    expect($result->taxTotal)->toBe('14.4000');
});

test('fromHydratedItems returns zero when prices include tax', function (): void {
    Setting::setValue('prices_include_tax', true);

    $product = Product::factory()->create();

    $input = TaxCalculationInput::fromHydratedItems(
        hydratedItems: [makeHydratedItem($product, '100.0000')],
        subtotal: '100.0000',
        shippingTotal: '5.0000',
        discountTotal: '0.0000',
        shippingAddress: new AddressLocation(countryCode: 'US', state: 'CA', postalCode: '90210'),
        billingAddress: new AddressLocation(countryCode: 'US', state: 'CA', postalCode: '90210'),
    );

    $calculator = new OrderTaxCalculator();
    $result = $calculator->calculate($input);

    expect($result->taxTotal)->toBe('0.0000');
});

test('fromHydratedItems uses billing address when tax is based on billing', function (): void {
    Setting::setValue('shipping_is_taxable', false);
    Setting::setValue('prices_include_tax', false);
    Setting::setValue('tax_based_on', 'billing');

    $region = Region::factory()->create([
        'countries' => ['CA'],
        'states' => ['ON'],
        'postal_codes' => [],
        'is_active' => true,
    ]);

    TaxRate::factory()->withoutConditions()->create([
        'region_id' => $region->id,
        'tax_category' => null,
        'rate' => '13.00',
        'is_active' => true,
        'is_compound' => false,
    ]);

    $product = Product::factory()->create([
        'is_tax_exempt' => false,
    ]);

    // Shipping address is US but billing is CA/ON — tax should use billing
    $input = TaxCalculationInput::fromHydratedItems(
        hydratedItems: [makeHydratedItem($product, '100.0000')],
        subtotal: '100.0000',
        shippingTotal: '0.0000',
        discountTotal: '0.0000',
        shippingAddress: new AddressLocation(countryCode: 'US', state: 'CA', postalCode: '90210'),
        billingAddress: new AddressLocation(countryCode: 'CA', state: 'ON', postalCode: 'M5V 2T6'),
    );

    $calculator = new OrderTaxCalculator();
    $result = $calculator->calculate($input);

    // $100 * 13% = $13.00
    expect($result->taxTotal)->toBe('13.0000');
});

test('fromHydratedItems handles empty addresses gracefully', function (): void {
    Setting::setValue('prices_include_tax', false);
    Setting::setValue('tax_based_on', 'shipping');
    Setting::setValue('default_tax_rate', '5.00');

    $product = Product::factory()->create([
        'is_tax_exempt' => false,
    ]);

    $input = TaxCalculationInput::fromHydratedItems(
        hydratedItems: [makeHydratedItem($product, '100.0000')],
        subtotal: '100.0000',
        shippingTotal: '0.0000',
        discountTotal: '0.0000',
        shippingAddress: null,
        billingAddress: null,
    );

    $calculator = new OrderTaxCalculator();
    $result = $calculator->calculate($input);

    expect($result->taxTotal)->toBe('5.0000');
    expect($result->taxDetails)->toHaveCount(1);
    expect($result->taxDetails[0]->taxName)->toBe([app()->getLocale() => 'Default Tax']);
});

test('fromHydratedItems skips tax-exempt products', function (): void {
    Setting::setValue('prices_include_tax', false);
    Setting::setValue('tax_based_on', 'shipping');
    Setting::setValue('default_tax_rate', '10.00');

    $taxExemptProduct = Product::factory()->create([
        'is_tax_exempt' => true,
    ]);

    $taxableProduct = Product::factory()->create([
        'is_tax_exempt' => false,
    ]);

    $input = TaxCalculationInput::fromHydratedItems(
        hydratedItems: [
            makeHydratedItem($taxExemptProduct, '100.0000'),
            makeHydratedItem($taxableProduct, '50.0000'),
        ],
        subtotal: '150.0000',
        shippingTotal: '0.0000',
        discountTotal: '0.0000',
        shippingAddress: null,
        billingAddress: null,
    );

    $calculator = new OrderTaxCalculator();
    $result = $calculator->calculate($input);

    // Only the taxable product ($50) should be taxed at 10%
    expect($result->taxTotal)->toBe('5.0000');
    expect($result->taxDetails)->toHaveCount(1);
});
