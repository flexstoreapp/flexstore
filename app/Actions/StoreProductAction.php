<?php

declare(strict_types=1);

namespace App\Actions;

use App\DTOs\StoreProductInput;
use App\Enums\ProductType;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final readonly class StoreProductAction
{
    public function __construct(
        private UpsertProductOptionsAction $manageOptionsAction,
        private UpsertProductVariantsAction $manageVariantsAction,
        private UpsertProductDownloadsAction $manageDownloadsAction,
        private SyncMediaAction $syncMediaAction,
    ) {
    }

    public function handle(StoreProductInput $input): Product
    {
        return DB::transaction(function () use ($input): Product {
            $hasVariants = $input->variants !== null && $input->variants !== [];
            $isDigital = $input->type === ProductType::Digital;
            $title = $input->title;
            $titleString = is_array($title) ? ($title['en'] ?? reset($title) ?: '') : $title;
            $urlHandle = $input->urlHandle ?? Str::slug((string) $titleString);

            $product = Product::query()->create([
                'title' => $title,
                'type' => $input->type,
                'url_handle' => $urlHandle,
                'description' => $input->description,
                'category_id' => $input->categoryId,
                'brand_id' => $input->brandId,
                'tax_category' => $input->taxCategory,
                'is_tax_exempt' => $input->isTaxExempt,
                'price' => $hasVariants ? null : $input->price,
                'compare_at_price' => $hasVariants ? null : $input->compareAtPrice,
                'cost_per_item' => $hasVariants ? null : $input->costPerItem,
                'sku' => $hasVariants ? null : $input->sku,
                'barcode' => $hasVariants ? null : $input->barcode,
                'track_stock' => $hasVariants ? null : ($isDigital ? false : $input->trackStock),
                'stock' => $hasVariants || $isDigital || ! $input->trackStock ? null : $input->stock,
                'low_stock_threshold' => $hasVariants || $isDigital ? null : $input->lowStockThreshold,
                'in_stock' => $hasVariants ? null : ($isDigital ? true : $input->inStock),
                'is_active' => $input->isActive,
                'weight' => $hasVariants || $isDigital ? null : $input->weight,
                'weight_unit' => $hasVariants || $isDigital ? null : $input->weightUnit,
                'length' => $hasVariants || $isDigital ? null : $input->length,
                'width' => $hasVariants || $isDigital ? null : $input->width,
                'height' => $hasVariants || $isDigital ? null : $input->height,
                'dimension_unit' => $hasVariants || $isDigital ? null : $input->dimensionUnit,
                'seo_title' => $input->seoTitle ?? Str::limit((string) $titleString, 70),
                'seo_description' => $input->seoDescription,
            ]);

            $this->syncMediaAction->handle($product, $input->media);

            if ($input->options !== null) {
                $this->manageOptionsAction->handle($product, $input->options);
            }

            if ($input->variants !== null) {
                $this->manageVariantsAction->handle($product, $input->variants);
            }

            if ($input->downloads !== null) {
                $this->manageDownloadsAction->handle($product, $input->downloads);
            }

            return $product;
        });
    }
}
