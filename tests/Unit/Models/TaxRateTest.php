<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Enums\TaxCategory;
use App\Models\OrderTaxDetail;
use App\Models\Region;
use App\Models\TaxRate;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\Factory;

covers(TaxRate::class);

uses()->group('models', 'tax');

test('has factory', function () {
    expect(TaxRate::factory())->toBeInstanceOf(Factory::class);
});

test('casts attributes correctly', function () {
    $taxRate = TaxRate::factory()->create([
        'rate' => '8.25',
        'min_order_value' => '50.0000',
        'max_order_value' => '1000.0000',
    ]);

    $casts = $taxRate->casts();

    expect($casts)
        ->toBeArray()
        ->toHaveKey('rate', 'decimal:2')
        ->toHaveKey('min_order_value', 'decimal:4')
        ->toHaveKey('max_order_value', 'decimal:4')
        ->toHaveKey('is_compound', 'boolean')
        ->toHaveKey('is_active', 'boolean')
        ->toHaveKey('tax_category', TaxCategory::class);
});

test('has region relationship', function () {
    $taxRate = TaxRate::factory()->create();

    expect($taxRate->region)->toBeInstanceOf(Region::class);
});

test('casts tax category to enum', function () {
    $taxRate = TaxRate::factory()->create([
        'tax_category' => TaxCategory::Electronics,
    ]);

    expect($taxRate->tax_category)->toBe(TaxCategory::Electronics);
});

test('handles null tax category', function () {
    $taxRate = TaxRate::factory()->create(['tax_category' => null]);

    expect($taxRate->tax_category)->toBeNull();
});

test('has order tax details relationship', function () {
    $taxRate = TaxRate::factory()->create();
    $orderTaxDetail = OrderTaxDetail::factory()->create([
        'tax_rate_id' => $taxRate->id,
    ]);

    expect($taxRate->orderTaxDetails)->toBeInstanceOf(Collection::class)
        ->and($taxRate->orderTaxDetails->first())->toBeInstanceOf(OrderTaxDetail::class)
        ->and($taxRate->orderTaxDetails->first()->id)->toBe($orderTaxDetail->id);
});

test('handles empty order tax details collection', function () {
    $taxRate = TaxRate::factory()->create();

    expect($taxRate->orderTaxDetails)->toBeInstanceOf(Collection::class);
    expect($taxRate->orderTaxDetails)->toHaveCount(0);
});
