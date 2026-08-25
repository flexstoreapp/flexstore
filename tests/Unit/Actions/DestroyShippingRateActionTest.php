<?php

declare(strict_types=1);

use App\Actions\DestroyShippingRateAction;
use App\Models\ShippingRate;

use function Pest\Laravel\assertDatabaseMissing;

covers(DestroyShippingRateAction::class);

uses()->group('actions', 'shipping');

test('deletes a shipping rate', function () {
    $shippingRate = ShippingRate::factory()->create();

    $action = new DestroyShippingRateAction();
    $result = $action->handle($shippingRate);

    expect($result)->toBeTrue();

    assertDatabaseMissing('shipping_rates', [
        'id' => $shippingRate->id,
    ]);
});
