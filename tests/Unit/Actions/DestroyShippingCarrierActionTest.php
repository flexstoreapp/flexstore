<?php

declare(strict_types=1);

use App\Actions\DestroyShippingCarrierAction;
use App\Models\ShippingCarrier;

use function Pest\Laravel\assertDatabaseMissing;

covers(DestroyShippingCarrierAction::class);

uses()->group('actions', 'shipping');

test('deletes a shipping carrier', function () {
    $carrier = ShippingCarrier::factory()->create();

    $action = new DestroyShippingCarrierAction();
    $result = $action->handle($carrier);

    expect($result)->toBeTrue();

    assertDatabaseMissing('shipping_carriers', [
        'id' => $carrier->id,
    ]);
});
