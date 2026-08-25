<?php

declare(strict_types=1);

namespace App\Actions;

use App\DTOs\UpdateProductInput;
use App\Enums\ProductType;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final readonly class UpdateProductAction
{
    public function __construct(
        private UpsertProductOptionsAction $manageOptionsAction,
        private UpsertProductVariantsAction $manageVariantsAction,
        private UpsertProductDownloadsAction $manageDownloadsAction,
        private SyncMediaAction $syncMediaAction,
    ) {
    }

    public function handle(Product $product, UpdateProductInput $input): Product
    {
        return DB::transaction(function () use ($product, $input): Product {
            $hasVariants = $this->hasVariants($product, $input);
            $type = $input->has('type') && $input->type instanceof ProductType ? $input->type : $product->type;
            $isDigital = $type === ProductType::Digital;

            $product->update([
                'title' => $input->has('title') ? $input->title : $product->title,
                'type' => $type,
                'url_handle' => $this->getUrlHandle($product, $input),
                'description' => $input->has('description') ? $input->description : $product->description,
                'category_id' => $input->has('category_id') ? $input->categoryId : $product->category_id,
                'brand_id' => $input->has('brand_id') ? $input->brandId : $product->brand_id,
                'tax_category' => $input->has('tax_category') ? $input->taxCategory : $product->tax_category,
                'is_tax_exempt' => $input->has('is_tax_exempt') ? $input->isTaxExempt : $product->is_tax_exempt,
                'price' => $this->getValueIfNoVariants($product, $input, 'price', $input->price),
                'compare_at_price' => $this->getValueIfNoVariants($product, $input, 'compare_at_price', $input->compareAtPrice),
                'cost_per_item' => $this->getValueIfNoVariants($product, $input, 'cost_per_item', $input->costPerItem),
                'sku' => $this->getValueIfNoVariants($product, $input, 'sku', $input->sku),
                'barcode' => $this->getValueIfNoVariants($product, $input, 'barcode', $input->barcode),
                'track_stock' => $isDigital ? ($hasVariants ? null : false) : $this->getValueIfNoVariants($product, $input, 'track_stock', $input->trackStock),
                'stock' => $isDigital ? null : $this->getValueIfNoVariants($product, $input, 'stock', $input->stock),
                'low_stock_threshold' => $isDigital ? null : $this->getValueIfNoVariants($product, $input, 'low_stock_threshold', $input->lowStockThreshold),
                'in_stock' => $isDigital ? ($hasVariants ? null : true) : $this->getValueIfNoVariants($product, $input, 'in_stock', $input->inStock),
                'is_active' => $input->has('is_active') ? $input->isActive : $product->is_active,
                'weight' => $isDigital ? null : $this->getValueIfNoVariants($product, $input, 'weight', $input->weight),
                'weight_unit' => $isDigital ? null : $this->getValueIfNoVariants($product, $input, 'weight_unit', $input->weightUnit),
                'length' => $isDigital ? null : $this->getValueIfNoVariants($product, $input, 'length', $input->length),
                'width' => $isDigital ? null : $this->getValueIfNoVariants($product, $input, 'width', $input->width),
                'height' => $isDigital ? null : $this->getValueIfNoVariants($product, $input, 'height', $input->height),
                'dimension_unit' => $isDigital ? null : $this->getValueIfNoVariants($product, $input, 'dimension_unit', $input->dimensionUnit),
                'seo_title' => $this->getSeoTitle($product, $input),
                'seo_description' => $input->has('seo_description') ? $input->seoDescription : $product->seo_description,
            ]);

            if ($input->has('media')) {
                $this->syncMediaAction->handle($product, $input->media ?? []);
            }

            if ($input->has('options')) {
                $this->manageOptionsAction->handle($product, $input->options ?? []);
            }

            if ($input->has('variants')) {
                $this->manageVariantsAction->handle($product, $input->variants ?? []);
            }

            if ($isDigital) {
                if ($input->has('downloads')) {
                    $this->manageDownloadsAction->handle($product, $input->downloads ?? []);
                }
            } elseif ($product->wasChanged('type')) {
                $this->manageDownloadsAction->handle($product, []);
            }

            return $product;
        });
    }

    private function hasVariants(Product $product, UpdateProductInput $input): bool
    {
        if ($input->has('variants') && $input->variants !== null && $input->variants !== []) {
            return true;
        }

        return $product->variants->isNotEmpty();
    }

    private function getValueIfNoVariants(Product $product, UpdateProductInput $input, string $key, mixed $fallbackInputValue): mixed
    {
        if ($this->hasVariants($product, $input)) {
            return null;
        }

        return $input->has($key) ? $fallbackInputValue : $product->{$key};
    }

    private function getUrlHandle(Product $product, UpdateProductInput $input): string
    {
        if ($input->has('url_handle') && $input->urlHandle === null) {
            $title = $input->has('title') ? $input->title : $product->title;
            $titleString = is_array($title) ? ($title['en'] ?? reset($title) ?: '') : (string) $title;

            return Str::slug((string) $titleString);
        }

        return $input->has('url_handle') && $input->urlHandle !== null ? $input->urlHandle : $product->url_handle;
    }

    private function getSeoTitle(Product $product, UpdateProductInput $input): mixed
    {
        if ($input->has('seo_title') && $input->seoTitle === null) {
            $title = $input->has('title') ? $input->title : $product->title;
            $titleString = is_array($title) ? ($title['en'] ?? reset($title) ?: '') : (string) $title;

            return Str::limit((string) $titleString, 70);
        }

        return $input->has('seo_title') ? $input->seoTitle : $product->seo_title;
    }
}
