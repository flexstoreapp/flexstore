<?php

declare(strict_types=1);

use App\DTOs\ProductOptionValueInput;

covers(ProductOptionValueInput::class);

uses()->group('dtos', 'product');

test('stores the id and value', function () {
    $input = new ProductOptionValueInput(id: 'value-1', value: 'Blue');

    expect($input->id)->toBe('value-1')
        ->and($input->value)->toBe('Blue');
});

test('fromArray casts the scalars to strings', function () {
    $input = ProductOptionValueInput::fromArray(['id' => 12, 'value' => 42]);

    expect($input->id)->toBe('12')
        ->and($input->value)->toBe('42');
});
