<?php

declare(strict_types=1);

use App\Actions\UpsertProductOptionsAction;
use App\DTOs\ProductOptionInput;
use App\Models\Product;
use App\Models\ProductOption;
use App\Models\ProductOptionValue;
use Illuminate\Support\Collection;

covers(UpsertProductOptionsAction::class, ProductOptionInput::class);

uses()->group('actions', 'product');

test('creates product options', function () {
    $product = Product::factory()->create();

    $options = [
        [
            'id' => fake()->uuid(),
            'name' => 'Size',
            'values' => [
                ['id' => fake()->uuid(), 'value' => 'Small'],
                ['id' => fake()->uuid(), 'value' => 'Medium'],
            ],
        ],
    ];

    $action = new UpsertProductOptionsAction();
    $result = $action->handle($product, array_map(ProductOptionInput::fromArray(...), $options));

    expect($result)->toBeInstanceOf(Collection::class)->toHaveCount(1)
        ->and($result[0])->toBeInstanceOf(ProductOption::class);

    expect($result[0]->name)->toBe('Size')
        ->and($result[0]->product_id)->toBe($product->id);

    expect($result[0]->values)->toBeInstanceOf(Collection::class)->toHaveCount(2)
        ->and($result[0]->values)->toContainOnlyInstancesOf(ProductOptionValue::class);

    expect($result[0]->values->pluck('value')->all())->toContain('Small', 'Medium');
});

test('updates product options', function () {
    $product = Product::factory()->create();
    $option = $product->options()->create(['name' => 'Size']);
    $option->values()->create(['id' => fake()->uuid(), 'value' => 'Small']);

    $options = [
        [
            'id' => $option->id,
            'name' => 'Size Updated',
            'values' => [
                ['id' => fake()->uuid(), 'value' => 'Large'],
            ],
        ],
    ];

    $action = new UpsertProductOptionsAction();
    $result = $action->handle($product, array_map(ProductOptionInput::fromArray(...), $options));

    $product->refresh();

    expect($result)->toBeInstanceOf(Collection::class)->toHaveCount(1);

    expect($result[0]->name)->toBe('Size Updated')
        ->and($result[0]->values)->toHaveCount(1)
        ->and($result[0]->values[0]->value)->toBe('Large');

    // Verify the product has only one option after update
    expect($product->options)->toHaveCount(1)
        ->and($product->options[0]->name)->toBe('Size Updated');
});

test('handles empty options input', function () {
    $product = Product::factory()->create();
    $option = $product->options()->create(['name' => 'Size']);
    $option->values()->create([
        'id' => fake()->uuid(),
        'value' => 'Small',
    ]);

    $action = new UpsertProductOptionsAction();
    $result = $action->handle($product, array_map(ProductOptionInput::fromArray(...), []));

    expect($result)->toBeInstanceOf(Collection::class)->toBeEmpty();
    expect($product->options()->count())->toBe(0);
});

test('removes orphaned options when updating with fewer options', function () {
    $product = Product::factory()->create();

    $option1 = $product->options()->create(['name' => 'Size']);
    $option2 = $product->options()->create(['name' => 'Color']);
    $option3 = $product->options()->create(['name' => 'Material']);

    $option1->values()->create(['id' => fake()->uuid(), 'value' => 'Small']);
    $option2->values()->create(['id' => fake()->uuid(), 'value' => 'Red']);
    $option3->values()->create(['id' => fake()->uuid(), 'value' => 'Cotton']);

    // Update with only one option
    $options = [
        [
            'id' => $option1->id,
            'name' => 'Size Updated',
            'values' => [
                ['id' => fake()->uuid(), 'value' => 'Large'],
            ],
        ],
    ];

    $action = new UpsertProductOptionsAction();
    $result = $action->handle($product, array_map(ProductOptionInput::fromArray(...), $options));

    expect($result)->toHaveCount(1);
    expect($product->options()->count())->toBe(1);
    expect($product->options()->first()->name)->toBe('Size Updated');

    // Verify orphaned options are deleted
    expect(ProductOption::find($option2->id))->toBeNull();
    expect(ProductOption::find($option3->id))->toBeNull();
});

test('removes orphaned option values when updating with fewer values', function () {
    $product = Product::factory()->create();
    $option = $product->options()->create(['name' => 'Size']);
    $value1 = $option->values()->create(['id' => fake()->uuid(), 'value' => 'Small']);
    $value2 = $option->values()->create(['id' => fake()->uuid(), 'value' => 'Medium']);
    $value3 = $option->values()->create(['id' => fake()->uuid(), 'value' => 'Large']);

    // Update with only one value
    $options = [
        [
            'id' => $option->id,
            'name' => 'Size',
            'values' => [
                ['id' => $value1->id, 'value' => 'Small Updated'],
            ],
        ],
    ];

    $action = new UpsertProductOptionsAction();
    $result = $action->handle($product, array_map(ProductOptionInput::fromArray(...), $options));

    expect($result[0]->values)->toHaveCount(1);
    expect($result[0]->values[0]->value)->toBe('Small Updated');

    // Verify orphaned values are deleted
    expect(ProductOptionValue::find($value2->id))->toBeNull();
    expect(ProductOptionValue::find($value3->id))->toBeNull();
});

test('creates multiple options with multiple values', function () {
    $product = Product::factory()->create();

    $options = [
        [
            'id' => fake()->uuid(),
            'name' => 'Size',
            'values' => [
                ['id' => fake()->uuid(), 'value' => 'Small'],
                ['id' => fake()->uuid(), 'value' => 'Medium'],
                ['id' => fake()->uuid(), 'value' => 'Large'],
            ],
        ],
        [
            'id' => fake()->uuid(),
            'name' => 'Color',
            'values' => [
                ['id' => fake()->uuid(), 'value' => 'Red'],
                ['id' => fake()->uuid(), 'value' => 'Blue'],
            ],
        ],
    ];

    $action = new UpsertProductOptionsAction();
    $result = $action->handle($product, array_map(ProductOptionInput::fromArray(...), $options));

    expect($result)->toHaveCount(2);

    $sizeOption = $result->firstWhere('name', 'Size');
    $colorOption = $result->firstWhere('name', 'Color');

    expect($sizeOption)->not->toBeNull();
    expect($sizeOption->values)->toHaveCount(3);
    expect($colorOption)->not->toBeNull();
    expect($colorOption->values)->toHaveCount(2);

    expect($product->options()->count())->toBe(2);
});

test('handles option without values', function () {
    $product = Product::factory()->create();

    $options = [
        [
            'id' => fake()->uuid(),
            'name' => 'Size',
            'values' => [],
        ],
    ];

    $action = new UpsertProductOptionsAction();
    $result = $action->handle($product, array_map(ProductOptionInput::fromArray(...), $options));

    expect($result)->toHaveCount(1);
    expect($result[0]->name)->toBe('Size');
    expect($result[0]->values)->toHaveCount(0);
});

test('handles option with missing values key', function () {
    $product = Product::factory()->create();

    $options = [
        [
            'id' => fake()->uuid(),
            'name' => 'Size',
        ],
    ];

    $action = new UpsertProductOptionsAction();
    $result = $action->handle($product, array_map(ProductOptionInput::fromArray(...), $options));

    expect($result)->toHaveCount(1);
    expect($result[0]->name)->toBe('Size');
    expect($result[0]->values)->toHaveCount(0);
});

test('updates existing option values and creates new ones', function () {
    $product = Product::factory()->create();

    $option = $product->options()->create(['name' => 'Size']);
    $existingValue = $option->values()->create(['id' => fake()->uuid(), 'value' => 'Small']);

    $newValueId = fake()->uuid();
    $options = [
        [
            'id' => $option->id,
            'name' => 'Size',
            'values' => [
                ['id' => $existingValue->id, 'value' => 'Small Updated'],
                ['id' => $newValueId, 'value' => 'Large'],
            ],
        ],
    ];

    $action = new UpsertProductOptionsAction();
    $result = $action->handle($product, array_map(ProductOptionInput::fromArray(...), $options));

    expect($result[0]->values)->toHaveCount(2);

    $updatedValue = $result[0]->values->firstWhere('id', $existingValue->id);
    $newValue = $result[0]->values->firstWhere('id', $newValueId);

    expect($updatedValue->value)->toBe('Small Updated');
    expect($newValue->value)->toBe('Large');
});

test('creates multiple options with correct data', function () {
    $product = Product::factory()->create();

    $option1Id = fake()->uuid();
    $option2Id = fake()->uuid();
    $option3Id = fake()->uuid();

    $options = [
        [
            'id' => $option1Id,
            'name' => 'Color',
            'values' => [['id' => fake()->uuid(), 'value' => 'Red']],
        ],
        [
            'id' => $option2Id,
            'name' => 'Size',
            'values' => [['id' => fake()->uuid(), 'value' => 'Small']],
        ],
        [
            'id' => $option3Id,
            'name' => 'Material',
            'values' => [['id' => fake()->uuid(), 'value' => 'Cotton']],
        ],
    ];

    $action = new UpsertProductOptionsAction();
    $result = $action->handle($product, array_map(ProductOptionInput::fromArray(...), $options));

    expect($result)->toHaveCount(3);

    $resultIds = $result->pluck('id')->all();
    $resultNames = $result->pluck('name')->all();

    expect($resultIds)->toContain($option1Id, $option2Id, $option3Id);
    expect($resultNames)->toContain('Color', 'Size', 'Material');

    // Verify each option has correct values
    $colorOption = $result->firstWhere('name', 'Color');
    $sizeOption = $result->firstWhere('name', 'Size');
    $materialOption = $result->firstWhere('name', 'Material');

    expect($colorOption->values)->toHaveCount(1);
    expect($colorOption->values[0]->value)->toBe('Red');
    expect($sizeOption->values)->toHaveCount(1);
    expect($sizeOption->values[0]->value)->toBe('Small');
    expect($materialOption->values)->toHaveCount(1);
    expect($materialOption->values[0]->value)->toBe('Cotton');
});

test('handles transaction rollback on database error', function () {
    $product = Product::factory()->create();

    $options = [
        [
            'id' => fake()->uuid(),
            'name' => 'Size',
            'values' => [
                ['id' => fake()->uuid(), 'value' => 'Small'],
            ],
        ],
    ];

    // Mock a database error by using an invalid product
    $invalidProduct = Product::make([
        'id' => 'invalid-id',
        'name' => 'Invalid Product',
        'url_handle' => 'invalid-product',
    ]);

    $action = new UpsertProductOptionsAction();

    expect(fn () => $action->handle($invalidProduct, array_map(ProductOptionInput::fromArray(...), $options)))
        ->toThrow(Exception::class);

    // Verify no options were created
    expect($product->options()->count())->toBe(0);
});

test('returns options with loaded values relationship', function () {
    $product = Product::factory()->create();

    $options = [
        [
            'id' => fake()->uuid(),
            'name' => 'Size',
            'values' => [
                ['id' => fake()->uuid(), 'value' => 'Small'],
                ['id' => fake()->uuid(), 'value' => 'Large'],
            ],
        ],
    ];

    $action = new UpsertProductOptionsAction();
    $result = $action->handle($product, array_map(ProductOptionInput::fromArray(...), $options));

    expect($result[0]->relationLoaded('values'))->toBeTrue();
    expect($result[0]->values)->toHaveCount(2);
});

test('handles mixed create and update operations', function () {
    $product = Product::factory()->create();
    $existingOption = $product->options()->create(['name' => 'Size']);
    $existingOption->values()->create(['id' => fake()->uuid(), 'value' => 'Small']);

    $newOptionId = fake()->uuid();
    $options = [
        [
            'id' => $existingOption->id,
            'name' => 'Size Updated',
            'values' => [
                ['id' => fake()->uuid(), 'value' => 'Large'],
            ],
        ],
        [
            'id' => $newOptionId,
            'name' => 'Color',
            'values' => [
                ['id' => fake()->uuid(), 'value' => 'Red'],
                ['id' => fake()->uuid(), 'value' => 'Blue'],
            ],
        ],
    ];

    $action = new UpsertProductOptionsAction();
    $result = $action->handle($product, array_map(ProductOptionInput::fromArray(...), $options));

    expect($result)->toHaveCount(2);
    expect($product->options()->count())->toBe(2);

    $updatedOption = $result->firstWhere('id', $existingOption->id);
    $newOption = $result->firstWhere('id', $newOptionId);

    expect($updatedOption->name)->toBe('Size Updated');
    expect($updatedOption->values)->toHaveCount(1);
    expect($newOption->name)->toBe('Color');
    expect($newOption->values)->toHaveCount(2);
});
