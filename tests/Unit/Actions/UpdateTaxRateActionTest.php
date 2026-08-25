<?php

declare(strict_types=1);

use App\Actions\UpdateTaxRateAction;
use App\DTOs\UpdateTaxRateInput;
use App\Enums\TaxCategory;
use App\Models\Region;
use App\Models\TaxRate;

covers(UpdateTaxRateAction::class, UpdateTaxRateInput::class);

uses()->group('actions', 'tax');

test('updates a tax rate with all fields', function () {
    $taxRate = TaxRate::factory()->create([
        'name' => 'Old Tax',
        'rate' => '5.0',
        'priority' => 5,
        'is_compound' => false,
        'is_active' => false,
    ]);

    $newRegion = Region::factory()->create();
    $newTaxCategory = TaxCategory::Standard;

    $data = [
        'name' => 'Updated Tax',
        'tax_category' => $newTaxCategory,
        'region_id' => $newRegion->id,
        'rate' => '8.75',
        'priority' => 25,
        'min_order_value' => '10.0000',
        'max_order_value' => '200.0000',
        'is_compound' => true,
        'is_active' => true,
    ];

    $action = new UpdateTaxRateAction();
    $updatedRate = $action->handle($taxRate, UpdateTaxRateInput::fromArray($data));

    expect($updatedRate)->toBeInstanceOf(TaxRate::class)
        ->and($updatedRate->name)->toBe('Updated Tax')
        ->and($updatedRate->tax_category)->toBe($newTaxCategory)
        ->and($updatedRate->region_id)->toBe($newRegion->id)
        ->and($updatedRate->rate)->toBe('8.75')
        ->and($updatedRate->priority)->toBe(25)
        ->and($updatedRate->min_order_value)->toBe('10.0000')
        ->and($updatedRate->max_order_value)->toBe('200.0000')
        ->and($updatedRate->is_compound)->toBeTrue()
        ->and($updatedRate->is_active)->toBeTrue();
});

test('updates only specified fields', function () {
    $taxRate = TaxRate::factory()->create([
        'name' => 'Original Tax',
        'rate' => '5.0',
    ]);

    $data = [
        'rate' => '8.75',
    ];

    $action = new UpdateTaxRateAction();
    $updatedRate = $action->handle($taxRate, UpdateTaxRateInput::fromArray($data));

    expect($updatedRate)->toBeInstanceOf(TaxRate::class)
        ->and($updatedRate->name)->toBe('Original Tax') // unchanged
        ->and($updatedRate->rate)->toBe('8.75'); // updated
});

test('updates tax_category to null for all categories', function () {
    $taxCategory = TaxCategory::Standard;
    $taxRate = TaxRate::factory()->create([
        'tax_category' => $taxCategory,
    ]);

    $data = [
        'tax_category' => null,
    ];

    $action = new UpdateTaxRateAction();
    $updatedRate = $action->handle($taxRate, UpdateTaxRateInput::fromArray($data));

    expect($updatedRate->tax_category)->toBeNull();
});
