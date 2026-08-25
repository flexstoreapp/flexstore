<?php

declare(strict_types=1);

use App\Actions\DuplicateProductAction;
use App\Enums\Permission;
use App\Enums\Role as RoleEnum;
use App\Enums\TaxCategory;
use App\Enums\WeightUnit;
use App\Http\Controllers\Admin\DuplicateProductController;
use App\Http\Requests\Admin\DuplicateProductRequest;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductOption;
use App\Models\ProductOptionValue;
use App\Models\ProductVariant;
use App\Models\ProductVariantOption;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;
use function Pest\Laravel\post;

covers(DuplicateProductController::class, DuplicateProductRequest::class, DuplicateProductAction::class);

uses()->group('product');

test('duplicates a product successfully and redirects to edit page', function () {
    $category = Category::factory()->create();
    $brand = Brand::factory()->create();
    $taxCategory = TaxCategory::Standard->value;

    $product = Product::factory()->create([
        'title' => 'Original Product',
        'url_handle' => 'original-product',
        'category_id' => $category->id,
        'brand_id' => $brand->id,
        'tax_category' => $taxCategory,
        'sku' => 'ORIGINAL-SKU',
        'barcode' => 'ORIGINAL-BARCODE',
        'price' => '99.9900',
        'compare_at_price' => '119.9900',
        'cost_per_item' => '50.0000',
        'weight' => '1.5',
        'weight_unit' => WeightUnit::Kg,
        'is_tax_exempt' => false,
        'track_stock' => true,
        'stock' => 10,
        'in_stock' => true,
        'low_stock_threshold' => 5,
        'seo_title' => 'SEO Title',
        'seo_description' => 'SEO Description',
        'is_active' => true,
    ]);

    $response = actingAsSuperAdmin()->post(route('admin.products.duplicate', $product), [
        'title' => 'Duplicated Product',
        'duplicate_category' => true,
        'duplicate_brand' => true,
        'duplicate_media' => true,
        'duplicate_pricing' => true,
        'duplicate_tax' => true,
        'duplicate_inventory' => true,
        'duplicate_shipping' => true,
        'duplicate_seo' => true,
        'is_active' => false,
    ]);

    $duplicatedProduct = Product::query()->where('url_handle', 'duplicated-product')->first();

    $response->assertRedirect(route('admin.products.edit', $duplicatedProduct))
        ->assertSessionHasNoErrors();

    expect($duplicatedProduct)->not->toBeNull()
        ->and($duplicatedProduct->id)->not->toBe($product->id)
        ->and($duplicatedProduct->getTranslation('title', 'en'))->toBe('Duplicated Product')
        ->and($duplicatedProduct->url_handle)->toBe('duplicated-product')
        ->and($duplicatedProduct->category_id)->toBe($category->id)
        ->and($duplicatedProduct->brand_id)->toBe($brand->id)
        ->and($duplicatedProduct->tax_category->value)->toBe($taxCategory)
        ->and($duplicatedProduct->price)->toBe('99.9900')
        ->and($duplicatedProduct->is_active)->toBeFalse();

    assertDatabaseHas('products', [
        'url_handle' => 'duplicated-product',
        'category_id' => $category->id,
        'brand_id' => $brand->id,
        'tax_category' => $taxCategory,
        'title' => castAsTranslatableJson('Duplicated Product'),
        'is_active' => false,
    ]);
});

test('duplicates product with options and variants', function () {
    $product = Product::factory()->create([
        'title' => 'Original Product',
    ]);

    $sizeOption = ProductOption::factory()->create([
        'product_id' => $product->id,
        'name' => 'Size',
    ]);

    $smallValue = ProductOptionValue::factory()->create([
        'product_option_id' => $sizeOption->id,
        'value' => 'Small',
    ]);

    $largeValue = ProductOptionValue::factory()->create([
        'product_option_id' => $sizeOption->id,
        'value' => 'Large',
    ]);

    $variant1 = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'title' => 'Small',
        'sku' => 'VAR-SKU-1',
    ]);

    $variant2 = ProductVariant::factory()->create([
        'product_id' => $product->id,
        'title' => 'Large',
        'sku' => 'VAR-SKU-2',
    ]);

    ProductVariantOption::factory()->create([
        'product_variant_id' => $variant1->id,
        'product_option_id' => $sizeOption->id,
        'product_option_value_id' => $smallValue->id,
    ]);

    ProductVariantOption::factory()->create([
        'product_variant_id' => $variant2->id,
        'product_option_id' => $sizeOption->id,
        'product_option_value_id' => $largeValue->id,
    ]);

    $response = actingAsSuperAdmin()->post(route('admin.products.duplicate', $product), [
        'title' => 'Duplicated Product',
    ]);

    $duplicatedProduct = Product::query()->where('url_handle', 'duplicated-product')->first();

    $response->assertRedirect(route('admin.products.edit', $duplicatedProduct))
        ->assertSessionHasNoErrors();

    expect($duplicatedProduct->options)->toHaveCount(1)
        ->and($duplicatedProduct->options->first()->name)->toBe('Size')
        ->and($duplicatedProduct->variants)->toHaveCount(2);
});

test('validates required title field', function () {
    $product = Product::factory()->create();

    $response = actingAsSuperAdmin()->post(route('admin.products.duplicate', $product), []);

    $response->assertSessionHasErrors(['title']);
});

test('validates title max length', function () {
    $product = Product::factory()->create();

    $response = actingAsSuperAdmin()->post(route('admin.products.duplicate', $product), [
        'title' => str_repeat('a', 256),
    ]);

    $response->assertSessionHasErrors(['title']);
});

test('validates URL handle uniqueness', function () {
    Product::factory()->create(['url_handle' => 'existing-url-handle']);

    $product = Product::factory()->create([
        'title' => 'Original Product',
    ]);

    $response = actingAsSuperAdmin()->post(route('admin.products.duplicate', $product), [
        'title' => 'Duplicated Product',
        'url_handle' => 'existing-url-handle',
    ]);

    $response->assertSessionHasErrors(['url_handle']);
});

test('duplicates product with selective options disabled', function () {
    $category = Category::factory()->create();
    $brand = Brand::factory()->create();
    $taxCategory = TaxCategory::Standard->value;

    $product = Product::factory()->create([
        'title' => 'Original Product',
        'category_id' => $category->id,
        'brand_id' => $brand->id,
        'tax_category' => $taxCategory,
        'sku' => 'ORIGINAL-SKU',
        'price' => '99.9900',
    ]);

    $response = actingAsSuperAdmin()->post(route('admin.products.duplicate', $product), [
        'title' => 'Duplicated Product',
        'duplicate_category' => false,
        'duplicate_brand' => false,
        'duplicate_pricing' => false,
        'duplicate_tax' => false,
    ]);

    $duplicatedProduct = Product::query()->where('url_handle', 'duplicated-product')->first();

    $response->assertRedirect(route('admin.products.edit', $duplicatedProduct))
        ->assertSessionHasNoErrors();

    expect($duplicatedProduct->category_id)->toBeNull()
        ->and($duplicatedProduct->brand_id)->toBeNull()
        ->and($duplicatedProduct->tax_category)->toBeNull()
        ->and($duplicatedProduct->price)->toBeNull();
});

test('duplicates product with SKUs and barcodes when option enabled', function () {
    $product = Product::factory()->create([
        'title' => 'Original Product',
        'sku' => 'ORIGINAL-SKU',
        'barcode' => 'ORIGINAL-BARCODE',
    ]);

    $response = actingAsSuperAdmin()->post(route('admin.products.duplicate', $product), [
        'title' => 'Duplicated Product',
        'duplicate_skus' => true,
        'duplicate_barcodes' => true,
    ]);

    $duplicatedProduct = Product::query()->where('url_handle', 'duplicated-product')->first();

    $response->assertRedirect(route('admin.products.edit', $duplicatedProduct))
        ->assertSessionHasNoErrors();

    expect($duplicatedProduct->sku)->toBe('ORIGINAL-SKU-copy')
        ->and($duplicatedProduct->barcode)->toBe('ORIGINAL-BARCODE-copy');
});

test('requires authentication', function () {
    $product = Product::factory()->create();

    $response = post(route('admin.products.duplicate', $product), [
        'title' => 'Duplicated Product',
    ]);

    $response->assertRedirect(route('admin.login'));
});

test('requires products.create permission', function () {
    $role = Role::query()->where(['name' => RoleEnum::Admin])->firstOrFail();
    $product = Product::factory()->create();

    $response = actingAsAdmin()->post(route('admin.products.duplicate', $product), [
        'title' => 'Duplicated Product',
    ]);

    $response->assertRedirect()
        ->assertSessionHasNoErrors();

    assertDatabaseHas('products', [
        'url_handle' => 'duplicated-product',
    ]);

    $role->revokePermissionTo(Permission::ProductsManage);

    $response = actingAsAdmin()->post(route('admin.products.duplicate', $product), [
        'title' => 'Another Duplicated Product',
    ]);

    $response->assertForbidden();

    assertDatabaseMissing('products', [
        'url_handle' => 'another-duplicated-product',
    ]);
});

test('rejects a duplicated product URL handle that is not a valid slug', function (string $urlHandle) {
    $product = Product::factory()->create();

    $response = actingAsSuperAdmin()->post(route('admin.products.duplicate', $product), [
        'title' => 'Sluggish Copy',
        'url_handle' => $urlHandle,
    ]);

    $response->assertRedirectBack()
        ->assertInvalid('url_handle');

    assertDatabaseMissing('products', [
        'url_handle' => $urlHandle,
    ]);
})->with(['Sluggish Copy', 'sluggish copy', 'sluggish,copy', '-sluggish-copy', 'sluggish--copy']);

test('accepts a duplicated product URL handle that is a valid slug', function () {
    $product = Product::factory()->create();

    $response = actingAsSuperAdmin()->post(route('admin.products.duplicate', $product), [
        'title' => 'Sluggish Copy',
        'url_handle' => 'sluggish-copy-2',
    ]);

    $response->assertRedirect()
        ->assertSessionHasNoErrors();

    assertDatabaseHas('products', [
        'url_handle' => 'sluggish-copy-2',
    ]);
});
