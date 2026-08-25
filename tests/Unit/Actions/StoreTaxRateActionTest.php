<?php

declare(strict_types=1);

use App\Actions\StoreTaxRateAction;
use App\DTOs\StoreTaxRateInput;
use App\Enums\TaxCategory;
use App\Models\Region;
use App\Models\TaxRate;

covers(StoreTaxRateAction::class, StoreTaxRateInput::class);

uses()->group('actions', 'tax');

test('creates a tax rate with all fields', function () {
    $region = Region::factory()->create();
    $taxCategory = TaxCategory::Standard;

    $data = [
        'name' => 'Sales Tax',
        'tax_category' => $taxCategory,
        'region_id' => $region->id,
        'rate' => '10.25',
        'min_order_value' => '5.0000',
        'max_order_value' => '100.0000',
        'is_compound' => false,
        'is_active' => true,
        'priority' => 15,
    ];

    $action = new StoreTaxRateAction();
    $taxRate = $action->handle(StoreTaxRateInput::fromArray($data));

    expect($taxRate)->toBeInstanceOf(TaxRate::class)
        ->and($taxRate->name)->toBe('Sales Tax')
        ->and($taxRate->tax_category)->toBe($taxCategory)
        ->and($taxRate->region_id)->toBe($region->id)
        ->and($taxRate->rate)->toBe('10.25')
        ->and($taxRate->min_order_value)->toBe('5.0000')
        ->and($taxRate->max_order_value)->toBe('100.0000')
        ->and($taxRate->is_compound)->toBeFalse()
        ->and($taxRate->is_active)->toBeTrue()
        ->and($taxRate->priority)->toBe(15);
});

test('creates a tax rate with minimal required fields', function () {
    $region = Region::factory()->create();

    $data = [
        'name' => 'Basic Tax',
        'region_id' => $region->id,
        'rate' => '5.00',
        'is_compound' => false,
        'is_active' => true,
    ];

    $action = new StoreTaxRateAction();
    $taxRate = $action->handle(StoreTaxRateInput::fromArray($data));

    expect($taxRate)->toBeInstanceOf(TaxRate::class)
        ->and($taxRate->name)->toBe('Basic Tax')
        ->and($taxRate->tax_category)->toBeNull()
        ->and($taxRate->region_id)->toBe($region->id)
        ->and($taxRate->rate)->toBe('5.00')
        ->and($taxRate->min_order_value)->toBeNull()
        ->and($taxRate->max_order_value)->toBeNull()
        ->and($taxRate->is_compound)->toBeFalse()
        ->and($taxRate->is_active)->toBeTrue()
        ->and($taxRate->priority)->toBe(0);
});

test('creates a tax rate for all categories when tax_category is null', function () {
    $region = Region::factory()->create();

    $data = [
        'name' => 'General Tax',
        'tax_category' => null,
        'region_id' => $region->id,
        'rate' => '8.50',
        'is_compound' => false,
        'is_active' => true,
    ];

    $action = new StoreTaxRateAction();
    $taxRate = $action->handle(StoreTaxRateInput::fromArray($data));

    expect($taxRate)->toBeInstanceOf(TaxRate::class)
        ->and($taxRate->tax_category)->toBeNull();
});
