<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\ProductOption;
use App\Models\ProductOptionValue;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\Factory;
use Spatie\Translatable\HasTranslations;

covers(ProductOptionValue::class);

uses()->group('models', 'product');

test('has factory', function () {
    expect(ProductOptionValue::factory())->toBeInstanceOf(Factory::class);
});

test('uses HasUuids trait', function () {
    expect(ProductOptionValue::class)->toUseTrait(HasUuids::class);
});

test('uses HasTranslations trait', function () {
    expect(ProductOptionValue::class)->toUseTrait(HasTranslations::class);
});

test('has translatable fields', function () {
    $value = new ProductOptionValue();

    expect($value->getTranslatableAttributes())->toContain('value');
});

test('touches option relationship', function () {
    $value = new ProductOptionValue();

    expect($value->getTouchedRelations())->toBe(['option']);
});

test('has option relationship', function () {
    $option = ProductOption::factory()->create();
    $value = ProductOptionValue::factory()->create([
        'product_option_id' => $option->id,
    ]);

    expect($value->option)->toBeInstanceOf(ProductOption::class)
        ->and($value->option->id)->toBe($option->id);
});
