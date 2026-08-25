<?php

declare(strict_types=1);

use App\Actions\StoreProductAction;
use App\Enums\DimensionUnit;
use App\Enums\WeightUnit;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Requests\Admin\StoreProductRequest;
use App\Models\Category;
use App\Models\Media;
use App\Models\Product;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Laravel\post;

covers(ProductController::class, StoreProductRequest::class, StoreProductAction::class);

uses()->group('product');

beforeEach(function (): void {
    Storage::fake();
});

function downloadItem(array $overrides = []): array
{
    return array_merge([
        'name' => 'Manual',
        'media_id' => Media::factory()->file()->create()->id,
    ], $overrides);
}

test('displays product create page', function () {
    Category::factory()->count(2)->create();

    $response = actingAsSuperAdmin()->get(route('admin.products.create'));

    $response->assertOk()
        ->assertInertia(
            fn (AssertableInertia $page) => $page
                ->component('admin/products/create')
                ->has('maxUploadSize')
        );
});

test('creates a new product and redirects to edit page', function () {
    $category = Category::factory()->create();
    $media = Media::factory()->count(2)->create();
    $productData = [
        'title' => 'New Product',
        'type' => 'physical',
        'url_handle' => 'new-product',
        'description' => 'Test description',
        'category_id' => $category->id,
        'is_tax_exempt' => true,
        'price' => '19.9900',
        'compare_at_price' => '24.9900',
        'cost_per_item' => '10.0000',
        'sku' => 'PROD-001',
        'barcode' => '123456789',
        'track_stock' => true,
        'stock' => 100,
        'in_stock' => true,
        'is_active' => true,
        'weight' => '1.50',
        'weight_unit' => WeightUnit::Kg->value,
        'media' => $media->pluck('id')->all(),
        'seo_title' => 'New Product SEO Title',
        'seo_description' => 'New Product SEO Description',
    ];

    $response = actingAsSuperAdmin()->post(route('admin.products.store'), $productData);

    $product = Product::query()->where('url_handle', 'new-product')->first();

    $response->assertRedirect(route('admin.products.edit', $product))
        ->assertSessionHasNoErrors();

    assertDatabaseHas('products', [
        ...Arr::except($productData, ['title', 'description', 'seo_title', 'seo_description', 'media']),
        'title' => castAsTranslatableJson($productData['title']),
        'type' => 'physical',
        'description' => castAsTranslatableJson($productData['description']),
        'seo_title' => castAsTranslatableJson($productData['seo_title']),
        'seo_description' => castAsTranslatableJson($productData['seo_description']),
    ]);

    expect($product->mediaGallery)->toHaveCount(2);
});

test('persists product dimensions', function () {
    actingAsSuperAdmin()->post(route('admin.products.store'), [
        'title' => 'Boxed Product',
        'type' => 'physical',
        'url_handle' => 'boxed-product',
        'is_tax_exempt' => true,
        'price' => '10.0000',
        'track_stock' => false,
        'in_stock' => true,
        'is_active' => true,
        'weight' => '1.50',
        'weight_unit' => WeightUnit::Kg->value,
        'length' => '30.50',
        'width' => '20.00',
        'height' => '10.00',
        'dimension_unit' => DimensionUnit::Cm->value,
    ])->assertRedirect()->assertSessionHasNoErrors();

    $product = Product::query()->where('url_handle', 'boxed-product')->firstOrFail();

    expect($product->length)->toBe('30.50')
        ->and($product->width)->toBe('20.00')
        ->and($product->height)->toBe('10.00')
        ->and($product->dimension_unit)->toBe(DimensionUnit::Cm);
});

test('persists customs information', function () {
    actingAsSuperAdmin()->post(route('admin.products.store'), [
        'title' => 'Imported Product',
        'type' => 'physical',
        'url_handle' => 'imported-product',
        'is_tax_exempt' => true,
        'price' => '10.0000',
        'track_stock' => false,
        'in_stock' => true,
        'is_active' => true,
        'weight' => '1.50',
        'weight_unit' => WeightUnit::Kg->value,
    ])->assertRedirect()->assertSessionHasNoErrors();

    assertDatabaseHas('products', [
        'url_handle' => 'imported-product',
    ]);
});

test('rejects an invalid dimension unit and negative dimensions', function () {
    $response = actingAsSuperAdmin()->post(route('admin.products.store'), [
        'title' => 'Bad Dimensions',
        'type' => 'physical',
        'is_tax_exempt' => true,
        'price' => '10.0000',
        'track_stock' => false,
        'in_stock' => true,
        'is_active' => true,
        'weight' => '1.50',
        'weight_unit' => WeightUnit::Kg->value,
        'length' => '-5',
        'dimension_unit' => 'furlong',
    ]);

    $response->assertRedirectBack()->assertInvalid(['length', 'dimension_unit']);
});

test('validates unique URL handle', function () {
    Product::factory()->create(['url_handle' => 'existing-url-handle']);

    $response = actingAsSuperAdmin()->post(route('admin.products.store'), [
        'title' => 'New Product',
        'type' => 'physical',
        'url_handle' => 'existing-url-handle', // Already exists
        'price' => '19.9900',
        'is_tax_exempt' => true,
        'track_stock' => false,
        'in_stock' => true,
        'is_active' => true,
        'weight' => '1.00',
        'weight_unit' => WeightUnit::Kg->value,
    ]);

    $response->assertRedirectBack()
        ->assertInvalid('url_handle');

    assertDatabaseMissing('products', [
        'title' => castAsTranslatableJson('New Product'),
        'type' => 'physical',
        'url_handle' => 'existing-url-handle',
    ]);
});

test('validates unique sku', function () {
    Product::factory()->create(['sku' => 'EXISTING-SKU']);

    $response = actingAsSuperAdmin()->post(route('admin.products.store'), [
        'title' => 'New Product',
        'type' => 'physical',
        'price' => '19.9900',
        'sku' => 'EXISTING-SKU', // Already exists
        'is_tax_exempt' => true,
        'track_stock' => false,
        'in_stock' => true,
        'is_active' => true,
        'weight' => '1.00',
        'weight_unit' => WeightUnit::Kg->value,
    ]);

    $response->assertRedirectBack()
        ->assertInvalid('sku');

    assertDatabaseMissing('products', [
        'title' => castAsTranslatableJson('New Product'),
        'type' => 'physical',
        'sku' => 'EXISTING-SKU',
    ]);
});

test('validates unique barcode', function () {
    Product::factory()->create(['barcode' => 'EXISTING-BARCODE']);

    $response = actingAsSuperAdmin()->post(route('admin.products.store'), [
        'title' => 'New Product',
        'type' => 'physical',
        'price' => '19.9900',
        'barcode' => 'EXISTING-BARCODE', // Already exists
        'is_tax_exempt' => true,
        'track_stock' => false,
        'in_stock' => true,
        'is_active' => true,
        'weight' => '1.00',
        'weight_unit' => WeightUnit::Kg->value,
    ]);

    $response->assertRedirectBack()
        ->assertInvalid('barcode');

    assertDatabaseMissing('products', [
        'title' => castAsTranslatableJson('New Product'),
        'barcode' => 'EXISTING-BARCODE',
    ]);
});

test('creates a product with options and variants', function () {
    $optionsData = [
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

    $variantsData = [
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
            'is_default' => true,
            'options' => [
                [
                    'option_id' => $optionsData[0]['id'],
                    'value_id' => $optionsData[0]['values'][0]['id'],
                ],
                [
                    'option_id' => $optionsData[1]['id'],
                    'value_id' => $optionsData[1]['values'][0]['id'],
                ],
            ],
        ],
        [
            'id' => fake()->uuid(),
            'title' => 'M / Red',
            'price' => '100.0000',
            'compare_at_price' => '120.0000',
            'cost_per_item' => '80.0000',
            'sku' => 'M-RED',
            'barcode' => '2222',
            'track_stock' => true,
            'stock' => 10,
            'in_stock' => true,
            'weight' => '1.50',
            'weight_unit' => WeightUnit::Kg->value,
            'is_default' => false,
            'options' => [
                [
                    'option_id' => $optionsData[0]['id'],
                    'value_id' => $optionsData[0]['values'][1]['id'],
                ],
                [
                    'option_id' => $optionsData[1]['id'],
                    'value_id' => $optionsData[1]['values'][0]['id'],
                ],
            ],
        ],
    ];

    $productData = [
        'title' => 'Test Product',
        'type' => 'physical',
        'url_handle' => 'test-product',
        'is_tax_exempt' => true,
        'is_active' => true,
        'options' => $optionsData,
        'variants' => $variantsData,
    ];

    $response = actingAsSuperAdmin()->post(route('admin.products.store'), $productData);

    $response->assertRedirect()
        ->assertSessionHasNoErrors();

    $product = Product::query()->where('url_handle', 'test-product')->sole();

    assertDatabaseHas('products', [
        ...Arr::except($productData, ['options', 'variants', 'title']),
        'title' => castAsTranslatableJson($productData['title']),
        'type' => 'physical',
    ]);

    foreach ($optionsData as $optionData) {
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

    foreach ($variantsData as $variantData) {
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

test('validates unique sku for variants when creating a new product', function () {
    $optionId = fake()->uuid();
    $valueId = fake()->uuid();

    $productData = [
        'title' => 'SKU Test Product',
        'type' => 'physical',
        'url_handle' => 'sku-test-product',
        'is_tax_exempt' => true,
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
            [
                'id' => fake()->uuid(),
                'title' => 'S',
                'price' => '100.0000',
                'sku' => 'DUPLICATE-SKU',
                'barcode' => '111',
                'track_stock' => true,
                'availablentity' => true,
                'stock' => 1,
                'in_stock' => true,
                'weight' => '1.00',
                'weight_unit' => WeightUnit::Kg->value,
                'is_default' => true,
                'options' => [
                    [
                        'option_id' => $optionId,
                        'value_id' => $valueId,
                    ],
                ],
            ],
            [
                'id' => fake()->uuid(),
                'title' => 'M',
                'price' => '120.0000',
                'sku' => 'DUPLICATE-SKU', // duplicate
                'barcode' => '222',
                'track_stock' => true,
                'stock' => 2,
                'in_stock' => true,
                'weight' => '1.00',
                'weight_unit' => WeightUnit::Kg->value,
                'is_default' => false,
                'options' => [
                    [
                        'option_id' => $optionId,
                        'value_id' => $valueId,
                    ],
                ],
            ],
        ],
    ];

    $response = actingAsSuperAdmin()->post(route('admin.products.store'), $productData);

    $response->assertRedirectBack()
        ->assertInvalid('variants.1.sku');

    assertDatabaseMissing('product_variants', [
        'sku' => 'DUPLICATE-SKU',
    ]);
});

test('validates unique barcode for variants when creating a new product', function () {
    $optionId = fake()->uuid();
    $valueId = fake()->uuid();

    $productData = [
        'title' => 'Barcode Test Product',
        'type' => 'physical',
        'url_handle' => 'barcode-test-product',
        'is_tax_exempt' => true,
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
            [
                'id' => fake()->uuid(),
                'title' => 'S',
                'price' => '100.0000',
                'sku' => 'SKU-1',
                'barcode' => 'DUPLICATE-BARCODE',
                'track_stock' => true,
                'stock' => 1,
                'in_stock' => true,
                'weight' => '1.00',
                'weight_unit' => WeightUnit::Kg->value,
                'is_default' => true,
                'options' => [
                    [
                        'option_id' => $optionId,
                        'value_id' => $valueId,
                    ],
                ],
            ],
            [
                'id' => fake()->uuid(),
                'title' => 'M',
                'price' => '120.0000',
                'sku' => 'SKU-2',
                'barcode' => 'DUPLICATE-BARCODE', // duplicate
                'track_stock' => true,
                'stock' => 2,
                'in_stock' => true,
                'weight' => '1.00',
                'weight_unit' => WeightUnit::Kg->value,
                'is_default' => false,
                'options' => [
                    [
                        'option_id' => $optionId,
                        'value_id' => $valueId,
                    ],
                ],
            ],
        ],
    ];

    $response = actingAsSuperAdmin()->post(route('admin.products.store'), $productData);

    $response->assertRedirectBack()
        ->assertInvalid('variants.1.barcode');

    assertDatabaseMissing('product_variants', [
        'barcode' => 'DUPLICATE-BARCODE',
    ]);
});

test('base product fields are set to null when variants are present when creating a new product', function () {
    $optionId = fake()->uuid();
    $valueId = fake()->uuid();

    $productData = [
        'title' => 'New Product',
        'type' => 'physical',
        'url_handle' => 'new-product',
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
        'is_tax_exempt' => true,
        'is_active' => true,
        'seo_title' => 'New Product SEO Title',
        'seo_description' => 'New Product SEO Description',
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
                'is_default' => true,
                'options' => [
                    [
                        'option_id' => $optionId,
                        'value_id' => $valueId,
                    ],
                ],
            ],
        ],
    ];

    $response = actingAsSuperAdmin()->post(route('admin.products.store'), $productData);

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
    $optionId = fake()->uuid();
    $valueId1 = fake()->uuid();
    $valueId2 = fake()->uuid();
    $variant1Id = fake()->uuid();
    $variant2Id = fake()->uuid();

    $productData = [
        'title' => 'Default Test Product',
        'type' => 'physical',
        'url_handle' => 'default-test-product',
        'is_tax_exempt' => true,
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
                'weight_unit' => WeightUnit::Kg->value,
                'is_default' => false, // Explicitly set to false
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
                'weight_unit' => WeightUnit::Kg->value,
                'is_default' => false, // Explicitly set to false
                'options' => [
                    [
                        'option_id' => $optionId,
                        'value_id' => $valueId2,
                    ],
                ],
            ],
        ],
    ];

    $response = actingAsSuperAdmin()->post(route('admin.products.store'), $productData);

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
    $optionId = fake()->uuid();
    $valueId1 = fake()->uuid();
    $valueId2 = fake()->uuid();
    $valueId3 = fake()->uuid();
    $variant1Id = fake()->uuid();
    $variant2Id = fake()->uuid();
    $variant3Id = fake()->uuid();

    $productData = [
        'title' => 'Multiple Default Test Product',
        'type' => 'physical',
        'url_handle' => 'multiple-default-test-product',
        'is_tax_exempt' => true,
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
                'weight_unit' => WeightUnit::Kg->value,
                'is_default' => true, // First variant marked as default
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
                'weight_unit' => WeightUnit::Kg->value,
                'is_default' => true, // Second variant also marked as default
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
                'weight_unit' => WeightUnit::Kg->value,
                'is_default' => true, // Third variant also marked as default
                'options' => [
                    [
                        'option_id' => $optionId,
                        'value_id' => $valueId3,
                    ],
                ],
            ],
        ],
    ];

    $response = actingAsSuperAdmin()->post(route('admin.products.store'), $productData);

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
    $response = post(route('admin.products.store'), [
        'title' => 'Test Product',
        'type' => 'physical',
    ]);

    $response->assertRedirect(route('admin.login'));
});

test('tax_category is required when is_tax_exempt is false', function () {
    $response = actingAsSuperAdmin()->post(route('admin.products.store'), [
        'title' => 'Taxable Product',
        'type' => 'physical',
        'url_handle' => 'taxable-product',
        'price' => '19.9900',
        'track_stock' => false,
        'in_stock' => true,
        'is_active' => true,
        'weight' => '1.00',
        'weight_unit' => WeightUnit::Kg->value,
        'is_tax_exempt' => false,
    ]);

    $response->assertRedirectBack()
        ->assertInvalid('tax_category');

    assertDatabaseMissing('products', [
        'url_handle' => 'taxable-product',
    ]);
});

test('physical product requires weight', function () {
    $response = actingAsSuperAdmin()->post(route('admin.products.store'), [
        'title' => 'Weightless Physical',
        'type' => 'physical',
        'url_handle' => 'weightless-physical',
        'price' => '19.9900',
        'is_tax_exempt' => true,
        'track_stock' => false,
        'in_stock' => true,
        'is_active' => true,
        'weight' => null,
        'weight_unit' => null,
    ]);

    $response->assertRedirectBack()
        ->assertInvalid('weight');

    assertDatabaseMissing('products', [
        'url_handle' => 'weightless-physical',
    ]);
});

test('creates a product with shopping catalog fields', function () {
    $response = actingAsSuperAdmin()->post(route('admin.products.store'), [
        'title' => 'Catalog Product',
        'type' => 'physical',
        'url_handle' => 'catalog-product',
        'price' => '19.9900',
        'is_tax_exempt' => true,
        'track_stock' => false,
        'in_stock' => true,
        'is_active' => true,
        'weight' => '1.00',
        'weight_unit' => WeightUnit::Kg->value,
    ]);

    $response->assertRedirect()
        ->assertSessionHasNoErrors();

    assertDatabaseHas('products', [
        'url_handle' => 'catalog-product',
    ]);
});

test('creates a digital product with downloads', function () {
    $response = actingAsSuperAdmin()->post(route('admin.products.store'), [
        'title' => 'Digital Guide',
        'url_handle' => 'digital-guide',
        'price' => '19.9900',
        'is_tax_exempt' => true,
        'track_stock' => false,
        'in_stock' => true,
        'is_active' => true,
        'type' => 'digital',
        'downloads' => [downloadItem(['name' => 'Guide'])],
    ]);

    $response->assertRedirect()
        ->assertSessionHasNoErrors();

    assertDatabaseHas('products', [
        'url_handle' => 'digital-guide',
        'type' => 'digital',
    ]);

    assertDatabaseHas('product_downloads', [
        'name' => 'Guide',
    ]);
});

test('digital product requires at least one download', function () {
    $response = actingAsSuperAdmin()->post(route('admin.products.store'), [
        'title' => 'Missing Downloads',
        'url_handle' => 'missing-downloads',
        'price' => '19.9900',
        'is_tax_exempt' => true,
        'track_stock' => false,
        'in_stock' => true,
        'is_active' => true,
        'type' => 'digital',
    ]);

    $response->assertRedirectBack()
        ->assertInvalid('downloads');

    assertDatabaseMissing('products', [
        'url_handle' => 'missing-downloads',
    ]);
});

test('digital product does not require weight or stock', function () {
    $response = actingAsSuperAdmin()->post(route('admin.products.store'), [
        'title' => 'No Weight Digital',
        'url_handle' => 'no-weight-digital',
        'price' => '19.9900',
        'is_tax_exempt' => true,
        'track_stock' => true,
        'in_stock' => true,
        'is_active' => true,
        'type' => 'digital',
        'downloads' => [downloadItem(['name' => 'Ebook'])],
    ]);

    $response->assertRedirect()
        ->assertSessionHasNoErrors();

    assertDatabaseHas('products', [
        'url_handle' => 'no-weight-digital',
        'type' => 'digital',
    ]);
});

test('rejects a digital product whose download media does not exist', function () {
    $response = actingAsSuperAdmin()->post(route('admin.products.store'), [
        'title' => 'Ghost File',
        'type' => 'digital',
        'is_tax_exempt' => true,
        'is_active' => true,
        'track_stock' => false,
        'in_stock' => true,
        'downloads' => [[
            'name' => 'Ghost',
            'media_id' => 999999, // does not exist
        ]],
    ]);

    $response->assertInvalid('downloads.0.media_id');
});

test('rejects a download that references image media instead of a file', function () {
    $imageMedia = Media::factory()->create();

    $response = actingAsSuperAdmin()->post(route('admin.products.store'), [
        'title' => 'Bad Media Type',
        'type' => 'digital',
        'is_tax_exempt' => true,
        'is_active' => true,
        'track_stock' => false,
        'in_stock' => true,
        'downloads' => [[
            'name' => 'Wrong Type',
            'media_id' => $imageMedia->id,
        ]],
    ]);

    $response->assertInvalid('downloads.0.media_id');
});
