<?php

declare(strict_types=1);

namespace App\Actions;

use App\DTOs\DuplicateProductInput;
use App\Models\Product;
use App\Models\ProductDownload;
use App\Models\ProductOption;
use App\Models\ProductOptionValue;
use App\Models\ProductVariant;
use App\Models\ProductVariantOption;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final readonly class DuplicateProductAction
{
    public function __construct(
        private SyncMediaAction $syncMediaAction,
    ) {
    }

    public function handle(Product $product, DuplicateProductInput $input): Product
    {
        $product->loadMissing(['options.values', 'variants.options', 'downloads', 'mediaGallery']);

        return DB::transaction(function () use ($product, $input): Product {
            $options = $this->parseDuplicationOptions($input);
            $productAttributes = $this->prepareProductAttributes($product, $input, $options);
            $newProduct = Product::query()->create($productAttributes);

            if ($options['duplicate_media']) {
                $this->syncMediaAction->handle($newProduct, $product->mediaGallery->pluck('id')->map(fn (mixed $id): int => (int) $id)->all());
            }

            $optionMapping = $this->cloneOptions($product, $newProduct);
            $variantMapping = $this->cloneVariants($product, $newProduct, $optionMapping, $options);

            if ($options['duplicate_digital_files']) {
                $this->cloneDownloads($product, $newProduct, $variantMapping);
            }

            return $newProduct;
        });
    }

    /**
     * @return array<string, bool>
     */
    private function parseDuplicationOptions(DuplicateProductInput $input): array
    {
        return [
            'duplicate_category' => $input->duplicateCategory,
            'duplicate_brand' => $input->duplicateBrand,
            'duplicate_media' => $input->duplicateMedia,
            'duplicate_pricing' => $input->duplicatePricing,
            'duplicate_tax' => $input->duplicateTax,
            'duplicate_inventory' => $input->duplicateInventory,
            'duplicate_skus' => $input->duplicateSkus,
            'duplicate_barcodes' => $input->duplicateBarcodes,
            'duplicate_shipping' => $input->duplicateShipping,
            'duplicate_seo' => $input->duplicateSeo,
            'duplicate_digital_files' => $input->duplicateDigitalFiles,
        ];
    }

    /**
     * @param  array<string, bool>  $options
     * @return array<string, mixed>
     */
    private function prepareProductAttributes(Product $product, DuplicateProductInput $input, array $options): array
    {
        $attributes = $product->getAttributes();
        unset($attributes['id'], $attributes['url_handle'], $attributes['created_at'], $attributes['updated_at']);

        foreach ($product->getTranslatableAttributes() as $attribute) {
            if (isset($attributes[$attribute])) {
                $attributes[$attribute] = json_decode((string) $attributes[$attribute], true);
            }
        }

        $attributes['title'] = $input->title;
        $attributes['url_handle'] = $this->generateUrlHandle($input);
        $attributes['is_active'] = $input->isActive;

        $this->applyCategory($attributes, $options);
        $this->applyBrand($attributes, $options);
        $this->applyPricing($attributes, $options);
        $this->applyTax($attributes, $options);
        $this->applyInventory($attributes, $product, $options);
        $this->applySku($attributes, $product, $options);
        $this->applyBarcode($attributes, $product, $options);
        $this->applyShipping($attributes, $options);
        $this->applySeo($attributes, $input, $options);

        return $attributes;
    }

    private function generateUrlHandle(DuplicateProductInput $input): string
    {
        if ($input->urlHandle !== null) {
            return $input->urlHandle;
        }

        if (is_array($input->title)) {
            $titleArr = $input->title;
            $title = $titleArr['en'] ?? reset($titleArr) ?: '';
        } else {
            $title = $input->title;
        }

        return $this->generateUniqueUrlHandle((string) $title);
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @param  array<string, bool>  $options
     */
    private function applyCategory(array &$attributes, array $options): void
    {
        if (! $options['duplicate_category']) {
            $attributes['category_id'] = null;
        }
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @param  array<string, bool>  $options
     */
    private function applyBrand(array &$attributes, array $options): void
    {
        if (! $options['duplicate_brand']) {
            $attributes['brand_id'] = null;
        }
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @param  array<string, bool>  $options
     */
    private function applyPricing(array &$attributes, array $options): void
    {
        if (! $options['duplicate_pricing']) {
            $attributes['price'] = null;
            $attributes['compare_at_price'] = null;
            $attributes['cost_per_item'] = null;
        }
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @param  array<string, bool>  $options
     */
    private function applyTax(array &$attributes, array $options): void
    {
        if (! $options['duplicate_tax']) {
            $attributes['tax_category'] = null;
            $attributes['is_tax_exempt'] = false;
        }
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @param  array<string, bool>  $options
     */
    private function applyInventory(array &$attributes, Product $product, array $options): void
    {
        $hasVariants = $product->variants->isNotEmpty();

        if ($hasVariants) {
            $attributes['stock'] = null;
            $attributes['in_stock'] = null;
            $attributes['track_stock'] = null;
            if (! $options['duplicate_inventory']) {
                $attributes['low_stock_threshold'] = null;
            }
        } elseif ($options['duplicate_inventory']) {
            $attributes['stock'] = $attributes['track_stock'] ? ($attributes['stock'] ?? 0) : null;
            $attributes['in_stock'] = ($attributes['stock'] ?? 0) > 0;
        } else {
            $attributes['stock'] = $attributes['track_stock'] ? 0 : null;
            $attributes['in_stock'] = false;
            $attributes['low_stock_threshold'] = null;
        }
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @param  array<string, bool>  $options
     */
    private function applySku(array &$attributes, Product $product, array $options): void
    {
        if ($options['duplicate_skus'] && ! empty($product->sku)) {
            $attributes['sku'] = $this->generateUniqueSku($product->sku);
        } else {
            $attributes['sku'] = null;
        }
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @param  array<string, bool>  $options
     */
    private function applyBarcode(array &$attributes, Product $product, array $options): void
    {
        if ($options['duplicate_barcodes'] && ! empty($product->barcode)) {
            $attributes['barcode'] = $this->generateUniqueBarcode($product->barcode);
        } else {
            $attributes['barcode'] = null;
        }
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @param  array<string, bool>  $options
     */
    private function applyShipping(array &$attributes, array $options): void
    {
        if (! $options['duplicate_shipping']) {
            $attributes['weight'] = null;
            $attributes['weight_unit'] = null;
        }
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @param  array<string, bool>  $options
     */
    private function applySeo(array &$attributes, DuplicateProductInput $input, array $options): void
    {
        if (! $options['duplicate_seo']) {
            if (is_array($input->title)) {
                $titleArr = $input->title;
                $title = $titleArr['en'] ?? reset($titleArr) ?: '';
            } else {
                $title = $input->title;
            }
            $attributes['seo_title'] = Str::limit((string) $title, 70);
            $attributes['seo_description'] = null;
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function cloneOptions(Product $originalProduct, Product $newProduct): array
    {
        $optionMapping = [];

        foreach ($originalProduct->options as $originalOption) {
            $newOption = ProductOption::query()->create([
                'product_id' => $newProduct->id,
                'name' => $originalOption->getTranslations('name'),
            ]);

            $optionMapping[$originalOption->id] = $newOption->id;
            $valueMapping = [];

            foreach ($originalOption->values as $originalValue) {
                $newValue = ProductOptionValue::query()->create([
                    'product_option_id' => $newOption->id,
                    'value' => $originalValue->getTranslations('value'),
                ]);

                $valueMapping[$originalValue->id] = $newValue->id;
            }

            $optionMapping[$originalOption->id . '_values'] = $valueMapping;
        }

        return $optionMapping;
    }

    /**
     * @param  array<string, string|array<string, string>>  $optionMapping
     * @param  array<string, bool>  $options
     * @return array<string, string>
     */
    private function cloneVariants(Product $originalProduct, Product $newProduct, array $optionMapping, array $options): array
    {
        $variantMapping = [];

        foreach ($originalProduct->variants as $originalVariant) {
            $variantAttributes = $this->prepareVariantAttributes($originalVariant, $newProduct->id, $options);
            $newVariant = ProductVariant::query()->create($variantAttributes);
            $this->linkVariantOptions($originalVariant, $newVariant, $optionMapping);

            $variantMapping[$originalVariant->id] = $newVariant->id;
        }

        return $variantMapping;
    }

    /**
     * @param  array<string, bool>  $options
     * @return array<string, mixed>
     */
    private function prepareVariantAttributes(ProductVariant $originalVariant, int $newProductId, array $options): array
    {
        $attributes = $originalVariant->getAttributes();
        unset($attributes['id'], $attributes['product_id'], $attributes['created_at'], $attributes['updated_at']);

        if (! $options['duplicate_media']) {
            $attributes['media_id'] = null;
        }

        foreach ($originalVariant->getTranslatableAttributes() as $attribute) {
            if (isset($attributes[$attribute])) {
                $attributes[$attribute] = json_decode((string) $attributes[$attribute], true);
            }
        }

        $attributes['product_id'] = $newProductId;

        $this->applyVariantPricing($attributes, $options);
        $this->applyVariantInventory($attributes, $options);
        $this->applyVariantSku($attributes, $originalVariant, $newProductId, $options);
        $this->applyVariantBarcode($attributes, $originalVariant, $newProductId, $options);
        $this->applyVariantShipping($attributes, $options);

        return $attributes;
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @param  array<string, bool>  $options
     */
    private function applyVariantPricing(array &$attributes, array $options): void
    {
        if (! $options['duplicate_pricing']) {
            $attributes['compare_at_price'] = null;
            $attributes['cost_per_item'] = null;
        }
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @param  array<string, bool>  $options
     */
    private function applyVariantInventory(array &$attributes, array $options): void
    {
        if ($options['duplicate_inventory']) {
            $attributes['stock'] = $attributes['track_stock'] ? ($attributes['stock'] ?? 0) : null;
            $attributes['in_stock'] = ($attributes['stock'] ?? 0) > 0;
        } else {
            $attributes['stock'] = $attributes['track_stock'] ? 0 : null;
            $attributes['in_stock'] = false;
            $attributes['low_stock_threshold'] = null;
        }
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @param  array<string, bool>  $options
     */
    private function applyVariantSku(array &$attributes, ProductVariant $originalVariant, int $newProductId, array $options): void
    {
        if ($options['duplicate_skus'] && ! empty($originalVariant->sku)) {
            $attributes['sku'] = $this->generateUniqueVariantSku($newProductId, $originalVariant->sku);
        } else {
            $attributes['sku'] = null;
        }
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @param  array<string, bool>  $options
     */
    private function applyVariantBarcode(array &$attributes, ProductVariant $originalVariant, int $newProductId, array $options): void
    {
        if ($options['duplicate_barcodes'] && ! empty($originalVariant->barcode)) {
            $attributes['barcode'] = $this->generateUniqueVariantBarcode($newProductId, $originalVariant->barcode);
        } else {
            $attributes['barcode'] = null;
        }
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @param  array<string, bool>  $options
     */
    private function applyVariantShipping(array &$attributes, array $options): void
    {
        if (! $options['duplicate_shipping']) {
            $attributes['weight'] = null;
            $attributes['weight_unit'] = null;
        }
    }

    /**
     * @param  array<string, string|array<string, string>>  $optionMapping
     */
    private function linkVariantOptions(ProductVariant $originalVariant, ProductVariant $newVariant, array $optionMapping): void
    {
        foreach ($originalVariant->options as $originalVariantOption) {
            $originalOptionId = $originalVariantOption->product_option_id;
            $originalValueId = $originalVariantOption->product_option_value_id;

            $newOptionId = $optionMapping[$originalOptionId] ?? null;
            $valueMapping = $optionMapping[$originalOptionId . '_values'] ?? [];
            $newValueId = $valueMapping[$originalValueId] ?? null;

            if ($newOptionId && $newValueId) {
                ProductVariantOption::query()->create([
                    'product_variant_id' => $newVariant->id,
                    'product_option_id' => $newOptionId,
                    'product_option_value_id' => $newValueId,
                ]);
            }
        }
    }

    private function generateUniqueUrlHandle(string $title): string
    {
        $baseUrlHandle = Str::slug($title);
        $urlHandle = $baseUrlHandle;
        $counter = 1;

        while (Product::query()->where('url_handle', $urlHandle)->exists()) {
            $urlHandle = $baseUrlHandle . '-' . $counter;
            $counter++;
        }

        return $urlHandle;
    }

    private function generateUniqueSku(string $originalSku): string
    {
        $baseSku = $originalSku . '-copy';
        $sku = $baseSku;
        $counter = 1;

        while (Product::query()->where('sku', $sku)->exists() || ProductVariant::query()->where('sku', $sku)->exists()) {
            $sku = $originalSku . '-copy-' . $counter;
            $counter++;
        }

        return $sku;
    }

    private function generateUniqueVariantSku(int $productId, string $originalSku): string
    {
        $baseSku = $originalSku . '-copy';
        $sku = $baseSku;
        $counter = 1;

        while (
            Product::query()->where('sku', $sku)->exists()
            || ProductVariant::query()->where('product_id', $productId)->where('sku', $sku)->exists()
        ) {
            $sku = $originalSku . '-copy-' . $counter;
            $counter++;
        }

        return $sku;
    }

    private function generateUniqueBarcode(string $originalBarcode): string
    {
        $baseBarcode = $originalBarcode . '-copy';
        $barcode = $baseBarcode;
        $counter = 1;

        while (
            Product::query()->where('barcode', $barcode)->exists()
            || ProductVariant::query()->where('barcode', $barcode)->exists()
        ) {
            $barcode = $originalBarcode . '-copy-' . $counter;
            $counter++;
        }

        return $barcode;
    }

    private function generateUniqueVariantBarcode(int $productId, string $originalBarcode): string
    {
        $baseBarcode = $originalBarcode . '-copy';
        $barcode = $baseBarcode;
        $counter = 1;

        while (
            Product::query()->where('barcode', $barcode)->exists()
            || ProductVariant::query()->where('product_id', $productId)->where('barcode', $barcode)->exists()
        ) {
            $barcode = $originalBarcode . '-copy-' . $counter;
            $counter++;
        }

        return $barcode;
    }

    /**
     * @param  array<string, string>  $variantMapping
     */
    private function cloneDownloads(Product $originalProduct, Product $newProduct, array $variantMapping): void
    {
        foreach ($originalProduct->downloads as $download) {
            ProductDownload::query()->create([
                'product_id' => $newProduct->id,
                'product_variant_id' => $download->product_variant_id !== null
                    ? ($variantMapping[$download->product_variant_id] ?? null)
                    : null,
                'media_id' => $download->media_id,
                'name' => $download->name,
                'sort_order' => $download->sort_order,
            ]);
        }
    }
}
