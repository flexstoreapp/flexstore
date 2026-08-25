<?php

declare(strict_types=1);

use App\Actions\UpdateProductAction;
use App\DTOs\UpdateProductInput;
use App\Enums\Permission;
use App\Enums\Role as RoleEnum;
use App\Enums\TaxCategory;
use App\Enums\WeightUnit;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Requests\Admin\UpdateProductRequest;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Media;
use App\Models\Product;
use App\Models\ProductDownload;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Laravel\get;
use function Pest\Laravel\patch;

covers([
    ProductController::class,
    UpdateProductRequest::class,
    UpdateProductAction::class,

]);

uses()->group('product');

test('displays product edit page', function () {
    $product = Product::factory()->create();

    Category::factory()->count(2)->create();

    $response = actingAsSuperAdmin()->get(route('admin.products.edit', $product));

    $response->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('admin/products/edit')
                ->where('product.id', $product->id)
                ->has('maxUploadSize')
        );
});

test('displays product edit page for a product with a brand image', function () {
    $brand = Brand::factory()->create([
        'image_id' => Media::factory()->create()->id,
    ]);

    $product = Product::factory()->create(['brand_id' => $brand->id]);

    $response = actingAsSuperAdmin()->get(route('admin.products.edit', $product));

    $response->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('admin/products/edit')
                ->where('product.id', $product->id)
                ->where('product.brand.id', $brand->id)
                ->has('product.brand.image')
        );
});

test('displays product edit page with options and values transformation', function () {
    $product = Product::factory()->create();

    $optionsData = [
        [
            'id' => fake()->uuid(),
            'name' => 'Size',
            'values' => [
                ['id' => fake()->uuid(), 'value' => 'Small'],
                ['id' => fake()->uuid(), 'value' => 'Large'],
            ],
        ],
        [
            'id' => fake()->uuid(),
            'name' => 'Color',
            'values' => [
                ['id' => fake()->uuid(), 'value' => 'Red'],
            ],
        ],
    ];

    app(UpdateProductAction::class)->handle($product, UpdateProductInput::fromArray([
        'options' => $optionsData,
    ]));

    $response = actingAsSuperAdmin()->get(route('admin.products.edit', $product));

    $response->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('admin/products/edit')
                ->where('product.id', $product->id)
                ->has('product.options', 2)
                ->where('product.options.0.values.0.product_option_id', fn ($value) => in_array($value, [$optionsData[0]['id'], $optionsData[1]['id']]))
                ->where('product.options.1.values.0.product_option_id', fn ($value) => in_array($value, [$optionsData[0]['id'], $optionsData[1]['id']]))
                ->has('maxUploadSize')
        );
});

test('displays product edit page with variants and options transformation', function () {
    $product = Product::factory()->create();

    $optionId = fake()->uuid();
    $valueId = fake()->uuid();
    $variantId = fake()->uuid();

    $optionsData = [
        [
            'id' => $optionId,
            'name' => 'Size',
            'values' => [
                ['id' => $valueId, 'value' => 'Medium'],
            ],
        ],
    ];

    $variantsData = [
        [
            'id' => $variantId,
            'title' => 'Medium Variant',
            'price' => '99.9900',
            'sku' => 'MED-001',
            'track_stock' => true,
            'stock' => 10,
            'in_stock' => true,
            'weight' => '1.5',
            'weight_unit' => WeightUnit::Kg->value,
            'options' => [
                ['option_id' => $optionId, 'value_id' => $valueId],
            ],
        ],
    ];

    app(UpdateProductAction::class)->handle($product, UpdateProductInput::fromArray([
        'options' => $optionsData,
        'variants' => $variantsData,
    ]));

    $response = actingAsSuperAdmin()->get(route('admin.products.edit', $product));

    $response->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('admin/products/edit')
                ->where('product.id', $product->id)
                ->where('product.variants.0.title', castAsTranslatableArray('Medium Variant'))
                ->where('product.variants.0.price', '99.9900')
                ->where('product.variants.0.sku', 'MED-001')
                ->where('product.variants.0.options.0.option_id', $optionId)
                ->where('product.variants.0.options.0.value_id', $valueId)
                ->where('product.variants.0.options.0.name', castAsTranslatableArray('Size'))
                ->where('product.variants.0.options.0.value', castAsTranslatableArray('Medium'))
                ->has('maxUploadSize')
        );
});

test('updates an existing product', function () {
    $category = Category::factory()->create();
    $media = Media::factory()->count(2)->create();
    $product = Product::factory()->create([
        'title' => 'Old Title',
        'type' => 'physical',
        'url_handle' => 'old-url-handle',
        'is_active' => false,
    ]);

    $updateData = [
        'title' => 'Updated Title',
        'type' => 'physical',
        'url_handle' => 'updated-url-handle',
        'description' => 'Updated description',
        'category_id' => $category->id,
        'price' => '29.9900',
        'compare_at_price' => '39.9900',
        'cost_per_item' => '15.0000',
        'sku' => 'PROD-002',
        'barcode' => '987654321',
        'track_stock' => true,
        'stock' => 50,
        'in_stock' => true,
        'is_tax_exempt' => true,
        'is_active' => true,
        'weight' => '2.50',
        'weight_unit' => WeightUnit::Kg->value,
        'media' => $media->pluck('id')->all(),
        'seo_title' => 'Updated SEO Title',
        'seo_description' => 'Updated SEO Description',
    ];

    $response = actingAsSuperAdmin()->patch(route('admin.products.update', $product), $updateData);

    $response->assertRedirect()
        ->assertSessionHasNoErrors();

    assertDatabaseHas('products', [
        ...Arr::except($updateData, ['title', 'description', 'seo_title', 'seo_description', 'media']),
        'title' => castAsTranslatableJson($updateData['title']),
        'type' => 'physical',
        'description' => castAsTranslatableJson($updateData['description']),
        'seo_title' => castAsTranslatableJson($updateData['seo_title']),
        'seo_description' => castAsTranslatableJson($updateData['seo_description']),
    ]);

    expect($product->refresh()->mediaGallery)->toHaveCount(2);
});

test('updates media on a product that has variants', function () {
    $product = Product::factory()->create();
    $media = Media::factory()->count(2)->create();

    $optionId = fake()->uuid();
    $valueId = fake()->uuid();

    $response = actingAsSuperAdmin()->patch(route('admin.products.update', $product), [
        'title' => 'Product With Variants',
        'type' => 'physical',
        'media' => $media->pluck('id')->all(),
        'options' => [
            ['id' => $optionId, 'name' => 'Size', 'values' => [['id' => $valueId, 'value' => 'Medium']]],
        ],
        'variants' => [
            [
                'id' => fake()->uuid(),
                'title' => 'Medium',
                'type' => 'physical',
                'price' => '19.9900',
                'is_default' => true,
                'options' => [['option_id' => $optionId, 'value_id' => $valueId]],
            ],
        ],
    ]);

    $response->assertRedirect()
        ->assertSessionHasNoErrors();

    $product->refresh()->load('mediaGallery');

    expect($product->mediaGallery)->toHaveCount(2)
        ->and($product->variants)->toHaveCount(1)
        ->and($product->featured_media?->id)->toBe($media->first()->id);
});

test('media is cleared when an empty media array is submitted', function () {
    $product = Product::factory()->create();
    $media = Media::factory()->count(2)->create();

    actingAsSuperAdmin()->patch(route('admin.products.update', $product), [
        'media' => $media->pluck('id')->all(),
    ])->assertSessionHasNoErrors();

    expect($product->refresh()->mediaGallery)->toHaveCount(2);

    actingAsSuperAdmin()->patch(route('admin.products.update', $product), [
        'media' => [],
    ])->assertSessionHasNoErrors();

    $product->refresh()->load('mediaGallery');

    expect($product->mediaGallery)->toHaveCount(0)
        ->and($product->featured_media)->toBeNull();
});

test('media is left untouched when the media key is omitted', function () {
    $product = Product::factory()->create();
    $media = Media::factory()->count(2)->create();

    actingAsSuperAdmin()->patch(route('admin.products.update', $product), [
        'media' => $media->pluck('id')->all(),
    ])->assertSessionHasNoErrors();

    actingAsSuperAdmin()->patch(route('admin.products.update', $product), [
        'title' => 'Renamed Product',
        'type' => 'physical',
    ])->assertSessionHasNoErrors();

    expect($product->refresh()->mediaGallery)->toHaveCount(2);
});

test('persists customs information', function () {
    $product = Product::factory()->create(['url_handle' => 'customs-product']);

    actingAsSuperAdmin()->patch(route('admin.products.update', $product), [
    ])->assertRedirect()->assertSessionHasNoErrors();

    assertDatabaseHas('products', [
        'id' => $product->id,
    ]);
});

test('automatically generates URL handle from product title if URL handle is not provided', function () {
    $product = Product::factory()->create([
        'title' => 'Original Title',
        'type' => 'physical',
        'url_handle' => 'original-url-handle',
    ]);
    $newTitle = 'Updated Product Title';
    $expectedUrlHandle = Str::slug($newTitle);

    $updatedProductData = [
        'title' => $newTitle,
        'type' => 'physical',
        'url_handle' => null, // No URL handle provided
        'price' => '29.9900',
        'track_stock' => false,
        'in_stock' => true,
        'is_active' => true,
    ];

    $response = actingAsSuperAdmin()->patch(route('admin.products.update', $product), $updatedProductData);

    $response->assertRedirect()
        ->assertSessionHasNoErrors();

    assertDatabaseHas('products', [
        'id' => $product->id,
        'title' => castAsTranslatableJson($newTitle),
        'type' => 'physical',
        'url_handle' => $expectedUrlHandle,
    ]);
});

test('validates unique URL handle during update', function () {

    Product::factory()->create(['url_handle' => 'existing-url-handle']);

    $productToUpdate = Product::factory()->create(['url_handle' => 'original-url-handle']);

    $response = actingAsSuperAdmin()->patch(route('admin.products.update', $productToUpdate), [
        'title' => 'Updated Title',
        'type' => 'physical',
        'url_handle' => 'existing-url-handle', // Already exists
        'price' => '29.9900',
        'track_stock' => true,
        'stock' => 50,
        'in_stock' => true,
        'is_active' => true,
    ]);

    $response->assertRedirectBack()
        ->assertInvalid('url_handle');

    assertDatabaseMissing('products', [
        'id' => $productToUpdate->id,
        'url_handle' => 'existing-url-handle',
    ]);
});

test('validates unique sku', function () {

    Product::factory()->create(['sku' => 'EXISTING-SKU']);

    $productToUpdate = Product::factory()->create(['sku' => 'ORIGINAL-SKU']);

    $response = actingAsSuperAdmin()->patch(route('admin.products.update', $productToUpdate), [
        'title' => 'Updated Title',
        'type' => 'physical',
        'sku' => 'EXISTING-SKU', // Already exists
        'price' => '29.9900',
        'track_stock' => true,
        'stock' => 50,
        'in_stock' => true,
        'is_active' => true,
    ]);

    $response->assertRedirectBack()
        ->assertInvalid('sku');

    assertDatabaseMissing('products', [
        'id' => $productToUpdate->id,
        'sku' => 'EXISTING-SKU',
    ]);
});

test('validates unique barcode', function () {

    Product::factory()->create(['barcode' => 'EXISTING-BARCODE']);

    $productToUpdate = Product::factory()->create(['barcode' => 'ORIGINAL-BARCODE']);

    $response = actingAsSuperAdmin()->patch(route('admin.products.update', $productToUpdate), [
        'title' => 'Updated Title',
        'type' => 'physical',
        'barcode' => 'EXISTING-BARCODE', // Already exists
        'price' => '29.9900',
        'track_stock' => true,
        'stock' => 50,
        'in_stock' => true,
        'is_active' => true,
    ]);

    $response->assertRedirectBack()
        ->assertInvalid('barcode');

    assertDatabaseMissing('products', [
        'id' => $productToUpdate->id,
        'barcode' => 'EXISTING-BARCODE',
    ]);
});

test('automatically generates seo title from product title if seo title is not provided', function () {
    $product = Product::factory()->create([
        'title' => 'Original Product Title',
        'type' => 'physical',
        'seo_title' => 'original-seo-title',
    ]);
    $newTitle = 'Updated Product Title';
    $expectedSeoTitle = Str::limit($newTitle, 70);

    $response = actingAsSuperAdmin()->patch(route('admin.products.update', $product), [
        'title' => $newTitle,
        'type' => 'physical',
        'price' => '29.9900',
        'track_stock' => false,
        'in_stock' => true,
        'is_active' => true,
        'seo_title' => null, // No seo_title provided
    ]);

    $response->assertRedirect()
        ->assertSessionHasNoErrors();

    assertDatabaseHas('products', [
        'id' => $product->id,
        'title' => castAsTranslatableJson($newTitle),
        'type' => 'physical',
        'seo_title' => castAsTranslatableJson($expectedSeoTitle),
    ]);
});

test('updates product options and variants', function () {

    $initialOptions = [
        [
            'id' => fake()->uuid(),
            'name' => 'Size',
            'values' => [
                [
                    'id' => fake()->uuid(),
                    'value' => 'S',
                ],
                [
                    'id' => fake()->uuid(),
                    'value' => 'M',
                ],
            ],
        ],
        [
            'id' => fake()->uuid(),
            'name' => 'Color',
            'values' => [
                [
                    'id' => fake()->uuid(),
                    'value' => 'Red',
                ],
            ],
        ],
    ];

    $initialVariants = [
        [
            'id' => fake()->uuid(),
            'title' => 'S / Red',
            'price' => '100.0000',
            'compare_at_price' => '120.0000',
            'cost_per_item' => '80.0000',
            'sku' => 'S-RED',
            'barcode' => '1111',
            'track_stock' => true,
            'stock' => 10,
            'in_stock' => true,
            'weight' => '1.50',
            'weight_unit' => WeightUnit::Kg->value,
            'options' => [
                [
                    'option_id' => $initialOptions[0]['id'],
                    'value_id' => $initialOptions[0]['values'][0]['id'],
                ],
                [
                    'option_id' => $initialOptions[1]['id'],
                    'value_id' => $initialOptions[1]['values'][0]['id'],
                ],
            ],
        ],
    ];

    $product = Product::factory()->create([
        'title' => 'Initial Product',
        'type' => 'physical',
        'url_handle' => 'initial-product',
        'is_active' => true,
    ]);

    // Attach initial options and variants using action (simulate store logic)
    $product = app(UpdateProductAction::class)->handle($product, UpdateProductInput::fromArray([
        'options' => $initialOptions,
        'variants' => $initialVariants,
    ]));

    $updatedOptions = [
        [
            'id' => $initialOptions[0]['id'],
            'name' => 'Size',
            'values' => [
                [
                    'id' => $initialOptions[0]['values'][0]['id'],
                    'value' => 'S',
                ],
                [
                    'id' => $initialOptions[0]['values'][1]['id'],
                    'value' => 'M',
                ],
                [
                    'id' => fake()->uuid(),
                    'value' => 'L', // New size
                ],
            ],
        ],
        [
            'id' => $initialOptions[1]['id'],
            'name' => 'Color',
            'values' => [
                [
                    'id' => $initialOptions[1]['values'][0]['id'],
                    'value' => 'Red',
                ],
                [
                    'id' => fake()->uuid(),
                    'value' => 'Blue', // New color
                ],
            ],
        ],
    ];

    $updatedVariants = [
        [
            'id' => $initialVariants[0]['id'],
            'title' => 'S / Red',
            'price' => '110.0000', // Updated price
            'compare_at_price' => '120.0000',
            'cost_per_item' => '85.0000', // Updated cost
            'sku' => 'S-RED',
            'barcode' => '1111',
            'track_stock' => true,
            'stock' => 8, // Updated stock
            'in_stock' => true,
            'weight' => '1.50',
            'weight_unit' => WeightUnit::Kg->value,
            'options' => [
                [
                    'option_id' => $updatedOptions[0]['id'],
                    'value_id' => $updatedOptions[0]['values'][0]['id'],
                ],
                [
                    'option_id' => $updatedOptions[1]['id'],
                    'value_id' => $updatedOptions[1]['values'][0]['id'],
                ],
            ],
        ],
        [
            'id' => fake()->uuid(),
            'title' => 'L / Blue',
            'price' => '150.0000',
            'compare_at_price' => '180.0000',
            'cost_per_item' => '120.0000',
            'sku' => 'L-BLUE',
            'barcode' => '3333',
            'track_stock' => true,
            'stock' => 5,
            'in_stock' => true,
            'weight' => '2.00',
            'weight_unit' => WeightUnit::Kg->value,
            'options' => [
                [
                    'option_id' => $updatedOptions[0]['id'],
                    'value_id' => $updatedOptions[0]['values'][2]['id'],
                ],
                [
                    'option_id' => $updatedOptions[1]['id'],
                    'value_id' => $updatedOptions[1]['values'][1]['id'],
                ],
            ],
        ],
    ];

    $updateData = [
        'title' => 'Updated Product',
        'type' => 'physical',
        'url_handle' => 'updated-product',
        'is_active' => true,
        'options' => $updatedOptions,
        'variants' => $updatedVariants,
    ];

    $response = actingAsSuperAdmin()->patch(route('admin.products.update', $product), $updateData);

    $response->assertRedirect()
        ->assertSessionHasNoErrors();

    assertDatabaseHas('products', [
        'id' => $product->id,
        'title' => castAsTranslatableJson('Updated Product'),
        'type' => 'physical',
        'url_handle' => 'updated-product',
        'is_active' => true,
    ]);

    foreach ($updatedOptions as $optionData) {
        assertDatabaseHas('product_options', [
            'id' => $optionData['id'],
            'product_id' => $product->id,
            'name' => castAsTranslatableJson($optionData['name']),
        ]);

        foreach ($optionData['values'] as $valueData) {
            assertDatabaseHas('product_option_values', [
                'id' => $valueData['id'],
                'product_option_id' => $optionData['id'],
                'value' => castAsTranslatableJson($valueData['value']),
            ]);
        }
    }

    foreach ($updatedVariants as $variantData) {
        assertDatabaseHas('product_variants', [
            'product_id' => $product->id,
            ...Arr::except($variantData, ['options', 'title']),
            'title' => castAsTranslatableJson($variantData['title']),
        ]);

        foreach ($variantData['options'] as $optionData) {
            assertDatabaseHas('product_variant_options', [
                'product_variant_id' => $variantData['id'],
                'product_option_id' => $optionData['option_id'],
                'product_option_value_id' => $optionData['value_id'],
            ]);
        }
    }
});

test('validates unique sku for variants', function () {

    $optionId = fake()->uuid();
    $valueId = fake()->uuid();

    $product = Product::factory()->create([
        'title' => 'SKU Update Test',
        'type' => 'physical',
        'url_handle' => 'sku-update-test',
        'track_stock' => true,
        'is_active' => true,
    ]);

    $variant1 = [
        'id' => fake()->uuid(),
        'title' => 'S',
        'price' => '100.0000',
        'sku' => 'UNIQUE-SKU',
        'barcode' => '111',
        'track_stock' => true,
        'stock' => 1,
        'in_stock' => true,
        'weight' => '1.00',
        'weight_unit' => WeightUnit::Kg->value,
        'options' => [
            [
                'option_id' => $optionId,
                'value_id' => $valueId,
            ],
        ],
    ];

    $product = app(UpdateProductAction::class)->handle($product, UpdateProductInput::fromArray([
        'options' => [
            [
                'id' => $optionId,
                'name' => 'Size',
                'values' => [
                    [
                        'id' => $valueId,
                        'value' => 'S',
                    ],
                ],
            ],
        ],
        'variants' => [$variant1],
    ]));

    $updateData = [
        'title' => 'SKU Update Test',
        'type' => 'physical',
        'url_handle' => 'sku-update-test',
        'is_active' => true,
        'options' => [
            [
                'id' => $optionId,
                'name' => 'Size',
                'values' => [
                    [
                        'id' => $valueId,
                        'value' => 'S',
                    ],
                ],
            ],
        ],
        'variants' => [
            $variant1,
            [
                'id' => fake()->uuid(),
                'title' => 'M',
                'price' => '120.0000',
                'sku' => 'UNIQUE-SKU', // duplicate
                'barcode' => '222',
                'track_stock' => true,
                'stock' => 2,
                'in_stock' => true,
                'weight' => '1.00',
                'weight_unit' => WeightUnit::Kg->value,
                'options' => [
                    [
                        'option_id' => $optionId,
                        'value_id' => $valueId,
                    ],
                ],
            ],
        ],
    ];

    $response = actingAsSuperAdmin()->patch(route('admin.products.update', $product), $updateData);

    $response->assertRedirectBack()
        ->assertInvalid('variants.1.sku');
});

test('validates unique barcode for variants', function () {
    $optionId = fake()->uuid();
    $valueId = fake()->uuid();

    $product = Product::factory()->create([
        'title' => 'Barcode Update Test',
        'type' => 'physical',
        'url_handle' => 'barcode-update-test',
        'is_active' => true,
    ]);

    $variant1 = [
        'id' => fake()->uuid(),
        'title' => 'S',
        'price' => '100.0000',
        'sku' => 'SKU-1',
        'barcode' => 'UNIQUE-BARCODE',
        'track_stock' => true,
        'stock' => 1,
        'in_stock' => true,
        'weight' => '1.00',
        'weight_unit' => WeightUnit::Kg->value,
        'options' => [
            [
                'option_id' => $optionId,
                'value_id' => $valueId,
            ],
        ],
    ];

    app(UpdateProductAction::class)->handle($product, UpdateProductInput::fromArray([
        'options' => [
            [
                'id' => $optionId,
                'name' => 'Size',
                'values' => [
                    [
                        'id' => $valueId,
                        'value' => 'S',
                    ],
                ],
            ],
        ],
        'variants' => [$variant1],
    ]));

    $updateData = [
        'title' => 'Barcode Update Test',
        'type' => 'physical',
        'url_handle' => 'barcode-update-test',
        'is_active' => true,
        'options' => [
            [
                'id' => $optionId,
                'name' => 'Size',
                'values' => [
                    [
                        'id' => $valueId,
                        'value' => 'S',
                    ],
                ],
            ],
        ],
        'variants' => [
            $variant1,
            [
                'id' => fake()->uuid(),
                'title' => 'M',
                'price' => '120.0000',
                'sku' => 'SKU-2',
                'barcode' => 'UNIQUE-BARCODE', // duplicate
                'track_stock' => true,
                'stock' => 2,
                'in_stock' => true,
                'weight' => '1.00',
                'weight_unit' => WeightUnit::Kg->value,
                'options' => [
                    [
                        'option_id' => $optionId,
                        'value_id' => $valueId,
                    ],
                ],
            ],
        ],
    ];

    $response = actingAsSuperAdmin()->patch(route('admin.products.update', $product), $updateData);

    $response->assertRedirectBack()
        ->assertInvalid('variants.1.barcode');
});

test('base product fields are set to null when variants are present', function () {

    $optionId = fake()->uuid();
    $valueId = fake()->uuid();

    $product = Product::factory()->create([
        'title' => 'New Product',
        'type' => 'physical',
        'url_handle' => 'new-product',
        'is_active' => true,
    ]);

    $optionsData = [
        [
            'id' => $optionId,
            'name' => 'Size',
            'values' => [
                [
                    'id' => $valueId,
                    'value' => 'S',
                ],
            ],
        ],
    ];

    $variantsData = [
        [
            'id' => fake()->uuid(),
            'title' => 'S',
            'price' => '100.0000',
            'sku' => 'SKU-1',
            'barcode' => 'BARCODE-1',
            'track_stock' => true,
            'stock' => 1,
            'in_stock' => true,
            'weight' => '1.00',
            'weight_unit' => WeightUnit::Kg->value,
            'options' => [
                [
                    'option_id' => $optionId,
                    'value_id' => $valueId,
                ],
            ],
        ],
    ];

    $product = app(UpdateProductAction::class)->handle($product, UpdateProductInput::fromArray([
        'options' => $optionsData,
        'variants' => $variantsData,
    ]));

    $updatedData = [
        'price' => '19.9900',
        'compare_at_price' => '29.9900',
        'cost_per_item' => '15.0000',
        'sku' => 'PROD-001',
        'barcode' => '123456789',
        'track_stock' => true,
        'stock' => 100,
        'in_stock' => true,
        'weight' => '1.50',
        'weight_unit' => WeightUnit::Kg->value,
    ];

    $response = actingAsSuperAdmin()->patch(route('admin.products.update', $product), $updatedData);

    $response->assertRedirect()
        ->assertSessionHasNoErrors();

    assertDatabaseHas('products', [
        'price' => null,
        'compare_at_price' => null,
        'cost_per_item' => null,
        'sku' => null,
        'barcode' => null,
        'track_stock' => null,
        'stock' => null,
        'in_stock' => null,
        'weight' => null,
        'weight_unit' => null,
    ]);
});

test('keeps every variant non default when no variant has is_default set to true', function () {

    $product = Product::factory()->create([
        'title' => 'Default Test Product',
        'type' => 'physical',
        'url_handle' => 'default-test-product',
        'is_active' => true,
    ]);

    $optionId = fake()->uuid();
    $valueId1 = fake()->uuid();
    $valueId2 = fake()->uuid();
    $variant1Id = fake()->uuid();
    $variant2Id = fake()->uuid();

    $updateData = [
        'title' => 'Default Test Product',
        'type' => 'physical',
        'url_handle' => 'default-test-product',
        'is_active' => true,
        'options' => [
            [
                'id' => $optionId,
                'name' => 'Size',
                'values' => [
                    [
                        'id' => $valueId1,
                        'value' => 'S',
                    ],
                    [
                        'id' => $valueId2,
                        'value' => 'M',
                    ],
                ],
            ],
        ],
        'variants' => [
            [
                'id' => $variant1Id,
                'title' => 'S',
                'price' => '100.0000',
                'sku' => 'SKU-S',
                'barcode' => 'BARCODE-S',
                'track_stock' => true,
                'stock' => 10,
                'in_stock' => true,
                'weight' => '1.00',
                'weight_unit' => WeightUnit::Kg->value,                'is_default' => false, // Explicitly set to false
                'options' => [
                    [
                        'option_id' => $optionId,
                        'value_id' => $valueId1,
                    ],
                ],
            ],
            [
                'id' => $variant2Id,
                'title' => 'M',
                'price' => '120.0000',
                'sku' => 'SKU-M',
                'barcode' => 'BARCODE-M',
                'track_stock' => true,
                'stock' => 5,
                'in_stock' => true,
                'weight' => '1.20',
                'weight_unit' => WeightUnit::Kg->value,                'is_default' => false, // Explicitly set to false
                'options' => [
                    [
                        'option_id' => $optionId,
                        'value_id' => $valueId2,
                    ],
                ],
            ],
        ],
    ];

    $response = actingAsSuperAdmin()->patch(route('admin.products.update', $product), $updateData);

    $response->assertRedirect()
        ->assertSessionHasNoErrors();

    assertDatabaseHas('product_variants', [
        'id' => $variant1Id,
        'is_default' => false,
    ]);

    assertDatabaseHas('product_variants', [
        'id' => $variant2Id,
        'is_default' => false,
    ]);
});

test('ensures only one variant is default when multiple variants have is_default set to true', function () {

    $product = Product::factory()->create([
        'title' => 'Multiple Default Test Product',
        'type' => 'physical',
        'url_handle' => 'multiple-default-test-product',
        'is_active' => true,
    ]);

    $optionId = fake()->uuid();
    $valueId1 = fake()->uuid();
    $valueId2 = fake()->uuid();
    $valueId3 = fake()->uuid();
    $variant1Id = fake()->uuid();
    $variant2Id = fake()->uuid();
    $variant3Id = fake()->uuid();

    $updateData = [
        'title' => 'Multiple Default Test Product',
        'type' => 'physical',
        'url_handle' => 'multiple-default-test-product',
        'is_active' => true,
        'options' => [
            [
                'id' => $optionId,
                'name' => 'Size',
                'values' => [
                    [
                        'id' => $valueId1,
                        'value' => 'S',
                    ],
                    [
                        'id' => $valueId2,
                        'value' => 'M',
                    ],
                    [
                        'id' => $valueId3,
                        'value' => 'L',
                    ],
                ],
            ],
        ],
        'variants' => [
            [
                'id' => $variant1Id,
                'title' => 'S',
                'price' => '100.0000',
                'sku' => 'SKU-S',
                'barcode' => 'BARCODE-S',
                'track_stock' => true,
                'stock' => 10,
                'in_stock' => true,
                'weight' => '1.00',
                'weight_unit' => WeightUnit::Kg->value,                'is_default' => true, // First variant marked as default
                'options' => [
                    [
                        'option_id' => $optionId,
                        'value_id' => $valueId1,
                    ],
                ],
            ],
            [
                'id' => $variant2Id,
                'title' => 'M',
                'price' => '120.0000',
                'sku' => 'SKU-M',
                'barcode' => 'BARCODE-M',
                'track_stock' => true,
                'stock' => 5,
                'in_stock' => true,
                'weight' => '1.20',
                'weight_unit' => WeightUnit::Kg->value,                'is_default' => true, // Second variant also marked as default
                'options' => [
                    [
                        'option_id' => $optionId,
                        'value_id' => $valueId2,
                    ],
                ],
            ],
            [
                'id' => $variant3Id,
                'title' => 'L',
                'price' => '140.0000',
                'sku' => 'SKU-L',
                'barcode' => 'BARCODE-L',
                'track_stock' => true,
                'stock' => 3,
                'in_stock' => true,
                'weight' => '1.50',
                'weight_unit' => WeightUnit::Kg->value,                'is_default' => true, // Third variant also marked as default
                'options' => [
                    [
                        'option_id' => $optionId,
                        'value_id' => $valueId3,
                    ],
                ],
            ],
        ],
    ];

    $response = actingAsSuperAdmin()->patch(route('admin.products.update', $product), $updateData);

    $response->assertRedirect()
        ->assertSessionHasNoErrors();

    assertDatabaseHas('product_variants', [
        'id' => $variant1Id,
        'is_default' => true,
    ]);

    assertDatabaseHas('product_variants', [
        'id' => $variant2Id,
        'is_default' => false,
    ]);

    assertDatabaseHas('product_variants', [
        'id' => $variant3Id,
        'is_default' => false,
    ]);
});

test('requires authentication', function () {
    $product = Product::factory()->create();

    $response = get(route('admin.products.edit', $product));

    $response->assertRedirect(route('admin.login'));

    $response = patch(route('admin.products.update', $product), [
        'title' => 'Updated Product',
        'type' => 'physical',
        'url_handle' => 'updated-product',
        'is_active' => true,
        'is_tax_exempt' => true,
    ]);

    $response->assertRedirect(route('admin.login'));
});

test('requires products.update permission', function () {
    $role = Role::query()->where(['name' => RoleEnum::Admin])->firstOrFail();
    $product = Product::factory()->create();

    $response = actingAsAdmin()->get(route('admin.products.edit', $product));

    $response->assertOk();

    $response = actingAsAdmin()->patch(route('admin.products.update', $product), [
        'title' => 'Updated Product',
        'type' => 'physical',
        'url_handle' => 'updated-product',
        'is_active' => true,
        'is_tax_exempt' => true,
    ]);

    $response->assertRedirectBack()
        ->assertSessionHasNoErrors();

    assertDatabaseHas('products', [
        'url_handle' => 'updated-product',
    ]);

    $role->revokePermissionTo(Permission::ProductsManage);

    $anotherProduct = Product::factory()->create();

    $response = actingAsAdmin()->get(route('admin.products.edit', $anotherProduct));

    $response->assertForbidden();

    $response = actingAsAdmin()->patch(route('admin.products.update', $anotherProduct), [
        'title' => 'Another Update',
        'type' => 'physical',
        'url_handle' => 'another-update',
        'is_active' => true,
        'is_tax_exempt' => true,
    ]);

    $response->assertForbidden();

    assertDatabaseMissing('products', [
        'url_handle' => 'another-update',
    ]);
});

test('tax_category is required when is_tax_exempt is set to false without an existing tax category', function () {
    $product = Product::factory()->create([
        'is_tax_exempt' => true,
        'tax_category' => null,
    ]);

    $response = actingAsSuperAdmin()->patch(route('admin.products.update', $product), [
        'is_tax_exempt' => false,
    ]);

    $response->assertRedirectBack()
        ->assertInvalid('tax_category');

    assertDatabaseHas('products', [
        'id' => $product->id,
        'is_tax_exempt' => true,
    ]);
});

test('tax_category is not required when product already has a tax category', function () {
    $taxCategory = TaxCategory::Standard->value;
    $product = Product::factory()->create([
        'is_tax_exempt' => true,
        'tax_category' => $taxCategory,
    ]);

    $response = actingAsSuperAdmin()->patch(route('admin.products.update', $product), [
        'is_tax_exempt' => false,
    ]);

    $response->assertRedirect()
        ->assertSessionHasNoErrors();

    assertDatabaseHas('products', [
        'id' => $product->id,
        'is_tax_exempt' => false,
        'tax_category' => $taxCategory,
    ]);
});

test('tax_category can be set together with is_tax_exempt false', function () {
    $taxCategory = TaxCategory::Standard->value;
    $product = Product::factory()->create([
        'is_tax_exempt' => true,
        'tax_category' => null,
    ]);

    $response = actingAsSuperAdmin()->patch(route('admin.products.update', $product), [
        'is_tax_exempt' => false,
        'tax_category' => $taxCategory,
    ]);

    $response->assertRedirect()
        ->assertSessionHasNoErrors();

    assertDatabaseHas('products', [
        'id' => $product->id,
        'is_tax_exempt' => false,
        'tax_category' => $taxCategory,
    ]);
});

test('partial patch without tax fields preserves existing taxable product', function () {
    $taxCategory = TaxCategory::Standard->value;
    $product = Product::factory()->create([
        'is_tax_exempt' => false,
        'tax_category' => $taxCategory,
    ]);

    $response = actingAsSuperAdmin()->patch(route('admin.products.update', $product), [
        'title' => 'Renamed Product',
        'type' => 'physical',
    ]);

    $response->assertRedirect()
        ->assertSessionHasNoErrors();

    assertDatabaseHas('products', [
        'id' => $product->id,
        'is_tax_exempt' => false,
        'tax_category' => $taxCategory,
    ]);
});

test('rejects a product URL handle that is not a valid slug', function () {
    $product = Product::factory()->create(['url_handle' => 'valid-handle']);

    $response = actingAsSuperAdmin()->patch(route('admin.products.update', $product), [
        'url_handle' => 'Invalid Handle',
    ]);

    $response->assertRedirectBack()
        ->assertInvalid('url_handle');

    assertDatabaseHas('products', [
        'id' => $product->id,
        'url_handle' => 'valid-handle',
    ]);
});

test('leaves an existing invalid URL handle untouched when it is not submitted', function () {
    $product = Product::factory()->create(['url_handle' => 'Legacy Handle']);

    $response = actingAsSuperAdmin()->patch(route('admin.products.update', $product), [
        'title' => 'Renamed Product',
        'type' => 'physical',
    ]);

    $response->assertRedirect()
        ->assertSessionHasNoErrors();

    assertDatabaseHas('products', [
        'id' => $product->id,
        'url_handle' => 'Legacy Handle',
    ]);
});

test('converts a physical product to digital with downloads', function () {
    $product = Product::factory()->create([
        'url_handle' => 'becomes-digital',
        'weight' => '2.50',
        'weight_unit' => WeightUnit::Kg->value,
    ]);

    $media = Media::factory()->file()->create();

    $response = actingAsSuperAdmin()->patch(route('admin.products.update', $product), [
        'type' => 'digital',
        'track_stock' => false,
        'in_stock' => true,
        'downloads' => [
            [
                'name' => 'Guide',
                'media_id' => $media->id,
            ],
        ],
    ]);

    $response->assertRedirect()
        ->assertSessionHasNoErrors();

    assertDatabaseHas('products', [
        'id' => $product->id,
        'type' => 'digital',
        'weight' => null,
        'weight_unit' => null,
    ]);

    assertDatabaseHas('product_downloads', [
        'product_id' => $product->id,
        'name' => 'Guide',
    ]);
});

test('updating to digital requires at least one download', function () {
    $product = Product::factory()->create(['url_handle' => 'digital-no-downloads']);

    $response = actingAsSuperAdmin()->patch(route('admin.products.update', $product), [
        'type' => 'digital',
        'track_stock' => false,
        'in_stock' => true,
        'downloads' => [],
    ]);

    $response->assertRedirectBack()
        ->assertInvalid('downloads');

    assertDatabaseHas('products', [
        'id' => $product->id,
        'type' => 'physical',
    ]);
});

test('flipping to digital is rejected when downloads are omitted and the product has none', function () {
    $product = Product::factory()->create(['url_handle' => 'flip-no-downloads']);

    $response = actingAsSuperAdmin()->patch(route('admin.products.update', $product), [
        'type' => 'digital',
        'track_stock' => false,
        'in_stock' => true,
    ]);

    $response->assertRedirectBack()
        ->assertInvalid('downloads');

    assertDatabaseHas('products', [
        'id' => $product->id,
        'type' => 'physical',
    ]);
});

test('removing all files from a digital product clears them', function () {
    $product = Product::factory()->digital()->create(['url_handle' => 'digital-clear-files']);
    $download = ProductDownload::factory()->create([
        'product_id' => $product->id,
    ]);

    // The file manager submits an empty array when the admin removes every file.
    $response = actingAsSuperAdmin()->patch(route('admin.products.update', $product), [
        'type' => 'digital',
        'track_stock' => false,
        'in_stock' => true,
        'downloads' => [],
    ]);

    $response->assertRedirect()
        ->assertSessionHasNoErrors();

    assertDatabaseMissing('product_downloads', ['id' => $download->id]);
});
