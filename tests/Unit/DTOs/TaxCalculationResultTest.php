<?php

declare(strict_types=1);

use App\DTOs\TaxCalculationResult;
use App\DTOs\TaxDetailInput;
use App\Enums\OrderTaxDetailItemType;

covers(TaxCalculationResult::class);

uses()->group('dtos', 'tax');

function makeDetail(
    ?int $orderItemId = 1,
    ?int $taxRateId = 10,
    OrderTaxDetailItemType $itemType = OrderTaxDetailItemType::Product,
    array $taxName = ['en' => 'VAT'],
    string $taxRate = '10.00',
    string $taxableAmount = '100.0000',
    string $taxAmount = '10.0000',
    bool $isCompound = false,
    ?string $proportion = null,
): TaxDetailInput {
    return new TaxDetailInput(
        orderItemId: $orderItemId,
        taxRateId: $taxRateId,
        itemType: $itemType,
        taxName: $taxName,
        taxRate: $taxRate,
        taxableAmount: $taxableAmount,
        taxAmount: $taxAmount,
        isCompound: $isCompound,
        proportion: $proportion,
    );
}

test('stores tax total and tax details', function () {
    $details = [makeDetail()];

    $result = new TaxCalculationResult(taxTotal: '10.0000', taxDetails: $details);

    expect($result->taxTotal)->toBe('10.0000')
        ->and($result->taxDetails)->toBe($details);
});

test('taxDetailsForStorage stamps each detail with order_id and timestamps', function () {
    $result = new TaxCalculationResult(taxTotal: '11.0000', taxDetails: [
        makeDetail(),
        makeDetail(orderItemId: null, taxRateId: null, itemType: OrderTaxDetailItemType::Shipping, taxableAmount: '10.0000', taxAmount: '1.0000'),
    ]);
    $stored = $result->taxDetailsForStorage(42);

    expect($stored)->toHaveCount(2);

    foreach ($stored as $row) {
        expect($row['order_id'])->toBe(42)
            ->and($row)->toHaveKey('created_at')
            ->and($row)->toHaveKey('updated_at');
    }
});

test('taxDetailsForStorage preserves original detail fields', function () {
    $result = new TaxCalculationResult(taxTotal: '10.0000', taxDetails: [
        makeDetail(orderItemId: 5, taxRateId: 3, taxName: ['en' => 'GST'], taxRate: '5.00', taxableAmount: '200.0000', taxAmount: '10.0000'),
    ]);
    $stored = $result->taxDetailsForStorage(99);

    expect($stored[0]['order_item_id'])->toBe(5)
        ->and($stored[0]['tax_rate_id'])->toBe(3)
        ->and($stored[0]['item_type'])->toBe(OrderTaxDetailItemType::Product->value)
        ->and($stored[0]['tax_name'])->toBe(json_encode(['en' => 'GST']))
        ->and($stored[0]['tax_rate'])->toBe('5.00')
        ->and($stored[0]['taxable_amount'])->toBe('200.0000')
        ->and($stored[0]['tax_amount'])->toBe('10.0000')
        ->and($stored[0]['is_compound'])->toBeFalse();
});

test('taxDetailsForStorage preserves optional proportion field', function () {
    $result = new TaxCalculationResult(taxTotal: '10.0000', taxDetails: [
        makeDetail(proportion: '0.666667'),
    ]);

    expect($result->taxDetailsForStorage(7)[0]['proportion'])->toBe('0.666667');
});

test('taxDetailsForStorage returns empty array when tax details are empty', function () {
    $result = new TaxCalculationResult(taxTotal: '0.0000', taxDetails: []);

    expect($result->taxDetailsForStorage(1))->toBe([]);
});

test('scaledTo rounds amounts to currency decimal places and recomputes total', function () {
    $result = new TaxCalculationResult(taxTotal: '159.9440', taxDetails: [
        makeDetail(orderItemId: 1, taxRate: '20.00', taxableAmount: '511.0900', taxAmount: '102.2180'),
        makeDetail(orderItemId: 2, taxRate: '20.00', taxableAmount: '288.6300', taxAmount: '57.7260'),
    ]);

    $scaled = $result->scaledTo(2);

    expect($scaled->taxDetails[0]->taxAmount)->toBe('102.2200')
        ->and($scaled->taxDetails[0]->taxableAmount)->toBe('511.0900')
        ->and($scaled->taxDetails[1]->taxAmount)->toBe('57.7300')
        ->and($scaled->taxDetails[1]->taxableAmount)->toBe('288.6300')
        ->and($scaled->taxTotal)->toBe('159.9500');
});

test('scaledTo with zero decimal places for JPY', function () {
    $result = new TaxCalculationResult(taxTotal: '6.6700', taxDetails: [
        makeDetail(orderItemId: 1, taxRateId: 1, taxName: ['en' => 'Tax'], taxRate: '3.33', taxAmount: '3.3300'),
        makeDetail(orderItemId: 2, taxRateId: 1, taxName: ['en' => 'Tax'], taxRate: '3.33', taxAmount: '3.3400'),
    ]);

    $scaled = $result->scaledTo(0);

    expect($scaled->taxDetails[0]->taxAmount)->toBe('3.0000')
        ->and($scaled->taxDetails[1]->taxAmount)->toBe('3.0000')
        ->and($scaled->taxTotal)->toBe('6.0000');
});

test('scaledTo returns empty result when no details', function () {
    $result = new TaxCalculationResult(taxTotal: '0.0000', taxDetails: []);

    $scaled = $result->scaledTo(2);

    expect($scaled->taxTotal)->toBe('0.0000')
        ->and($scaled->taxDetails)->toBe([]);
});

test('aggregatedTaxDetails groups by tax rate id and sums amounts', function () {
    $result = new TaxCalculationResult(taxTotal: '25.0000', taxDetails: [
        makeDetail(orderItemId: 1, taxRateId: 10, taxableAmount: '100.0000', taxAmount: '10.0000'),
        makeDetail(orderItemId: 2, taxRateId: 10, taxableAmount: '50.0000', taxAmount: '5.0000'),
        makeDetail(orderItemId: null, taxRateId: 10, itemType: OrderTaxDetailItemType::Shipping, taxableAmount: '20.0000', taxAmount: '2.0000', proportion: '1.0000'),
        makeDetail(orderItemId: 1, taxRateId: 20, taxName: ['en' => 'State Tax'], taxRate: '5.00', taxableAmount: '100.0000', taxAmount: '5.0000'),
        makeDetail(orderItemId: 2, taxRateId: 20, taxName: ['en' => 'State Tax'], taxRate: '5.00', taxableAmount: '50.0000', taxAmount: '3.0000'),
    ]);

    $aggregated = $result->aggregatedTaxDetails();

    expect($aggregated)->toHaveCount(2)
        ->and($aggregated[0]['tax_name'])->toBe(['en' => 'VAT'])
        ->and($aggregated[0]['tax_rate'])->toBe('10.00')
        ->and($aggregated[0]['taxable_amount'])->toBe('170.0000')
        ->and($aggregated[0]['tax_amount'])->toBe('17.0000')
        ->and($aggregated[1]['tax_name'])->toBe(['en' => 'State Tax'])
        ->and($aggregated[1]['tax_rate'])->toBe('5.00')
        ->and($aggregated[1]['taxable_amount'])->toBe('150.0000')
        ->and($aggregated[1]['tax_amount'])->toBe('8.0000');
});

test('aggregatedTaxDetails groups default tax by name and rate when tax_rate_id is null', function () {
    $result = new TaxCalculationResult(taxTotal: '15.0000', taxDetails: [
        makeDetail(orderItemId: 1, taxRateId: null, taxName: ['en' => 'Default Tax'], taxableAmount: '100.0000', taxAmount: '10.0000'),
        makeDetail(orderItemId: 2, taxRateId: null, taxName: ['en' => 'Default Tax'], taxableAmount: '50.0000', taxAmount: '5.0000'),
    ]);

    $aggregated = $result->aggregatedTaxDetails();

    expect($aggregated)->toHaveCount(1)
        ->and($aggregated[0]['tax_name'])->toBe(['en' => 'Default Tax'])
        ->and($aggregated[0]['taxable_amount'])->toBe('150.0000')
        ->and($aggregated[0]['tax_amount'])->toBe('15.0000');
});

test('aggregatedTaxDetails returns empty array when no details', function () {
    $result = new TaxCalculationResult(taxTotal: '0.0000', taxDetails: []);

    expect($result->aggregatedTaxDetails())->toBe([]);
});

test('properties are readonly', function () {
    $result = new TaxCalculationResult(taxTotal: '5.0000', taxDetails: []);

    expect(fn () => $result->taxTotal = '10.0000')->toThrow(Error::class)
        ->and(fn () => $result->taxDetails = [])->toThrow(Error::class);
});
