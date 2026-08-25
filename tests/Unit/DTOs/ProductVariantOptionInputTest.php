<?php

declare(strict_types=1);

use App\DTOs\ProductVariantOptionInput;

covers(ProductVariantOptionInput::class);

uses()->group('dtos', 'product');

test('stores the option and value ids', function () {
    $input = new ProductVariantOptionInput(optionId: 'option-1', valueId: 'value-1');

    expect($input->optionId)->toBe('option-1')
        ->and($input->valueId)->toBe('value-1');
});

test('fromArray casts the scalars to strings', function () {
    $input = ProductVariantOptionInput::fromArray(['option_id' => 1, 'value_id' => 2]);

    expect($input->optionId)->toBe('1')
        ->and($input->valueId)->toBe('2');
});
