<?php

declare(strict_types=1);

use App\DTOs\OrderItemInput;

covers(OrderItemInput::class);

uses()->group('dtos', 'order');

test('fromArray casts the scalars and keeps the variant options', function () {
    $input = OrderItemInput::fromArray([
        'id' => '5',
        'product_id' => '7',
        'product_variant_id' => 'variant-1',
        'quantity' => '2',
        'variant_options' => ['color' => 'blue'],
    ]);

    expect($input->id)->toBe(5)
        ->and($input->productId)->toBe(7)
        ->and($input->productVariantId)->toBe('variant-1')
        ->and($input->quantity)->toBe(2)
        ->and($input->variantOptions)->toBe(['color' => 'blue']);
});

test('fromArray leaves the optional fields null for a new line item', function () {
    $input = OrderItemInput::fromArray(['product_id' => 7, 'quantity' => 1]);

    expect($input->id)->toBeNull()
        ->and($input->productVariantId)->toBeNull()
        ->and($input->variantOptions)->toBeNull();
});

test('fromArray ignores variant options that are not an array', function () {
    $input = OrderItemInput::fromArray([
        'product_id' => 7,
        'quantity' => 1,
        'variant_options' => 'blue',
    ]);

    expect($input->variantOptions)->toBeNull();
});

test('toArray round trips through fromArray', function () {
    $data = [
        'id' => 5,
        'product_id' => 7,
        'product_variant_id' => 'variant-1',
        'quantity' => 2,
        'variant_options' => ['color' => 'blue'],
    ];

    expect(OrderItemInput::fromArray($data)->toArray())->toBe($data);
});
