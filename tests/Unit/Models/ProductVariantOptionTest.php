<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\ProductOption;
use App\Models\ProductOptionValue;
use App\Models\ProductVariant;
use App\Models\ProductVariantOption;

covers(ProductVariantOption::class);

uses()->group('models', 'product');

test('has variant relationship', function () {
    $variant = ProductVariant::factory()->create();
    $variantOption = ProductVariantOption::factory()->create([
        'product_variant_id' => $variant->id,
    ]);

    expect($variantOption->variant)->toBeInstanceOf(ProductVariant::class)
        ->and($variantOption->variant->id)->toBe($variant->id);
});

test('has option relationship', function () {
    $option = ProductOption::factory()->create();
    $variantOption = ProductVariantOption::factory()->create([
        'product_option_id' => $option->id,
    ]);

    expect($variantOption->option)->toBeInstanceOf(ProductOption::class)
        ->and($variantOption->option->id)->toBe($option->id);
});

test('has value relationship', function () {
    $value = ProductOptionValue::factory()->create();
    $variantOption = ProductVariantOption::factory()->create([
        'product_option_value_id' => $value->id,
    ]);

    expect($variantOption->value)->toBeInstanceOf(ProductOptionValue::class)
        ->and($variantOption->value->id)->toBe($value->id);
});
