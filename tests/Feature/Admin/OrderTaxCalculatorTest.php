<?php

declare(strict_types=1);

use App\Enums\TaxCategory;
use App\Http\Controllers\Admin\OrderTaxCalculatorController;
use App\Http\Requests\Admin\CalculateOrderTaxRequest;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Region;
use App\Models\Setting;
use App\Models\TaxRate;
use App\Utilities\OrderTaxCalculator;

use function Pest\Laravel\postJson;

covers([
    OrderTaxCalculatorController::class,
    CalculateOrderTaxRequest::class,
    OrderTaxCalculator::class,
]);

uses()->group('order');

test('calculates taxes for basic order without discount or shipping', function () {
    Setting::setValue('prices_include_tax', false);
    Setting::setValue('tax_based_on', 'shipping');

    $region = Region::factory()->create([
        'countries' => ['US'],
        'states' => ['NY'],
        'postal_codes' => ['10001'],
        'is_active' => true,
    ]);
    $taxCategory = TaxCategory::Standard->value;
    $taxRate = TaxRate::factory()->withoutConditions()->create([
        'region_id' => $region->id,
        'tax_category' => $taxCategory,
        'name' => 'VAT',
        'rate' => '10.00',
        'is_compound' => false,
        'is_active' => true,
    ]);

    $product = Product::factory()->create([
        'tax_category' => $taxCategory,
        'price' => '100.0000',
        'is_tax_exempt' => false,
    ]);

    $requestData = [
        'items' => [
            [
                'product_id' => $product->id,
                'quantity' => 1,
                'unit_price' => '100.0000',
            ],
        ],
        'discount_total' => '0.0000',
        'shipping_total' => '0.0000',
        'shipping_address' => [
            'state' => 'NY',
            'postal_code' => '10001',
            'country_code' => 'US',
        ],
    ];

    $response = actingAsSuperAdmin()->postJson(route('admin.orders.calculate-taxes'), $requestData);

    $response->assertOk()
        ->assertJson([
            'tax_total' => '10.0000',
        ]);

    $taxDetails = $response->json('tax_details');
    expect($taxDetails)->toHaveCount(1);
    expect($taxDetails[0]['tax_rate'])->toBe('10.00');
});

test('calculates taxes considering order-level discount', function () {
    Setting::setValue('prices_include_tax', false);
    Setting::setValue('tax_based_on', 'shipping');

    $region = Region::factory()->create([
        'countries' => ['US'],
        'states' => ['CA'],
        'postal_codes' => ['90210'],
        'is_active' => true,
    ]);
    $taxCategory = TaxCategory::Standard->value;
    $taxRate = TaxRate::factory()->withoutConditions()->create([
        'region_id' => $region->id,
        'tax_category' => $taxCategory,
        'name' => 'VAT',
        'rate' => '10.00',
        'is_compound' => false,
        'is_active' => true,
    ]);

    $product = Product::factory()->create([
        'tax_category' => $taxCategory,
        'price' => '100.0000',
        'is_tax_exempt' => false,
    ]);

    $requestData = [
        'items' => [
            [
                'product_id' => $product->id,
                'quantity' => 1,
                'unit_price' => '100.0000',
            ],
        ],
        'discount_total' => '20.0000', // 20% discount
        'shipping_total' => '0.0000',
        'shipping_address' => [
            'state' => 'CA',
            'postal_code' => '90210',
            'country_code' => 'US',
        ],
    ];

    $response = actingAsSuperAdmin()->postJson(route('admin.orders.calculate-taxes'), $requestData);

    $response->assertOk()
        ->assertJson([
            'tax_total' => '8.0000', // Tax on $80 (after discount): $80 * 10% = $8
        ]);
});

test('calculates taxes for multiple items with proportional discount', function () {
    Setting::setValue('prices_include_tax', false);
    Setting::setValue('tax_based_on', 'shipping');

    $region = Region::factory()->create([
        'countries' => ['CA'],
        'states' => ['ON'],
        'postal_codes' => ['M5V 3A8'],
        'is_active' => true,
    ]);
    $taxCategory = TaxCategory::Standard->value;
    $taxRate = TaxRate::factory()->withoutConditions()->create([
        'region_id' => $region->id,
        'tax_category' => $taxCategory,
        'name' => 'VAT',
        'rate' => '13.00',
        'is_compound' => false,
        'is_active' => true,
    ]);

    $product1 = Product::factory()->create([
        'tax_category' => $taxCategory,
        'price' => '100.0000',
        'is_tax_exempt' => false,
    ]);

    $product2 = Product::factory()->create([
        'tax_category' => $taxCategory,
        'price' => '50.0000',
        'is_tax_exempt' => false,
    ]);

    $requestData = [
        'items' => [
            [
                'product_id' => $product1->id,
                'quantity' => 1,
                'unit_price' => '100.0000',
            ],
            [
                'product_id' => $product2->id,
                'quantity' => 1,
                'unit_price' => '50.0000',
            ],
        ],
        'discount_total' => '30.0000', // 20% discount
        'shipping_total' => '0.0000',
        'shipping_address' => [
            'state' => 'ON',
            'postal_code' => 'M5V 3A8',
            'country_code' => 'CA',
        ],
    ];

    $response = actingAsSuperAdmin()->postJson(route('admin.orders.calculate-taxes'), $requestData);

    $response->assertOk()
        ->assertJson([
            'tax_total' => '15.6000', // Tax on $120 (after discount): $120 * 13% = $15.60
            'tax_details' => [],
        ]);
});

test('calculates shipping tax when shipping is taxable using item tax rates', function () {
    Setting::setValue('prices_include_tax', false);
    Setting::setValue('shipping_is_taxable', true);
    Setting::setValue('tax_based_on', 'shipping');

    $region = Region::factory()->create([
        'countries' => ['GB'],
        'states' => ['England'],
        'postal_codes' => ['SW1A 2AA'],
        'is_active' => true,
    ]);
    $taxCategory = TaxCategory::Standard->value;
    $taxRate = TaxRate::factory()->withoutConditions()->create([
        'region_id' => $region->id,
        'tax_category' => $taxCategory,
        'name' => 'VAT',
        'rate' => '20.00',
        'is_compound' => false,
        'is_active' => true,
    ]);

    $product = Product::factory()->create([
        'tax_category' => $taxCategory,
        'price' => '100.0000',
        'is_tax_exempt' => false,
    ]);

    $requestData = [
        'items' => [
            [
                'product_id' => $product->id,
                'quantity' => 1,
                'unit_price' => '100.0000',
            ],
        ],
        'discount_total' => '0.0000',
        'shipping_total' => '10.0000',
        'shipping_address' => [
            'state' => 'England',
            'postal_code' => 'SW1A 2AA',
            'country_code' => 'GB',
        ],
    ];

    $response = actingAsSuperAdmin()->postJson(route('admin.orders.calculate-taxes'), $requestData);

    $response->assertOk()
        ->assertJson([
            'tax_total' => '22.0000', // Product: $20, Shipping: $2
        ]);

    $taxDetails = $response->json('tax_details');
    expect($taxDetails)->toHaveCount(1);
    expect($taxDetails[0]['tax_rate'])->toBe('20.00');
    expect($taxDetails[0]['tax_amount'])->toBe('22.0000');
});

test('does not calculate shipping tax when shipping is not taxable', function () {
    Setting::setValue('prices_include_tax', false);
    Setting::setValue('shipping_is_taxable', false);
    Setting::setValue('tax_based_on', 'shipping');

    $region = Region::factory()->create([
        'countries' => ['US'],
        'states' => ['TX'],
        'postal_codes' => ['75201'],
        'is_active' => true,
    ]);
    $taxCategory = TaxCategory::Standard->value;
    $taxRate = TaxRate::factory()->withoutConditions()->create([
        'region_id' => $region->id,
        'tax_category' => $taxCategory,
        'name' => 'Sales Tax',
        'rate' => '8.25',
        'is_compound' => false,
        'is_active' => true,
    ]);

    $product = Product::factory()->create([
        'tax_category' => $taxCategory,
        'price' => '100.0000',
        'is_tax_exempt' => false,
    ]);

    $requestData = [
        'items' => [
            [
                'product_id' => $product->id,
                'quantity' => 1,
                'unit_price' => '100.0000',
            ],
        ],
        'discount_total' => '0.0000',
        'shipping_total' => '10.0000',
        'shipping_address' => [
            'state' => 'TX',
            'postal_code' => '75201',
            'country_code' => 'US',
        ],
    ];

    $response = actingAsSuperAdmin()->postJson(route('admin.orders.calculate-taxes'), $requestData);

    $response->assertOk()
        ->assertJson([
            'tax_total' => '8.2500', // Only product tax, no shipping tax
        ]);

    $taxDetails = $response->json('tax_details');
    expect($taxDetails)->toHaveCount(1);
    expect($taxDetails[0]['tax_rate'])->toBe('8.25');
});

test('applies tax rate only when order meets minimum value requirement', function () {
    Setting::setValue('prices_include_tax', false);
    Setting::setValue('tax_based_on', 'shipping');

    $region = Region::factory()->create([
        'countries' => ['US'],
        'states' => ['FL'],
        'postal_codes' => ['33101'],
        'is_active' => true,
    ]);
    $taxCategory = TaxCategory::Standard->value;
    $taxRate = TaxRate::factory()->withoutConditions()->create([
        'region_id' => $region->id,
        'tax_category' => $taxCategory,
        'name' => 'Sales Tax',
        'rate' => '6.00',
        'min_order_value' => '50.0000',
        'is_compound' => false,
        'is_active' => true,
    ]);

    $product = Product::factory()->create([
        'tax_category' => $taxCategory,
        'price' => '30.0000',
        'is_tax_exempt' => false,
    ]);

    $requestData = [
        'items' => [
            [
                'product_id' => $product->id,
                'quantity' => 1,
                'unit_price' => '30.0000',
            ],
        ],
        'discount_total' => '0.0000',
        'shipping_total' => '0.0000',
        'shipping_address' => [
            'state' => 'FL',
            'postal_code' => '33101',
            'country_code' => 'US',
        ],
    ];

    $response = actingAsSuperAdmin()->postJson(route('admin.orders.calculate-taxes'), $requestData);

    $response->assertOk()
        ->assertJson([
            'tax_total' => '0.0000', // Below minimum order value
        ]);
});

test('skips tax-exempt products', function () {
    Setting::setValue('prices_include_tax', false);
    Setting::setValue('tax_based_on', 'shipping');

    $region = Region::factory()->create([
        'countries' => ['US'],
        'states' => ['WA'],
        'postal_codes' => ['98101'],
        'is_active' => true,
    ]);
    $taxCategory = TaxCategory::Standard->value;
    $taxRate = TaxRate::factory()->withoutConditions()->create([
        'region_id' => $region->id,
        'tax_category' => $taxCategory,
        'name' => 'Sales Tax',
        'rate' => '10.25',
        'is_compound' => false,
        'is_active' => true,
    ]);

    $product = Product::factory()->create([
        'tax_category' => $taxCategory,
        'price' => '100.0000',
        'is_tax_exempt' => true, // Tax-exempt product
    ]);

    $requestData = [
        'items' => [
            [
                'product_id' => $product->id,
                'quantity' => 1,
                'unit_price' => '100.0000',
            ],
        ],
        'discount_total' => '0.0000',
        'shipping_total' => '0.0000',
        'shipping_address' => [
            'state' => 'WA',
            'postal_code' => '98101',
            'country_code' => 'US',
        ],
    ];

    $response = actingAsSuperAdmin()->postJson(route('admin.orders.calculate-taxes'), $requestData);

    $response->assertOk()
        ->assertJson([
            'tax_total' => '0.0000',
        ]);

    expect($response->json('tax_details'))->toBeEmpty();
});

test('calculates compound taxes correctly', function () {
    Setting::setValue('prices_include_tax', false);
    Setting::setValue('tax_based_on', 'shipping');

    $region = Region::factory()->create([
        'countries' => ['US'],
        'states' => ['CA'],
        'postal_codes' => ['90210'],
        'is_active' => true,
    ]);
    $taxCategory = TaxCategory::Standard->value;

    $primaryTaxRate = TaxRate::factory()->withoutConditions()->create([
        'region_id' => $region->id,
        'tax_category' => $taxCategory,
        'name' => 'Primary Tax',
        'rate' => '10.00',
        'is_compound' => false,
        'is_active' => true,
        'priority' => 1,
    ]);

    $compoundTaxRate = TaxRate::factory()->withoutConditions()->create([
        'region_id' => $region->id,
        'tax_category' => $taxCategory,
        'name' => 'Compound Tax',
        'rate' => '5.00',
        'is_compound' => true,
        'is_active' => true,
        'priority' => 2,
    ]);

    $product = Product::factory()->create([
        'tax_category' => $taxCategory,
        'price' => '100.0000',
        'is_tax_exempt' => false,
    ]);

    $requestData = [
        'items' => [
            [
                'product_id' => $product->id,
                'quantity' => 1,
                'unit_price' => '100.0000',
            ],
        ],
        'discount_total' => '0.0000',
        'shipping_total' => '0.0000',
        'shipping_address' => [
            'state' => 'CA',
            'postal_code' => '90210',
            'country_code' => 'US',
        ],
    ];

    $response = actingAsSuperAdmin()->postJson(route('admin.orders.calculate-taxes'), $requestData);

    $response->assertOk()
        ->assertJson([
            'tax_total' => '15.5000', // Primary: 10.00, Compound: (100 + 10) * 0.05 = 5.50
        ]);

    $taxDetails = $response->json('tax_details');
    expect($taxDetails)->toHaveCount(2);
    expect($taxDetails[0]['tax_amount'])->toBe('10.0000');
    expect($taxDetails[1]['tax_amount'])->toBe('5.5000');
});

test('returns zero tax when no region matches address', function () {
    Setting::setValue('prices_include_tax', false);
    Setting::setValue('tax_based_on', 'shipping');
    Setting::setValue('default_tax_rate', null);

    $product = Product::factory()->create([
        'price' => '100.0000',
        'is_tax_exempt' => false,
    ]);

    $requestData = [
        'items' => [
            [
                'product_id' => $product->id,
                'quantity' => 1,
                'unit_price' => '100.0000',
            ],
        ],
        'discount_total' => '0.0000',
        'shipping_total' => '0.0000',
        'shipping_address' => [
            'state' => 'Unknown',
            'postal_code' => '00000',
            'country_code' => 'XX',
        ],
    ];

    $response = actingAsSuperAdmin()->postJson(route('admin.orders.calculate-taxes'), $requestData);

    $response->assertOk()
        ->assertJson([
            'tax_total' => '0.0000',
        ]);
});

test('validates required fields', function () {
    $response = actingAsSuperAdmin()->postJson(route('admin.orders.calculate-taxes'), []);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['items']);
});

test('validates item structure', function () {
    $response = actingAsSuperAdmin()->postJson(route('admin.orders.calculate-taxes'), [
        'items' => [
            [
                // Missing required product_id
                'quantity' => 1,
                'unit_price' => '100.0000',
            ],
        ],
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['items.0.product_id']);
});

test('validates address structure', function () {
    $product = Product::factory()->create();

    $response = actingAsSuperAdmin()->postJson(route('admin.orders.calculate-taxes'), [
        'items' => [
            [
                'product_id' => $product->id,
                'quantity' => 1,
                'unit_price' => '100.0000',
            ],
        ],
        'shipping_address' => [
            // Missing required fields
            'state' => 'CA',
            // postal_code and country_code missing
        ],
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors([
            'shipping_address.postal_code',
            'shipping_address.country_code',
        ]);
});

test('calculates taxes correctly when prices include tax', function () {
    Setting::setValue('prices_include_tax', true);

    $product = Product::factory()->create([
        'price' => '120.0000',
        'is_tax_exempt' => false,
    ]);

    $requestData = [
        'items' => [
            [
                'product_id' => $product->id,
                'quantity' => 1,
                'unit_price' => '120.0000',
            ],
        ],
        'discount_total' => '0.0000',
        'shipping_total' => '0.0000',
    ];

    $response = actingAsSuperAdmin()->postJson(route('admin.orders.calculate-taxes'), $requestData);

    $response->assertOk()
        ->assertJson([
            'tax_total' => '0.0000', // When prices include tax, no additional tax calculation is needed
        ]);

    // When prices include tax, tax_details are empty
    expect($response->json('tax_details'))->toBe([]);
});

test('uses default tax rate when no specific tax rates match', function () {
    Setting::setValue('prices_include_tax', false);
    Setting::setValue('default_tax_rate', '18.00');
    Setting::setValue('tax_based_on', 'shipping');

    $product = Product::factory()->create([
        'tax_category' => null,
        'price' => '100.0000',
        'is_tax_exempt' => false,
    ]);

    $requestData = [
        'items' => [
            [
                'product_id' => $product->id,
                'quantity' => 1,
                'unit_price' => '100.0000',
            ],
        ],
        'discount_total' => '0.0000',
        'shipping_total' => '0.0000',
        'shipping_address' => [
            'state' => 'Unknown',
            'postal_code' => '00000',
            'country_code' => 'XX',
        ],
    ];

    $response = actingAsSuperAdmin()->postJson(route('admin.orders.calculate-taxes'), $requestData);

    $response->assertOk()
        ->assertJson([
            'tax_total' => '18.0000', // 100 * 18% = 18
        ]);

    $taxDetails = $response->json('tax_details');
    expect($taxDetails)->toHaveCount(1);
    expect($taxDetails[0])->toMatchArray([
        'tax_rate' => '18.00',
        'tax_name' => castAsTranslatableArray('Default Tax'),
        'taxable_amount' => '100.0000',
        'tax_amount' => '18.0000',
    ]);
});

test('uses product price when unit_price is not provided', function () {
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
        'rate' => '10.00',
        'is_active' => true,
        'is_compound' => false,
    ]);

    $product = Product::factory()->create([
        'price' => '80.0000',
        'is_tax_exempt' => false,
    ]);

    $response = actingAsSuperAdmin()->postJson(route('admin.orders.calculate-taxes'), [
        'items' => [
            [
                'product_id' => $product->id,
                'quantity' => 1,
                // unit_price omitted — should fall back to product price
            ],
        ],
        'discount_total' => '0.0000',
        'shipping_total' => '0.0000',
        'shipping_address' => [
            'state' => 'NY',
            'postal_code' => '10001',
            'country_code' => 'US',
        ],
    ]);

    $response->assertOk()
        ->assertJson(['tax_total' => '8.0000']); // 80 * 10% = 8
});

test('uses variant price when unit_price is not provided and variant exists', function () {
    Setting::setValue('prices_include_tax', false);
    Setting::setValue('tax_based_on', 'shipping');

    $region = Region::factory()->create([
        'countries' => ['US'],
        'states' => ['CA'],
        'postal_codes' => [],
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
        'price' => '100.0000',
        'is_tax_exempt' => false,
    ]);

    $variant = ProductVariant::factory()->for($product)->create(['price' => '60.0000']);

    $response = actingAsSuperAdmin()->postJson(route('admin.orders.calculate-taxes'), [
        'items' => [
            [
                'product_id' => $product->id,
                'product_variant_id' => $variant->id,
                'quantity' => 1,
                // unit_price omitted — should fall back to variant price (60), not product price (100)
            ],
        ],
        'discount_total' => '0.0000',
        'shipping_total' => '0.0000',
        'shipping_address' => [
            'state' => 'CA',
            'postal_code' => '90210',
            'country_code' => 'US',
        ],
    ]);

    $response->assertOk()
        ->assertJson(['tax_total' => '6.0000']); // 60 * 10% = 6
});

test('computes subtotal from items when not provided', function () {
    Setting::setValue('prices_include_tax', false);
    Setting::setValue('tax_based_on', 'shipping');

    $region = Region::factory()->create([
        'countries' => ['US'],
        'states' => ['TX'],
        'postal_codes' => [],
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
        'price' => '50.0000',
        'is_tax_exempt' => false,
    ]);

    $response = actingAsSuperAdmin()->postJson(route('admin.orders.calculate-taxes'), [
        'items' => [
            [
                'product_id' => $product->id,
                'quantity' => 3,
                'unit_price' => '50.0000',
            ],
        ],
        // subtotal omitted — should be auto-computed as 3 * 50 = 150
        'discount_total' => '0.0000',
        'shipping_total' => '0.0000',
        'shipping_address' => [
            'state' => 'TX',
            'postal_code' => '75201',
            'country_code' => 'US',
        ],
    ]);

    $response->assertOk()
        ->assertJson(['tax_total' => '15.0000']); // 150 * 10% = 15
});

test('requires authentication', function () {
    $product = Product::factory()->create([
        'price' => '100.0000',
    ]);

    $requestData = [
        'items' => [
            [
                'product_id' => $product->id,
                'quantity' => 1,
                'unit_price' => '100.0000',
            ],
        ],
        'discount_total' => '0.0000',
        'shipping_total' => '0.0000',
    ];

    $response = postJson(route('admin.orders.calculate-taxes'), $requestData);

    $response->assertStatus(401);
});

test('uses order snapshotted tax settings when order_id is provided', function () {
    // Global settings: prices include tax (zero tax)
    Setting::setValue('prices_include_tax', true);
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
        'rate' => '10.0000',
        'is_active' => true,
        'is_compound' => false,
    ]);

    $product = Product::factory()->create([
        'price' => '100.0000',
        'is_tax_exempt' => false,
    ]);

    // Order was created when prices_include_tax was false
    $order = Order::factory()->create([
        'prices_include_tax' => false,
        'shipping_is_taxable' => false,
        'tax_based_on' => 'shipping',
    ]);

    $response = actingAsSuperAdmin()->postJson(route('admin.orders.calculate-taxes'), [
        'order_id' => $order->id,
        'items' => [
            [
                'product_id' => $product->id,
                'quantity' => 1,
                'unit_price' => '100.0000',
            ],
        ],
        'shipping_address' => [
            'state' => 'NY',
            'postal_code' => '10001',
            'country_code' => 'US',
        ],
    ]);

    $response->assertOk();

    // Should use order's prices_include_tax=false, not global true
    expect($response->json('tax_total'))->toBe('10.0000');
});

test('validates order_id must reference existing order', function () {
    $product = Product::factory()->create();

    $response = actingAsSuperAdmin()->postJson(route('admin.orders.calculate-taxes'), [
        'order_id' => 999999,
        'items' => [
            [
                'product_id' => $product->id,
                'quantity' => 1,
            ],
        ],
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['order_id']);
});
