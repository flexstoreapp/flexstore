<?php

declare(strict_types=1);

use App\Actions\UpdateProductAction;
use App\DTOs\UpdateProductInput;
use App\Enums\WeightUnit;
use App\Models\Category;
use App\Models\Product;

covers(UpdateProductAction::class, UpdateProductInput::class);

uses()->group('actions', 'product');

beforeEach(function () {
    $this->product = Product::factory()->create([
        'title' => 'Test Product',
        'url_handle' => 'test-product',
        'sku' => 'TEST-SKU-123',
        'description' => 'Test product description',
        'category_id' => Category::factory()->create()->id,
        'price' => '99.9900',
        'compare_at_price' => '14.0000',
        'cost_per_item' => 50,
        'barcode' => 'TEST-BARCODE-123',
        'track_stock' => true,
        'stock' => 10,
        'in_stock' => true,
        'is_tax_exempt' => false,
        'is_active' => true,

        'weight' => '5',
        'weight_unit' => WeightUnit::Kg,
        'seo_title' => 'Test Product',
        'seo_description' => 'Test product description',
    ]);
});

test('updates a product with only specified fields', function () {
    $productData = [
        'title' => 'Updated Product',
        'price' => '149.9900',
    ];

    $action = app(UpdateProductAction::class);
    $result = $action->handle($this->product, UpdateProductInput::fromArray($productData));

    expect($result->title)->toBe('Updated Product')
        ->and($result->url_handle)->toBe('test-product') // unchanged
        ->and($result->sku)->toBe('TEST-SKU-123') // unchanged
        ->and($result->description)->toBe('Test product description') // unchanged
        ->and($result->category_id)->toBe($this->product->category_id) // unchanged
        ->and($result->price)->toBe('149.9900')
        ->and($result->compare_at_price)->toBe('14.0000') // unchanged
        ->and($result->is_active)->toBeTrue() // unchanged
        ->and($result->in_stock)->toBeTrue(); // unchanged
});

test('updates a product with options', function () {
    $sizeOption = $this->product->options()->create(['name' => 'Size']);
    $colorOption = $this->product->options()->create(['name' => 'Color']);

    $optionsData = [
        [
            'id' => $sizeOption->id,
            'name' => 'Size',
            'values' => [
                ['id' => fake()->uuid(), 'value' => 'Small'],
                ['id' => fake()->uuid(), 'value' => 'Medium'],
                ['id' => fake()->uuid(), 'value' => 'Large'],
            ],
        ],
        [
            'id' => $colorOption->id,
            'name' => 'Color',
            'values' => [
                ['id' => fake()->uuid(), 'value' => 'Red'],
                ['id' => fake()->uuid(), 'value' => 'Blue'],
                ['id' => fake()->uuid(), 'value' => 'Green'],
            ],
        ],
    ];

    $productData = [
        'title' => 'Updated Product',
        'options' => $optionsData,
    ];

    $action = app(UpdateProductAction::class);
    $result = $action->handle($this->product, UpdateProductInput::fromArray($productData));

    $options = $result->options->sortBy('name')->values();

    expect($result->title)->toBe('Updated Product')
        ->and($result->options)->toHaveCount(2);

    expect($options[0]->name)->toBe('Color')
        ->and($options[0]->values->pluck('value')->sort()->values()->all())->toEqual(['Blue', 'Green', 'Red']);

    expect($options[1]->name)->toBe('Size')
        ->and($options[1]->values->pluck('value')->sort()->values()->all())->toEqual(['Large', 'Medium', 'Small']);
});

test('updates a product with variants', function () {
    $sizeOption = $this->product->options()->create(['name' => 'Size']);
    $smallValue = $sizeOption->values()->create(['id' => fake()->uuid(), 'value' => 'Small']);
    $mediumValue = $sizeOption->values()->create(['id' => fake()->uuid(), 'value' => 'Medium']);

    $colorOption = $this->product->options()->create(['name' => 'Color']);
    $redValue = $colorOption->values()->create(['id' => fake()->uuid(), 'value' => 'Red']);
    $blueValue = $colorOption->values()->create(['id' => fake()->uuid(), 'value' => 'Blue']);

    $variantsData = [
        [
            'title' => 'Medium / Blue',
            'sku' => 'UPDATED-SKU-MEDIUM-BLUE',
            'price' => '149.9900',
            'track_stock' => false,
            'stock' => 10,
            'in_stock' => false,
            'weight' => '5',
            'weight_unit' => WeightUnit::Kg,
            'options' => [
                ['option_id' => $sizeOption->id, 'value_id' => $mediumValue->id],
                ['option_id' => $colorOption->id, 'value_id' => $blueValue->id],
            ],
        ],
        [
            'title' => 'Small / Red',
            'sku' => 'UPDATED-SKU-SMALL-RED',
            'price' => '139.9900',
            'track_stock' => true,
            'stock' => 5,
            'in_stock' => true,
            'weight' => '1',
            'weight_unit' => WeightUnit::Kg,
            'options' => [
                ['option_id' => $sizeOption->id, 'value_id' => $smallValue->id],
                ['option_id' => $colorOption->id, 'value_id' => $redValue->id],
            ],
        ],
    ];

    $productData = [
        'title' => 'Updated Product',
        'variants' => $variantsData,
    ];

    $action = app(UpdateProductAction::class);
    $result = $action->handle($this->product, UpdateProductInput::fromArray($productData));

    $variants = $result->variants->sortBy('title')->values();

    expect($result->title)->toBe('Updated Product')
        ->and($result->variants)->toHaveCount(2);

    expect($variants[0]->title)->toBe('Medium / Blue')
        ->and($variants[0]->sku)->toBe('UPDATED-SKU-MEDIUM-BLUE')
        ->and($variants[0]->price)->toBe('149.9900')
        ->and($variants[0]->track_stock)->toBeFalse()
        ->and($variants[0]->stock)->toBeNull() // null because track_stock is false
        ->and($variants[0]->in_stock)->toBeFalse()
        ->and($variants[0]->weight)->toBe('5.00') // kept because product is physical
        ->and($variants[0]->weight_unit)->toBe(WeightUnit::Kg); // kept because product is physical

    expect($variants[1]->title)->toBe('Small / Red')
        ->and($variants[1]->sku)->toBe('UPDATED-SKU-SMALL-RED')
        ->and($variants[1]->price)->toBe('139.9900')
        ->and($variants[1]->track_stock)->toBeTrue()
        ->and($variants[1]->stock)->toBe(5)
        ->and($variants[1]->in_stock)->toBeTrue()
        ->and($variants[1]->weight)->toBe('1.00')
        ->and($variants[1]->weight_unit)->toBe(WeightUnit::Kg);
});

test('updates a product with both options and variants', function () {
    $sizeOption = $this->product->options()->create(['name' => 'Size']);
    $smallValue = $sizeOption->values()->create(['id' => fake()->uuid(), 'value' => 'Small']);
    $mediumValue = $sizeOption->values()->create(['id' => fake()->uuid(), 'value' => 'Medium']);
    $largeValue = $sizeOption->values()->create(['id' => fake()->uuid(), 'value' => 'Large']);

    $colorOption = $this->product->options()->create(['name' => 'Color']);
    $redValue = $colorOption->values()->create(['id' => fake()->uuid(), 'value' => 'Red']);
    $blueValue = $colorOption->values()->create(['id' => fake()->uuid(), 'value' => 'Blue']);
    $greenValue = $colorOption->values()->create(['id' => fake()->uuid(), 'value' => 'Green']);

    $optionsData = [
        [
            'id' => $sizeOption->id,
            'name' => 'Size',
            'values' => [
                ['id' => $smallValue->id, 'value' => 'Small'],
                ['id' => $mediumValue->id, 'value' => 'Medium'],
                ['id' => $largeValue->id, 'value' => 'Large'],
            ],
        ],
        [
            'id' => $colorOption->id,
            'name' => 'Color',
            'values' => [
                ['id' => $redValue->id, 'value' => 'Red'],
                ['id' => $blueValue->id, 'value' => 'Blue'],
                ['id' => $greenValue->id, 'value' => 'Green'],
            ],
        ],
    ];

    $variantsData = [
        [
            'title' => 'Medium / Blue',
            'sku' => 'UPDATED-SKU-MEDIUM-BLUE',
            'price' => '149.9900',
            'track_stock' => false,
            'stock' => 10,
            'in_stock' => false,
            'weight' => '5',
            'weight_unit' => WeightUnit::Kg,
            'options' => [
                ['option_id' => $sizeOption->id, 'value_id' => $mediumValue->id],
                ['option_id' => $colorOption->id, 'value_id' => $blueValue->id],
            ],
        ],
        [
            'title' => 'Small / Red',
            'sku' => 'UPDATED-SKU-SMALL-RED',
            'price' => '139.9900',
            'track_stock' => true,
            'stock' => 5,
            'in_stock' => true,
            'weight' => '1',
            'weight_unit' => WeightUnit::Kg,
            'options' => [
                ['option_id' => $sizeOption->id, 'value_id' => $smallValue->id],
                ['option_id' => $colorOption->id, 'value_id' => $redValue->id],
            ],
        ],
    ];

    $productData = [
        'title' => 'Updated Product',
        'options' => $optionsData,
        'variants' => $variantsData,
    ];

    $action = app(UpdateProductAction::class);
    $result = $action->handle($this->product, UpdateProductInput::fromArray($productData));

    $options = $result->options->sortBy('name')->values();
    $variants = $result->variants->sortBy('title')->values();

    expect($result->title)->toBe('Updated Product')
        ->and($result->options)->toHaveCount(2)
        ->and($result->variants)->toHaveCount(2);

    expect($options[0]->name)->toBe('Color')
        ->and($options[0]->values->pluck('value')->sort()->values()->all())->toEqual(['Blue', 'Green', 'Red']);

    expect($options[1]->name)->toBe('Size')
        ->and($options[1]->values->pluck('value')->sort()->values()->all())->toEqual(['Large', 'Medium', 'Small']);

    expect($variants)->toHaveCount(2)
        ->and($variants[0]->title)->toBe('Medium / Blue')
        ->and($variants[0]->sku)->toBe('UPDATED-SKU-MEDIUM-BLUE')
        ->and($variants[0]->price)->toBe('149.9900')
        ->and($variants[0]->track_stock)->toBeFalse()
        ->and($variants[0]->stock)->toBeNull() // null because track_stock is false
        ->and($variants[0]->in_stock)->toBeFalse()
        ->and($variants[0]->weight)->toBe('5.00') // kept because product is physical
        ->and($variants[0]->weight_unit)->toBe(WeightUnit::Kg); // kept because product is physical

    expect($variants[1]->title)->toBe('Small / Red')
        ->and($variants[1]->sku)->toBe('UPDATED-SKU-SMALL-RED')
        ->and($variants[1]->price)->toBe('139.9900')
        ->and($variants[1]->track_stock)->toBeTrue()
        ->and($variants[1]->stock)->toBe(5)
        ->and($variants[1]->in_stock)->toBeTrue()
        ->and($variants[1]->weight)->toBe('1.00')
        ->and($variants[1]->weight_unit)->toBe(WeightUnit::Kg);
});

test('updates option values without providing option name', function () {
    $sizeOption = $this->product->options()->create(['name' => 'Size']);
    $smallValue = $sizeOption->values()->create(['id' => fake()->uuid(), 'value' => 'Small']);

    $optionsData = [
        [
            'id' => $sizeOption->id,
            // name is intentionally not provided - should preserve existing name
            'values' => [
                ['id' => $smallValue->id, 'value' => 'Small'],
                ['id' => fake()->uuid(), 'value' => 'Medium'],
            ],
        ],
    ];

    $productData = [
        'options' => $optionsData,
    ];

    $action = app(UpdateProductAction::class);
    $result = $action->handle($this->product, UpdateProductInput::fromArray($productData));

    expect($result->options)->toHaveCount(1)
        ->and($result->options[0]->name)->toBe('Size')
        ->and($result->options[0]->values)->toHaveCount(2);
});
