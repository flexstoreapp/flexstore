<?php

declare(strict_types=1);

use App\DTOs\TaxableItem;
use App\Enums\TaxCategory;

covers(TaxableItem::class);

uses()->group('dtos', 'tax');

test('stores all fields', function () {
    $item = new TaxableItem(
        id: 1,
        totalPrice: '100.0000',
        isTaxExempt: false,
        taxCategory: TaxCategory::Electronics,
    );

    expect($item->id)->toBe(1)
        ->and($item->totalPrice)->toBe('100.0000')
        ->and($item->isTaxExempt)->toBeFalse()
        ->and($item->taxCategory)->toBe(TaxCategory::Electronics);
});

test('allows null id and tax category', function () {
    $item = new TaxableItem(
        id: null,
        totalPrice: '50.0000',
        isTaxExempt: true,
        taxCategory: null,
    );

    expect($item->id)->toBeNull()
        ->and($item->taxCategory)->toBeNull()
        ->and($item->isTaxExempt)->toBeTrue();
});

test('properties are readonly', function () {
    $item = new TaxableItem(id: 1, totalPrice: '10.0000', isTaxExempt: false, taxCategory: null);

    expect(fn () => $item->totalPrice = '20.0000')->toThrow(Error::class);
});
