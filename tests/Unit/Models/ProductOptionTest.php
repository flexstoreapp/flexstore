<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Product;
use App\Models\ProductOption;
use App\Models\ProductOptionValue;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\Factory;
use Spatie\Translatable\HasTranslations;

covers(ProductOption::class);

uses()->group('models', 'product');

test('has factory', function () {
    expect(ProductOption::factory())->toBeInstanceOf(Factory::class);
});

test('uses HasUuids trait', function () {
    expect(ProductOption::class)->toUseTrait(HasUuids::class);
});

test('uses HasTranslations trait', function () {
    expect(ProductOption::class)->toUseTrait(HasTranslations::class);
});

test('has translatable fields', function () {
    $option = new ProductOption();

    expect($option->getTranslatableAttributes())->toContain('name');
});

test('touches product relationship', function () {
    $option = new ProductOption();

    expect($option->getTouchedRelations())->toBe(['product']);
});

test('has product relationship', function () {
    $product = Product::factory()->create();
    $option = ProductOption::factory()->create([
        'product_id' => $product->id,
    ]);

    expect($option->product)->toBeInstanceOf(Product::class)
        ->and($option->product->id)->toBe($product->id);
});

test('has values relationship', function () {
    $product = Product::factory()->create();
    $option = ProductOption::factory()->create([
        'product_id' => $product->id,
    ]);
    $value = ProductOptionValue::factory()->create([
        'product_option_id' => $option->id,
    ]);

    expect($option->values)->toBeInstanceOf(Collection::class)
        ->and($option->values->first())->toBeInstanceOf(ProductOptionValue::class)
        ->and($option->values->first()->id)->toBe($value->id);
});
