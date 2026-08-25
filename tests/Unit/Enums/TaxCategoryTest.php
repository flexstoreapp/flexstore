<?php

declare(strict_types=1);

use App\Enums\TaxCategory;

covers(TaxCategory::class);

uses()->group('enums', 'tax');

test('label returns non-empty string for every case', function () {
    foreach (TaxCategory::cases() as $case) {
        expect($case->label())->toBeString()->not->toBe('');
    }
});

test('label returns the translated string for known cases', function () {
    expect(TaxCategory::Standard->label())->toBe(__('Standard'))
        ->and(TaxCategory::FoodAndBeverages->label())->toBe(__('Food & beverages'))
        ->and(TaxCategory::Electronics->label())->toBe(__('Electronics'));
});

test('englishLabel returns the canonical English string regardless of current locale', function () {
    app()->setLocale('ar');

    expect(TaxCategory::Standard->englishLabel())->toBe('Standard')
        ->and(TaxCategory::FoodAndBeverages->englishLabel())->toBe('Food & beverages')
        ->and(TaxCategory::Electronics->englishLabel())->toBe('Electronics');
});

test('options returns value-label pairs for every case', function () {
    $options = TaxCategory::options();

    expect($options)->toHaveCount(count(TaxCategory::cases()));

    foreach ($options as $option) {
        expect($option)->toHaveKeys(['value', 'label'])
            ->and($option['value'])->toBeString()->not->toBe('')
            ->and($option['label'])->toBeString()->not->toBe('');
    }

    $values = array_column($options, 'value');
    expect($values)->toContain(TaxCategory::Standard->value)
        ->and($values)->toContain(TaxCategory::FoodAndBeverages->value);
});
