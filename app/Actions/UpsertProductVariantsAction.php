<?php

declare(strict_types=1);

namespace App\Actions;

use App\DTOs\ProductVariantInput;
use App\DTOs\ProductVariantOptionInput;
use App\Enums\ProductType;
use App\Models\Media;
use App\Models\Product;
use App\Models\ProductVariant;
use DateTimeInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final readonly class UpsertProductVariantsAction
{
    /**
     * @param  list<ProductVariantInput>  $variants
     * @return Collection<int, ProductVariant>
     */
    public function handle(Product $product, array $variants): Collection
    {
        return DB::transaction(function () use ($product, $variants): Collection {
            $existingVariants = $product->variants()->get()->keyBy('id');

            if ($variants === []) {
                $existingVariants->each->delete();
                Media::deleteUnreferenced($existingVariants->pluck('media_id')->all());

                return collect();
            }

            $normalizedVariants = $this->normalizeDefaultVariants($variants);
            $variantIds = $this->processVariants($product, $normalizedVariants, $existingVariants);
            $this->cleanupOrphanedVariants($existingVariants, $variantIds);

            return $product->variants()->with('options')->whereIn('id', $variantIds)->get();
        });
    }

    /**
     * @param  list<ProductVariantInput>  $variants
     * @return list<ProductVariantInput>
     */
    private function normalizeDefaultVariants(array $variants): array
    {
        $hasDefault = false;
        $normalized = [];

        foreach ($variants as $variant) {
            $isDefault = $variant->isDefault && ! $hasDefault;
            if ($isDefault) {
                $hasDefault = true;
            }
            $normalized[] = $isDefault === $variant->isDefault
                ? $variant
                : new ProductVariantInput(
                    id: $variant->id,
                    title: $variant->title,
                    price: $variant->price,
                    compareAtPrice: $variant->compareAtPrice,
                    costPerItem: $variant->costPerItem,
                    sku: $variant->sku,
                    barcode: $variant->barcode,
                    trackStock: $variant->trackStock,
                    stock: $variant->stock,
                    lowStockThreshold: $variant->lowStockThreshold,
                    inStock: $variant->inStock,
                    weight: $variant->weight,
                    weightUnit: $variant->weightUnit,
                    length: $variant->length,
                    width: $variant->width,
                    height: $variant->height,
                    dimensionUnit: $variant->dimensionUnit,
                    mediaId: $variant->mediaId,
                    isDefault: $isDefault,
                    options: $variant->options,
                );
        }

        return $normalized;
    }

    /**
     * @param  list<ProductVariantInput>  $variants
     * @param  Collection<string, ProductVariant>  $existingVariants
     * @return list<string>
     */
    private function processVariants(Product $product, array $variants, Collection $existingVariants): array
    {
        $variantsToUpsert = [];
        $variantIds = [];
        $variantOptionsMap = [];
        $now = now();

        foreach ($variants as $variantInput) {
            $variantId = $variantInput->id ?? Str::uuid7()->toString();
            $variantIds[] = $variantId;

            $variantOptionsMap[$variantId] = $variantInput->options;
            $existingVariant = $existingVariants->get($variantId);

            $variantsToUpsert[] = $this->buildVariantRow($product, $variantId, $variantInput, $existingVariant, $now);
        }

        if ($variantsToUpsert !== []) {
            ProductVariant::query()->upsert(
                $variantsToUpsert,
                uniqueBy: ['id'],
                update: [
                    'title',
                    'price',
                    'compare_at_price',
                    'cost_per_item',
                    'sku',
                    'barcode',
                    'track_stock',
                    'stock',
                    'low_stock_threshold',
                    'in_stock',
                    'weight',
                    'weight_unit',
                    'length',
                    'width',
                    'height',
                    'dimension_unit',
                    'is_default',
                    'media_id',
                    'updated_at',
                ]
            );
        }

        $this->syncAllVariantOptions($variantIds, $variantOptionsMap);

        return $variantIds;
    }

    /**
     * @return array<string, mixed>
     */
    private function buildVariantRow(Product $product, string $variantId, ProductVariantInput $variant, ?ProductVariant $existingVariant, DateTimeInterface $now): array
    {
        $locale = app()->getLocale();
        $existingTranslations = $existingVariant?->getTranslations('title') ?? [];
        $mergedTitle = array_merge($existingTranslations, [$locale => $variant->title]);
        $isPhysical = $product->type === ProductType::Physical;

        return [
            'id' => $variantId,
            'product_id' => $product->id,
            'title' => json_encode($mergedTitle),
            'price' => $variant->price,
            'compare_at_price' => $variant->compareAtPrice,
            'cost_per_item' => $variant->costPerItem,
            'sku' => $variant->sku,
            'barcode' => $variant->barcode,
            'track_stock' => $isPhysical && $variant->trackStock,
            'stock' => $isPhysical && $variant->trackStock ? $variant->stock : null,
            'low_stock_threshold' => $isPhysical ? $variant->lowStockThreshold : null,
            'in_stock' => $isPhysical ? $variant->inStock : true,
            'weight' => $isPhysical ? $variant->weight : null,
            'weight_unit' => $isPhysical ? $variant->weightUnit?->value : null,
            'length' => $isPhysical ? $variant->length : null,
            'width' => $isPhysical ? $variant->width : null,
            'height' => $isPhysical ? $variant->height : null,
            'dimension_unit' => $isPhysical ? $variant->dimensionUnit?->value : null,
            'is_default' => $variant->isDefault,
            'media_id' => $variant->mediaId,
            'created_at' => $now,
            'updated_at' => $now,
        ];
    }

    /**
     * @param  Collection<string, ProductVariant>  $existingVariants
     * @param  list<string>  $variantIds
     */
    private function cleanupOrphanedVariants(Collection $existingVariants, array $variantIds): void
    {
        $orphanedVariants = $existingVariants->reject(fn (ProductVariant $v): bool => in_array($v->id, $variantIds, true));

        foreach ($orphanedVariants as $variant) {
            $variant->delete();
        }

        Media::deleteUnreferenced($orphanedVariants->pluck('media_id')->all());
    }

    /**
     * @param  list<string>  $variantIds
     * @param  array<string, list<ProductVariantOptionInput>>  $variantOptionsMap
     */
    private function syncAllVariantOptions(array $variantIds, array $variantOptionsMap): void
    {
        if ($variantIds === []) {
            return;
        }

        DB::table('product_variant_options')
            ->whereIn('product_variant_id', $variantIds)
            ->delete();

        $optionsToInsert = [];
        $now = now();

        foreach ($variantOptionsMap as $variantId => $options) {
            foreach ($options as $option) {
                $optionsToInsert[] = [
                    'product_variant_id' => $variantId,
                    'product_option_id' => $option->optionId,
                    'product_option_value_id' => $option->valueId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        if ($optionsToInsert !== []) {
            DB::table('product_variant_options')->insert($optionsToInsert);
        }
    }
}
