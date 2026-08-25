<?php

declare(strict_types=1);

use App\Actions\DestroyTaxRateAction;
use App\Models\TaxRate;

covers(DestroyTaxRateAction::class);

uses()->group('actions', 'tax');

test('can delete a tax rate', function () {
    $taxRate = TaxRate::factory()->create();

    $action = new DestroyTaxRateAction();

    $result = $action->handle($taxRate);

    expect($result)->toBeTrue();
    expect(TaxRate::find($taxRate->id))->toBeNull();
});
