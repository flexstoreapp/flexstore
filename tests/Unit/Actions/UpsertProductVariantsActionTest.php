<?php

declare(strict_types=1);

use App\Actions\SyncMediaAction;
use App\Actions\UpsertProductVariantsAction;
use App\DTOs\ProductVariantInput;
use App\Enums\WeightUnit;
use App\Models\Media;
use App\Models\Mediable;
use App\Models\Product;
use App\Models\ProductOption;
use App\Models\ProductOptionValue;
use App\Models\ProductVariant;
use App\Models\ProductVariantOption;
use Illuminate\Support\Collection;

covers(UpsertProductVariantsAction::class, ProductVariantInput::class);

uses()->group('actions', 'product');

beforeEach(function () {
    $this->product = Product::factory()->create([
        'title' => 'Test Product',
        'url_handle' => 'test-product',
    ]);

    $sizeOption = ProductOption::create([
        'id' => fake()->uuid(),
        'product_id' => $this->product->id,
        'name' => 'Size',
    ]);

    $smallValue = ProductOptionValue::create([
        'id' => fake()->uuid(),
        'product_option_id' => $sizeOption->id,
        'value' => 'Small',
    ]);

    $largeValue = ProductOptionValue::create([
        'id' => fake()->uuid(),
        'product_option_id' => $sizeOption->id,
        'value' => 'Large',
    ]);

    $colorOption = ProductOption::create([
        'id' => fake()->uuid(),
        'product_id' => $this->product->id,
        'name' => 'Color',
    ]);

    $redValue = ProductOptionValue::create([
        'id' => fake()->uuid(),
        'product_option_id' => $colorOption->id,
        'value' => 'Red',
    ]);

    $blueValue = ProductOptionValue::create([
        'id' => fake()->uuid(),
        'product_option_id' => $colorOption->id,
        'value' => 'Blue',
    ]);

    $this->sizeOption = $sizeOption;
    $this->smallValue = $smallValue;
    $this->largeValue = $largeValue;
    $this->colorOption = $colorOption;
    $this->redValue = $redValue;
    $this->blueValue = $blueValue;
});

test('creates product variants', function () {
    $media = Media::factory()->create();
    $variants = [
        [
            'id' => fake()->uuid(),
            'title' => 'Red / Small',
            'price' => '100',
            'compare_at_price' => '150.0000',
            'cost_per_item' => 50,
            'sku' => 'SKU-RED-S',
            'barcode' => 'BARCODE-RED-S',
            'track_stock' => true,
            'stock' => 10,
            'in_stock' => true,
            'weight' => '100',
            'weight_unit' => WeightUnit::G,
            'media_id' => $media->id,
            'is_default' => false,
            'options' => [
                ['option_id' => $this->sizeOption->id, 'value_id' => $this->smallValue->id],
                ['option_id' => $this->colorOption->id, 'value_id' => $this->redValue->id],
            ],
        ],
    ];

    $action = app(UpsertProductVariantsAction::class);
    $result = $action->handle($this->product, array_map(ProductVariantInput::fromArray(...), $variants));

    expect($result)->toBeInstanceOf(Collection::class)->toHaveCount(1)
        ->and($result[0])->toBeInstanceOf(ProductVariant::class);

    expect($result[0]->title)->toBe('Red / Small')
        ->and($result[0]->price)->toBe('100.0000')
        ->and($result[0]->compare_at_price)->toBe('150.0000')
        ->and($result[0]->cost_per_item)->toBe('50.0000')
        ->and($result[0]->sku)->toBe('SKU-RED-S')
        ->and($result[0]->barcode)->toBe('BARCODE-RED-S')
        ->and($result[0]->track_stock)->toBeTrue()
        ->and($result[0]->stock)->toBe(10)
        ->and($result[0]->in_stock)->toBeTrue()
        ->and($result[0]->weight)->toBe('100.00')
        ->and($result[0]->weight_unit)->toBe(WeightUnit::G)
        ->and($result[0]->media->pluck('id')->all())->toBe([$media->id])
        ->and($result[0]->is_default)->toBeFalse()
        ->and($result[0]->options)->toHaveCount(2);

    expect($result[0]->options)->toContainOnlyInstancesOf(ProductVariantOption::class)->toHaveCount(2);

    $optionIds = $result[0]->options->pluck('product_option_id')->all();
    $valueIds = $result[0]->options->pluck('product_option_value_id')->all();

    expect($optionIds)->toContain($this->colorOption->id, $this->sizeOption->id)
        ->and($valueIds)->toContain($this->redValue->id, $this->smallValue->id);
});

test('updates product variants', function () {
    $initialVariant = ProductVariant::factory()->create([
        'id' => fake()->uuid(),
        'product_id' => $this->product->id,
        'title' => 'Red / Small',
        'price' => '100',
        'sku' => 'SKU-RED-S',
    ]);

    ProductVariantOption::create([
        'product_variant_id' => $initialVariant->id,
        'product_option_id' => $this->sizeOption->id,
        'product_option_value_id' => $this->smallValue->id,
    ]);

    ProductVariantOption::create([
        'product_variant_id' => $initialVariant->id,
        'product_option_id' => $this->colorOption->id,
        'product_option_value_id' => $this->redValue->id,
    ]);

    $variants = [
        [
            'id' => $initialVariant->id,
            'title' => 'Red / Large',
            'price' => '120',
            'sku' => 'SKU-RED-L',
            'track_stock' => true,
            'in_stock' => true,
            'options' => [
                ['option_id' => $this->sizeOption->id, 'value_id' => $this->largeValue->id],
                ['option_id' => $this->colorOption->id, 'value_id' => $this->redValue->id],
            ],
        ],
    ];

    $action = app(UpsertProductVariantsAction::class);
    $result = $action->handle($this->product, array_map(ProductVariantInput::fromArray(...), $variants));

    expect($result)->toBeInstanceOf(Collection::class)->toHaveCount(1);

    expect($result[0]->title)->toBe('Red / Large')
        ->and($result[0]->price)->toBe('120.0000')
        ->and($result[0]->sku)->toBe('SKU-RED-L');

    expect($result[0]->options)->toHaveCount(2);

    $optionIds = $result[0]->options->pluck('product_option_id')->all();
    $valueIds = $result[0]->options->pluck('product_option_value_id')->all();

    expect($optionIds)->toContain($this->colorOption->id, $this->sizeOption->id)
        ->and($valueIds)->toContain($this->largeValue->id, $this->redValue->id)
        ->and($valueIds)->not->toContain($this->smallValue->id); // small value should be deleted
});

test('handles empty variants input', function () {
    ProductVariant::factory()->create([
        'id' => fake()->uuid(),
        'product_id' => $this->product->id,
        'title' => 'Red / Small',
        'price' => '100',
        'sku' => 'SKU-RED-S',
    ]);

    $action = app(UpsertProductVariantsAction::class);
    $result = $action->handle($this->product, array_map(ProductVariantInput::fromArray(...), []));

    expect($result)->toBeInstanceOf(Collection::class)->toBeEmpty()
        ->and($this->product->variants()->count())->toBe(0);
});

test('sets default variant correctly when is_default is true', function () {
    $variants = [
        [
            'id' => fake()->uuid(),
            'title' => 'Red / Small',
            'price' => '100',
            'sku' => 'SKU-RED-S',
            'track_stock' => true,
            'stock' => 10,
            'in_stock' => true,
            'weight' => '100',
            'weight_unit' => WeightUnit::G,
            'is_default' => false,
            'options' => [
                ['option_id' => $this->sizeOption->id, 'value_id' => $this->smallValue->id],
                ['option_id' => $this->colorOption->id, 'value_id' => $this->redValue->id],
            ],
        ],
        [
            'id' => fake()->uuid(),
            'title' => 'Blue / Large',
            'price' => '120',
            'sku' => 'SKU-BLUE-L',
            'track_stock' => true,
            'stock' => 15,
            'in_stock' => true,
            'weight' => '120',
            'weight_unit' => WeightUnit::G,
            'is_default' => true,
            'options' => [
                ['option_id' => $this->sizeOption->id, 'value_id' => $this->largeValue->id],
                ['option_id' => $this->colorOption->id, 'value_id' => $this->blueValue->id],
            ],
        ],
    ];

    $action = app(UpsertProductVariantsAction::class);
    $result = $action->handle($this->product, array_map(ProductVariantInput::fromArray(...), $variants));

    expect($result)->toHaveCount(2);

    $defaultVariant = $result->where('is_default', true)->first();
    $nonDefaultVariant = $result->where('is_default', false)->first();

    expect($defaultVariant)->not->toBeNull()
        ->and($defaultVariant->title)->toBe('Blue / Large')
        ->and($defaultVariant->is_default)->toBeTrue();

    expect($nonDefaultVariant)->not->toBeNull()
        ->and($nonDefaultVariant->title)->toBe('Red / Small')
        ->and($nonDefaultVariant->is_default)->toBeFalse();
});

test('keeps every variant non default when none is flagged', function () {
    $variants = [
        [
            'id' => fake()->uuid(),
            'title' => 'Red / Small',
            'price' => '100',
            'sku' => 'SKU-RED-S',
            'track_stock' => true,
            'stock' => 10,
            'in_stock' => true,
            'weight' => '100',
            'weight_unit' => WeightUnit::G,
            'is_default' => false,
            'options' => [
                ['option_id' => $this->sizeOption->id, 'value_id' => $this->smallValue->id],
                ['option_id' => $this->colorOption->id, 'value_id' => $this->redValue->id],
            ],
        ],
        [
            'id' => fake()->uuid(),
            'title' => 'Blue / Large',
            'price' => '120',
            'sku' => 'SKU-BLUE-L',
            'track_stock' => true,
            'stock' => 15,
            'in_stock' => true,
            'weight' => '120',
            'weight_unit' => WeightUnit::G,
            'is_default' => false,
            'options' => [
                ['option_id' => $this->sizeOption->id, 'value_id' => $this->largeValue->id],
                ['option_id' => $this->colorOption->id, 'value_id' => $this->blueValue->id],
            ],
        ],
    ];

    $action = app(UpsertProductVariantsAction::class);
    $result = $action->handle($this->product, array_map(ProductVariantInput::fromArray(...), $variants));

    expect($result)->toHaveCount(2)
        ->and($result->where('is_default', true))->toBeEmpty();
});

test('ensures only one variant is default when multiple variants have is_default true', function () {
    $variants = [
        [
            'id' => fake()->uuid(),
            'title' => 'Red / Small',
            'price' => '100',
            'sku' => 'SKU-RED-S',
            'track_stock' => true,
            'stock' => 10,
            'in_stock' => true,
            'weight' => '100',
            'weight_unit' => WeightUnit::G,
            'is_default' => true,
            'options' => [
                ['option_id' => $this->sizeOption->id, 'value_id' => $this->smallValue->id],
                ['option_id' => $this->colorOption->id, 'value_id' => $this->redValue->id],
            ],
        ],
        [
            'id' => fake()->uuid(),
            'title' => 'Blue / Large',
            'price' => '120',
            'sku' => 'SKU-BLUE-L',
            'track_stock' => true,
            'stock' => 15,
            'in_stock' => true,
            'weight' => '120',
            'weight_unit' => WeightUnit::G,
            'is_default' => true,
            'options' => [
                ['option_id' => $this->sizeOption->id, 'value_id' => $this->largeValue->id],
                ['option_id' => $this->colorOption->id, 'value_id' => $this->blueValue->id],
            ],
        ],
    ];

    $action = app(UpsertProductVariantsAction::class);
    $result = $action->handle($this->product, array_map(ProductVariantInput::fromArray(...), $variants));

    expect($result)->toHaveCount(2);

    $defaultVariants = $result->where('is_default', true);
    expect($defaultVariants)->toHaveCount(1)
        ->and($defaultVariants->first()->title)->toBe('Red / Small');
});

test('removes orphaned variants when updating with fewer variants', function () {
    $variant1 = ProductVariant::factory()->create([
        'id' => fake()->uuid(),
        'product_id' => $this->product->id,
        'title' => 'Red / Small',
        'price' => '100',
        'sku' => 'SKU-RED-S',
    ]);

    $variant2 = ProductVariant::factory()->create([
        'id' => fake()->uuid(),
        'product_id' => $this->product->id,
        'title' => 'Blue / Large',
        'price' => '120',
        'sku' => 'SKU-BLUE-L',
    ]);

    $variant3 = ProductVariant::factory()->create([
        'id' => fake()->uuid(),
        'product_id' => $this->product->id,
        'title' => 'Green / Medium',
        'price' => '110',
        'sku' => 'SKU-GREEN-M',
    ]);

    $variants = [
        [
            'id' => $variant1->id,
            'title' => 'Red / Small Updated',
            'price' => '105',
            'sku' => 'SKU-RED-S-UPDATED',
            'track_stock' => true,
            'in_stock' => true,
            'options' => [
                ['option_id' => $this->sizeOption->id, 'value_id' => $this->smallValue->id],
                ['option_id' => $this->colorOption->id, 'value_id' => $this->redValue->id],
            ],
        ],
    ];

    $action = app(UpsertProductVariantsAction::class);
    $result = $action->handle($this->product, array_map(ProductVariantInput::fromArray(...), $variants));

    expect($result)->toHaveCount(1);
    expect($this->product->variants()->count())->toBe(1);
    expect($result[0]->title)->toBe('Red / Small Updated');

    expect(ProductVariant::find($variant2->id))->toBeNull();
    expect(ProductVariant::find($variant3->id))->toBeNull();
});

test('removes orphaned variant options when updating variant options', function () {
    $variant = ProductVariant::factory()->create([
        'id' => fake()->uuid(),
        'product_id' => $this->product->id,
        'title' => 'Red / Small',
        'price' => '100',
        'sku' => 'SKU-RED-S',
    ]);

    $option1 = ProductVariantOption::create([
        'product_variant_id' => $variant->id,
        'product_option_id' => $this->sizeOption->id,
        'product_option_value_id' => $this->smallValue->id,
    ]);

    $option2 = ProductVariantOption::create([
        'product_variant_id' => $variant->id,
        'product_option_id' => $this->colorOption->id,
        'product_option_value_id' => $this->redValue->id,
    ]);

    $variants = [
        [
            'id' => $variant->id,
            'title' => 'Red / Small',
            'price' => '100',
            'sku' => 'SKU-RED-S',
            'track_stock' => true,
            'in_stock' => true,
            'options' => [
                ['option_id' => $this->sizeOption->id, 'value_id' => $this->smallValue->id],
            ],
        ],
    ];

    $action = app(UpsertProductVariantsAction::class);
    $result = $action->handle($this->product, array_map(ProductVariantInput::fromArray(...), $variants));

    expect($result[0]->options)->toHaveCount(1);

    $actualOptionId = $result[0]->options[0]->product_option_id;
    $expectedOptionId = $this->sizeOption->id;

    expect($actualOptionId)->toBe($expectedOptionId);

    expect(ProductVariantOption::find($option2->id))->toBeNull();
});

test('creates multiple variants with different configurations', function () {
    $variants = [
        [
            'id' => fake()->uuid(),
            'title' => 'Red / Small',
            'price' => '100',
            'sku' => 'SKU-RED-S',
            'track_stock' => true,
            'stock' => 10,
            'in_stock' => true,
            'is_default' => true,
            'options' => [
                ['option_id' => $this->sizeOption->id, 'value_id' => $this->smallValue->id],
                ['option_id' => $this->colorOption->id, 'value_id' => $this->redValue->id],
            ],
        ],
        [
            'id' => fake()->uuid(),
            'title' => 'Blue / Large',
            'price' => '120',
            'sku' => 'SKU-BLUE-L',
            'track_stock' => false,
            'stock' => null,
            'in_stock' => true,
            'is_default' => false,
            'options' => [
                ['option_id' => $this->sizeOption->id, 'value_id' => $this->largeValue->id],
                ['option_id' => $this->colorOption->id, 'value_id' => $this->blueValue->id],
            ],
        ],
        [
            'id' => fake()->uuid(),
            'title' => 'Red / Large',
            'price' => '115',
            'sku' => 'SKU-RED-L',
            'track_stock' => true,
            'stock' => 5,
            'in_stock' => true,
            'is_default' => false,
            'options' => [
                ['option_id' => $this->sizeOption->id, 'value_id' => $this->largeValue->id],
                ['option_id' => $this->colorOption->id, 'value_id' => $this->redValue->id],
            ],
        ],
    ];

    $action = app(UpsertProductVariantsAction::class);
    $result = $action->handle($this->product, array_map(ProductVariantInput::fromArray(...), $variants));

    expect($result)->toHaveCount(3);
    expect($this->product->variants()->count())->toBe(3);

    $defaultVariants = $result->where('is_default', true);
    expect($defaultVariants)->toHaveCount(1);
    expect($defaultVariants->first()->title)->toBe('Red / Small');
});

test('handles variant without options', function () {
    $variants = [
        [
            'id' => fake()->uuid(),
            'title' => 'Default Variant',
            'price' => '100',
            'sku' => 'SKU-DEFAULT',
            'track_stock' => true,
            'in_stock' => true,
            'options' => [],
        ],
    ];

    $action = app(UpsertProductVariantsAction::class);
    $result = $action->handle($this->product, array_map(ProductVariantInput::fromArray(...), $variants));

    expect($result)->toHaveCount(1);
    expect($result[0]->title)->toBe('Default Variant');
    expect($result[0]->options)->toHaveCount(0);
});

test('handles variant with missing options key', function () {
    $variants = [
        [
            'id' => fake()->uuid(),
            'title' => 'Default Variant',
            'price' => '100',
            'sku' => 'SKU-DEFAULT',
            'track_stock' => true,
            'in_stock' => true,
        ],
    ];

    $action = app(UpsertProductVariantsAction::class);
    $result = $action->handle($this->product, array_map(ProductVariantInput::fromArray(...), $variants));

    expect($result)->toHaveCount(1);
    expect($result[0]->title)->toBe('Default Variant');
    expect($result[0]->options)->toHaveCount(0);
});

test('handles variant with null values for optional fields', function () {
    $variants = [
        [
            'id' => fake()->uuid(),
            'title' => 'Minimal Variant',
            'price' => '100',
            'sku' => 'SKU-MINIMAL',
            'compare_at_price' => null,
            'cost_per_item' => null,
            'barcode' => null,
            'track_stock' => false,
            'stock' => null,
            'in_stock' => true,
            'weight' => null,
            'media_id' => null,
            'options' => [],
        ],
    ];

    $action = app(UpsertProductVariantsAction::class);
    $result = $action->handle($this->product, array_map(ProductVariantInput::fromArray(...), $variants));

    expect($result)->toHaveCount(1);
    expect($result[0]->title)->toBe('Minimal Variant');
    expect($result[0]->compare_at_price)->toBeNull();
    expect($result[0]->cost_per_item)->toBeNull();
    expect($result[0]->barcode)->toBeNull();
    expect($result[0]->stock)->toBeNull();
    expect($result[0]->weight)->toBeNull();
    expect($result[0]->media)->toBeEmpty();
});

test('creates multiple variants with correct data', function () {
    $variant1Id = fake()->uuid();
    $variant2Id = fake()->uuid();
    $variant3Id = fake()->uuid();

    $variants = [
        [
            'id' => $variant1Id,
            'title' => 'Blue / Large',
            'price' => '120',
            'sku' => 'SKU-BLUE-L',
            'track_stock' => true,
            'in_stock' => true,
            'options' => [
                ['option_id' => $this->sizeOption->id, 'value_id' => $this->largeValue->id],
                ['option_id' => $this->colorOption->id, 'value_id' => $this->blueValue->id],
            ],
        ],
        [
            'id' => $variant2Id,
            'title' => 'Red / Small',
            'price' => '100',
            'sku' => 'SKU-RED-S',
            'track_stock' => true,
            'in_stock' => true,
            'options' => [
                ['option_id' => $this->sizeOption->id, 'value_id' => $this->smallValue->id],
                ['option_id' => $this->colorOption->id, 'value_id' => $this->redValue->id],
            ],
        ],
        [
            'id' => $variant3Id,
            'title' => 'Red / Large',
            'price' => '115',
            'sku' => 'SKU-RED-L',
            'track_stock' => true,
            'in_stock' => true,
            'options' => [
                ['option_id' => $this->sizeOption->id, 'value_id' => $this->largeValue->id],
                ['option_id' => $this->colorOption->id, 'value_id' => $this->redValue->id],
            ],
        ],
    ];

    $action = app(UpsertProductVariantsAction::class);
    $result = $action->handle($this->product, array_map(ProductVariantInput::fromArray(...), $variants));

    expect($result)->toHaveCount(3);

    $resultIds = $result->pluck('id')->all();
    expect($resultIds)->toContain($variant1Id, $variant2Id, $variant3Id);

    $resultTitles = $result->pluck('title')->all();
    expect($resultTitles)->toContain('Blue / Large', 'Red / Small', 'Red / Large');
});

test('handles transaction rollback on database error', function () {
    $variants = [
        [
            'id' => fake()->uuid(),
            'title' => 'Test Variant',
            'price' => '100',
            'sku' => 'SKU-TEST',
            'track_stock' => true,
            'in_stock' => true,
            'options' => [
                ['option_id' => $this->sizeOption->id, 'value_id' => $this->smallValue->id],
            ],
        ],
    ];

    // Mock a database error by using a product that doesn't exist in database
    $invalidProduct = Product::make([
        'id' => 'invalid-id',
        'name' => 'Invalid Product',
        'url_handle' => 'invalid-product',
    ]);

    $action = app(UpsertProductVariantsAction::class);

    expect(fn () => $action->handle($invalidProduct, array_map(ProductVariantInput::fromArray(...), $variants)))
        ->toThrow(Exception::class);

    expect($this->product->variants()->count())->toBe(0);
});

test('returns variants with loaded options relationship', function () {
    $variants = [
        [
            'id' => fake()->uuid(),
            'title' => 'Red / Small',
            'price' => '100',
            'sku' => 'SKU-RED-S',
            'track_stock' => true,
            'in_stock' => true,
            'options' => [
                ['option_id' => $this->sizeOption->id, 'value_id' => $this->smallValue->id],
                ['option_id' => $this->colorOption->id, 'value_id' => $this->redValue->id],
            ],
        ],
    ];

    $action = app(UpsertProductVariantsAction::class);
    $result = $action->handle($this->product, array_map(ProductVariantInput::fromArray(...), $variants));

    expect($result[0]->relationLoaded('options'))->toBeTrue();
    expect($result[0]->options)->toHaveCount(2);
});

test('handles mixed create and update operations', function () {
    $existingVariant = ProductVariant::factory()->create([
        'id' => fake()->uuid(),
        'product_id' => $this->product->id,
        'title' => 'Red / Small',
        'price' => '100',
        'sku' => 'SKU-RED-S',
    ]);

    ProductVariantOption::create([
        'product_variant_id' => $existingVariant->id,
        'product_option_id' => $this->sizeOption->id,
        'product_option_value_id' => $this->smallValue->id,
    ]);

    $newVariantId = fake()->uuid();
    $variants = [
        [
            'id' => $existingVariant->id,
            'title' => 'Red / Small Updated',
            'price' => '105',
            'sku' => 'SKU-RED-S-UPDATED',
            'track_stock' => true,
            'in_stock' => true,
            'options' => [
                ['option_id' => $this->sizeOption->id, 'value_id' => $this->smallValue->id],
                ['option_id' => $this->colorOption->id, 'value_id' => $this->redValue->id],
            ],
        ],
        [
            'id' => $newVariantId,
            'title' => 'Blue / Large',
            'price' => '120',
            'sku' => 'SKU-BLUE-L',
            'track_stock' => true,
            'in_stock' => true,
            'options' => [
                ['option_id' => $this->sizeOption->id, 'value_id' => $this->largeValue->id],
                ['option_id' => $this->colorOption->id, 'value_id' => $this->blueValue->id],
            ],
        ],
    ];

    $action = app(UpsertProductVariantsAction::class);
    $result = $action->handle($this->product, array_map(ProductVariantInput::fromArray(...), $variants));

    expect($result)->toHaveCount(2);
    expect($this->product->variants()->count())->toBe(2);

    $updatedVariant = $result->firstWhere('id', $existingVariant->id);
    $newVariant = $result->firstWhere('id', $newVariantId);

    expect($updatedVariant->title)->toBe('Red / Small Updated');
    expect($updatedVariant->price)->toBe('105.0000');
    expect($updatedVariant->options)->toHaveCount(2);
    expect($newVariant->title)->toBe('Blue / Large');
    expect($newVariant->price)->toBe('120.0000');
    expect($newVariant->options)->toHaveCount(2);
});

test('attaches media to the variant', function () {
    $media = Media::factory()->create();
    $variants = [
        [
            'id' => fake()->uuid(),
            'title' => 'Red / Small',
            'price' => '100',
            'sku' => 'SKU-RED-S',
            'track_stock' => true,
            'in_stock' => true,
            'media_id' => $media->id,
            'options' => [],
        ],
    ];

    $action = app(UpsertProductVariantsAction::class);
    $result = $action->handle($this->product, array_map(ProductVariantInput::fromArray(...), $variants));

    expect($result[0]->media->pluck('id')->all())->toBe([$media->id]);
});

test('handles weight unit enum correctly', function () {
    $variants = [
        [
            'id' => fake()->uuid(),
            'title' => 'Heavy Item',
            'price' => '100',
            'sku' => 'SKU-HEAVY',
            'track_stock' => true,
            'in_stock' => true,
            'weight' => '2500',
            'weight_unit' => WeightUnit::Kg,
            'options' => [],
        ],
    ];

    $action = app(UpsertProductVariantsAction::class);
    $result = $action->handle($this->product, array_map(ProductVariantInput::fromArray(...), $variants));

    expect($result[0]->weight)->toBe('2500.00');
    expect($result[0]->weight_unit)->toBe(WeightUnit::Kg);
});

test('clears the default flag of an existing variant when it is no longer the default', function () {
    $existingVariant = ProductVariant::factory()->create([
        'id' => fake()->uuid(),
        'product_id' => $this->product->id,
        'title' => 'Existing Default',
        'price' => '100',
        'sku' => 'SKU-EXISTING',
        'is_default' => true,
    ]);

    $variants = [
        [
            'id' => $existingVariant->id,
            'title' => 'Existing Default Updated',
            'price' => '105',
            'sku' => 'SKU-EXISTING-UPDATED',
            'track_stock' => true,
            'in_stock' => true,
            'is_default' => false, // Explicitly set to false
            'options' => [],
        ],
        [
            'id' => fake()->uuid(),
            'title' => 'New Variant',
            'price' => '120',
            'sku' => 'SKU-NEW',
            'track_stock' => true,
            'in_stock' => true,
            'is_default' => false,
            'options' => [],
        ],
    ];

    $action = app(UpsertProductVariantsAction::class);
    $result = $action->handle($this->product, array_map(ProductVariantInput::fromArray(...), $variants));

    expect($result)->toHaveCount(2)
        ->and($result->where('is_default', true))->toBeEmpty();
});

test('detaches media of orphaned variants when updating with fewer variants', function () {
    $orphanMedia = Media::factory()->create();

    $orphanedVariant = ProductVariant::factory()->create([
        'id' => fake()->uuid(),
        'product_id' => $this->product->id,
        'title' => 'Red / Small',
        'price' => '100',
        'sku' => 'SKU-RED-S',
    ]);

    (new SyncMediaAction())->handle($orphanedVariant, [$orphanMedia->id]);

    expect(Mediable::query()->count())->toBe(1);

    $variants = [
        [
            'id' => fake()->uuid(),
            'title' => 'Blue / Large',
            'price' => '120',
            'sku' => 'SKU-BLUE-L',
            'track_stock' => true,
            'in_stock' => true,
            'options' => [
                ['option_id' => $this->sizeOption->id, 'value_id' => $this->largeValue->id],
                ['option_id' => $this->colorOption->id, 'value_id' => $this->blueValue->id],
            ],
        ],
    ];

    $action = app(UpsertProductVariantsAction::class);
    $result = $action->handle($this->product, array_map(ProductVariantInput::fromArray(...), $variants));

    expect($result)->toHaveCount(1);
    expect($result[0]->title)->toBe('Blue / Large');
    expect($this->product->variants()->count())->toBe(1);

    expect(ProductVariant::find($orphanedVariant->id))->toBeNull();
});
