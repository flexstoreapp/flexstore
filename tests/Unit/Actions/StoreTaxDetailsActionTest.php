<?php

declare(strict_types=1);

use App\Actions\StoreTaxDetailsAction;
use App\DTOs\TaxDetailInput;
use App\Enums\OrderTaxDetailItemType;
use App\Models\Order;
use App\Models\OrderTaxDetail;
use App\Models\TaxRate;

covers(StoreTaxDetailsAction::class);

uses()->group('actions', 'tax');

test('saves tax details to database', function () {
    $order = Order::factory()->create();
    $taxRate1 = TaxRate::factory()->create();
    $taxRate2 = TaxRate::factory()->create();

    $taxDetails = [
        new TaxDetailInput(
            orderItemId: null,
            taxRateId: $taxRate1->id,
            itemType: OrderTaxDetailItemType::Product,
            taxName: castAsTranslatableArray('VAT'),
            taxRate: '20.00',
            taxableAmount: '100.0000',
            taxAmount: '20.0000',
            isCompound: false,
            proportion: null,
        ),
        new TaxDetailInput(
            orderItemId: null,
            taxRateId: $taxRate2->id,
            itemType: OrderTaxDetailItemType::Product,
            taxName: castAsTranslatableArray('Sales Tax'),
            taxRate: '8.25',
            taxableAmount: '100.0000',
            taxAmount: '8.2500',
            isCompound: false,
            proportion: null,
        ),
    ];

    $action = new StoreTaxDetailsAction();
    $action->handle($order->id, $taxDetails);

    expect(OrderTaxDetail::query()->count())->toBe(2);

    $savedDetails = OrderTaxDetail::query()->get();

    expect($savedDetails[0]->tax_name)->toBe('VAT')
        ->and($savedDetails[0]->tax_amount)->toBe('20.0000')
        ->and($savedDetails[1]->tax_name)->toBe('Sales Tax')
        ->and($savedDetails[1]->tax_amount)->toBe('8.2500');
});

test('saves single tax detail', function () {
    $order = Order::factory()->create();
    $taxRate1 = TaxRate::factory()->create();

    $action = new StoreTaxDetailsAction();
    $action->handle($order->id, [
        new TaxDetailInput(
            orderItemId: null,
            taxRateId: $taxRate1->id,
            itemType: OrderTaxDetailItemType::Product,
            taxName: castAsTranslatableArray('GST'),
            taxRate: '15.00',
            taxableAmount: '200.0000',
            taxAmount: '30.0000',
            isCompound: false,
            proportion: null,
        ),
    ]);

    expect(OrderTaxDetail::query()->count())->toBe(1);

    $detail = OrderTaxDetail::query()->first();
    expect($detail->tax_name)->toBe('GST')
        ->and($detail->tax_rate)->toBe('15.00')
        ->and($detail->taxable_amount)->toBe('200.0000')
        ->and($detail->tax_amount)->toBe('30.0000');
});

test('saves default tax detail with null tax rate id', function () {
    $order = Order::factory()->create();

    $action = new StoreTaxDetailsAction();
    $action->handle($order->id, [
        new TaxDetailInput(
            orderItemId: null,
            taxRateId: null,
            itemType: OrderTaxDetailItemType::Product,
            taxName: castAsTranslatableArray('Default Tax'),
            taxRate: '8.50',
            taxableAmount: '39.9900',
            taxAmount: '3.4000',
            isCompound: false,
            proportion: null,
        ),
    ]);

    expect(OrderTaxDetail::query()->count())->toBe(1);

    $detail = OrderTaxDetail::query()->first();
    expect($detail->tax_rate_id)->toBeNull()
        ->and($detail->tax_name)->toBe('Default Tax')
        ->and($detail->tax_amount)->toBe('3.4000');
});

test('handles empty tax details array', function () {
    $order = Order::factory()->create();

    $action = new StoreTaxDetailsAction();
    $action->handle($order->id, []);

    expect(OrderTaxDetail::query()->count())->toBe(0);
});

test('saves tax details with zero amounts', function () {
    $order = Order::factory()->create();
    $taxRate3 = TaxRate::factory()->create();

    $action = new StoreTaxDetailsAction();
    $action->handle($order->id, [
        new TaxDetailInput(
            orderItemId: null,
            taxRateId: $taxRate3->id,
            itemType: OrderTaxDetailItemType::Product,
            taxName: castAsTranslatableArray('Exempt Tax'),
            taxRate: '0.00',
            taxableAmount: '100.0000',
            taxAmount: '0.0000',
            isCompound: false,
            proportion: null,
        ),
    ]);

    expect(OrderTaxDetail::query()->count())->toBe(1);

    $detail = OrderTaxDetail::query()->first();
    expect($detail->tax_rate)->toBe('0.00')
        ->and($detail->tax_amount)->toBe('0.0000');
});

test('saves tax details for multiple orders by calling handle per order', function () {
    $order1 = Order::factory()->create();
    $order2 = Order::factory()->create();
    $taxRate1 = TaxRate::factory()->create();

    $action = new StoreTaxDetailsAction();
    $action->handle($order1->id, [
        new TaxDetailInput(
            orderItemId: null,
            taxRateId: $taxRate1->id,
            itemType: OrderTaxDetailItemType::Product,
            taxName: castAsTranslatableArray('VAT'),
            taxRate: '20.00',
            taxableAmount: '100.0000',
            taxAmount: '20.0000',
            isCompound: false,
            proportion: null,
        ),
    ]);
    $action->handle($order2->id, [
        new TaxDetailInput(
            orderItemId: null,
            taxRateId: $taxRate1->id,
            itemType: OrderTaxDetailItemType::Product,
            taxName: castAsTranslatableArray('VAT'),
            taxRate: '20.00',
            taxableAmount: '150.0000',
            taxAmount: '30.0000',
            isCompound: false,
            proportion: null,
        ),
    ]);

    expect(OrderTaxDetail::query()->count())->toBe(2);

    $order1Details = OrderTaxDetail::query()->where('order_id', $order1->id)->get();
    $order2Details = OrderTaxDetail::query()->where('order_id', $order2->id)->get();

    expect($order1Details->count())->toBe(1)
        ->and($order2Details->count())->toBe(1)
        ->and($order1Details->first()->taxable_amount)->toBe('100.0000')
        ->and($order2Details->first()->taxable_amount)->toBe('150.0000');
});
