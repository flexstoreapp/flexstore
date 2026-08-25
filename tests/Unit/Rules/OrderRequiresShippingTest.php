<?php

declare(strict_types=1);

use App\Models\Order;
use App\Rules\OrderRequiresShipping;
use Illuminate\Support\Facades\Validator;

covers(OrderRequiresShipping::class);

uses()->group('rules', 'order');

function validateOrderShipping(Order $order): Illuminate\Contracts\Validation\Validator
{
    return Validator::make(
        ['items' => [1]],
        ['items' => [new OrderRequiresShipping($order)]],
    );
}

test('fails when the order has no items', function () {
    $order = Order::factory()->create();

    $validator = validateOrderShipping($order->fresh());

    expect($validator->passes())->toBeFalse()
        ->and($validator->errors()->first('items'))
        ->toBe(__('This order has no items that require shipping.'));
});
