<?php

declare(strict_types=1);

use App\DTOs\HydratedOrderItem;
use App\Enums\WeightUnit;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Utilities\OrderUtility;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

covers(OrderUtility::class, HydratedOrderItem::class);

uses()->group('utilities', 'order');

test('hydrates basic item with product only', function (): void {
    $product = Product::factory()->create([
        'price' => '10.5000',
        'sku' => 'PROD-001',
        'weight' => '1.5',
        'weight_unit' => WeightUnit::Kg,
    ]);

    $itemsData = [
        [
            'product_id' => $product->id,
            'quantity' => 2,
        ],
    ];

    $utility = app(OrderUtility::class);
    $result = $utility->hydrateItems($itemsData);

    expect($result)->toHaveCount(1);
    $item = $result[0];
    expect($item)->toBeInstanceOf(HydratedOrderItem::class)
        ->and($item->unitPrice)->toBe('10.5000')
        ->and($item->totalPrice)->toBe('21.0000')
        ->and($item->quantity)->toBe(2)
        ->and($item->productSku)->toBe('PROD-001')
        ->and($item->requiresShipping)->toBeTrue()
        ->and($item->weight)->toBe('1.50')
        ->and($item->weightUnit)->toBe(WeightUnit::Kg)
        ->and($item->product)->toBeInstanceOf(Product::class)
        ->and($item->variant)->toBeNull();
});

test('hydrates item with product variant', function (): void {
    $product = Product::factory()->create([
        'price' => '10.0000',
        'sku' => 'PROD-001',
    ]);

    $variant = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'price' => '15.0000',
        'sku' => 'VAR-001',
        'title' => 'Large',
        'weight' => '2.0',
        'weight_unit' => WeightUnit::Kg,
    ]);

    $itemsData = [
        [
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'quantity' => 3,
            'variant_options' => ['size' => 'Large'],
        ],
    ];

    $utility = app(OrderUtility::class);
    $result = $utility->hydrateItems($itemsData);

    expect($result)->toHaveCount(1);
    $item = $result[0];
    expect($item)->toBeInstanceOf(HydratedOrderItem::class)
        ->and($item->unitPrice)->toBe('15.0000')
        ->and($item->totalPrice)->toBe('45.0000')
        ->and($item->quantity)->toBe(3)
        ->and($item->productSku)->toBe('VAR-001')
        ->and($item->variantTitle)->toBe('Large')
        ->and($item->variantOptions)->toBe(['size' => 'Large'])
        ->and($item->requiresShipping)->toBeTrue()
        ->and($item->weight)->toBe('2.00')
        ->and($item->weightUnit)->toBe(WeightUnit::Kg)
        ->and($item->product)->toBeInstanceOf(Product::class)
        ->and($item->variant)->toBeInstanceOf(ProductVariant::class);
});

test('throws exception when product not found', function (): void {
    $itemsData = [
        [
            'product_id' => 999999,
            'quantity' => 1,
        ],
    ];

    $utility = app(OrderUtility::class);
    $utility->hydrateItems($itemsData);
})->throws(ModelNotFoundException::class);

test('batch loads products to avoid N+1 queries', function (): void {
    $products = Product::factory()->count(5)->create();

    $itemsData = [];
    foreach ($products as $product) {
        $itemsData[] = [
            'product_id' => $product->id,
            'quantity' => 1,
        ];
    }

    DB::enableQueryLog();
    $utility = app(OrderUtility::class);
    $utility->hydrateItems($itemsData);
    $queries = DB::getQueryLog();
    DB::disableQueryLog();

    $productQueries = collect($queries)->filter(
        fn ($query) => preg_match('/select \* from [`"]products[`"]/', $query['query']) === 1
    );

    expect($productQueries)->toHaveCount(1);
});

test('calculates order total correctly', function (): void {
    $utility = app(OrderUtility::class);
    $total = $utility->calculateOrderTotal(
        subtotal: '100.0000',
        taxTotal: '10.0000',
        shippingTotal: '15.0000',
        discountTotal: '25.0000'
    );

    expect($total)->toBe('100.0000'); // 100 + 10 + 15 - 25 = 100
});

test('calculates order total with BigDecimal precision', function (): void {
    $utility = app(OrderUtility::class);
    $total = $utility->calculateOrderTotal(
        subtotal: '29.9700',
        taxTotal: '2.4000',
        shippingTotal: '5.9900',
        discountTotal: '10.0000'
    );

    expect($total)->toBe('28.3600');
});

test('handles zero discount in order total', function (): void {
    $utility = app(OrderUtility::class);
    $total = $utility->calculateOrderTotal(
        subtotal: '100.0000',
        taxTotal: '10.0000',
        shippingTotal: '15.0000',
        discountTotal: '0.0000'
    );

    expect($total)->toBe('125.0000');
});

test('calculateSubtotal sums totals from hydrated items', function (): void {
    $product = Product::factory()->create(['price' => '10.0000']);

    $items = [
        new HydratedOrderItem(
            product: $product,
            variant: null,
            unitPrice: '10.0000',
            totalPrice: '20.0000',
            quantity: 2,
            variantOptions: null,
            productTitle: ['en' => 'A'],
            productSku: 'A',
            variantTitle: null,
            requiresShipping: false,
            weight: null,
            weightUnit: null,
        ),
        new HydratedOrderItem(
            product: $product,
            variant: null,
            unitPrice: '5.0000',
            totalPrice: '15.0000',
            quantity: 3,
            variantOptions: null,
            productTitle: ['en' => 'B'],
            productSku: 'B',
            variantTitle: null,
            requiresShipping: false,
            weight: null,
            weightUnit: null,
        ),
    ];

    expect(OrderUtility::calculateSubtotal($items))->toBe('35.0000');
});

test('calculateSubtotal sums totals from OrderItem models', function (): void {
    $order = App\Models\Order::factory()->create();
    $product = Product::factory()->create();
    App\Models\OrderItem::factory()->for($order)->for($product)->create(['total_price' => '20.0000']);
    App\Models\OrderItem::factory()->for($order)->for($product)->create(['total_price' => '10.0000']);

    expect(OrderUtility::calculateSubtotal($order->items))->toBe('30.0000');
});

test('hydrateItems throws when a referenced variant is missing', function (): void {
    $product = Product::factory()->create();

    expect(fn () => app(OrderUtility::class)->hydrateItems([
        ['product_id' => $product->id, 'product_variant_id' => '00000000-0000-0000-0000-000000000000', 'quantity' => 1],
    ]))->toThrow(ModelNotFoundException::class);
});
