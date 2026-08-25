<?php

declare(strict_types=1);

use App\DTOs\TaxDetailInput;
use App\Enums\OrderTaxDetailItemType;

covers(TaxDetailInput::class);

uses()->group('dtos', 'tax');

test('withScaledAmounts returns a new instance with updated amounts but every other field unchanged', function () {
    $original = new TaxDetailInput(
        orderItemId: 7,
        taxRateId: 3,
        itemType: OrderTaxDetailItemType::Product,
        taxName: ['en' => 'VAT', 'fr' => 'TVA'],
        taxRate: '20.00',
        taxableAmount: '511.0900',
        taxAmount: '102.2180',
        isCompound: true,
        proportion: '0.75',
    );

    $scaled = $original->withScaledAmounts(taxableAmount: '511.09', taxAmount: '102.22');

    expect($scaled)->not->toBe($original)
        ->and($scaled->taxableAmount)->toBe('511.09')
        ->and($scaled->taxAmount)->toBe('102.22')
        ->and($scaled->orderItemId)->toBe($original->orderItemId)
        ->and($scaled->taxRateId)->toBe($original->taxRateId)
        ->and($scaled->itemType)->toBe($original->itemType)
        ->and($scaled->taxName)->toBe($original->taxName)
        ->and($scaled->taxRate)->toBe($original->taxRate)
        ->and($scaled->isCompound)->toBe($original->isCompound)
        ->and($scaled->proportion)->toBe($original->proportion);
});

test('properties are readonly', function () {
    $detail = new TaxDetailInput(
        orderItemId: 1,
        taxRateId: 1,
        itemType: OrderTaxDetailItemType::Product,
        taxName: ['en' => 'Tax'],
        taxRate: '10.00',
        taxableAmount: '100.0000',
        taxAmount: '10.0000',
        isCompound: false,
        proportion: null,
    );

    expect(fn () => $detail->taxAmount = '20.0000')->toThrow(Error::class);
});
