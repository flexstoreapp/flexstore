<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Admin;

use App\Http\Resources\Api\V1\MediaResource;
use App\Models\Media;
use App\Models\Product;
use App\Models\ProductDownload;
use App\Models\ProductOption;
use App\Models\ProductOptionValue;
use App\Models\ProductVariant;
use App\Models\ProductVariantOption;
use App\Utilities\LocalizedText;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

/**
 * @property-read Product $resource
 */
final class AdminProductResource extends JsonResource
{
    /**
     * @var list<string>
     */
    public const array RELATIONS = [
        'category:id,name',
        'brand:id,name',
        'mediaGallery',
        'options.values',
        'variants.media',
        'variants.options.option',
        'variants.options.value',
        'downloads.media',
        'crossSells.mediaGallery',
        'crossSells.variants:id,product_id,price',
        'upSells.mediaGallery',
        'upSells.variants:id,product_id,price',
    ];

    /**
     * @return array<string, mixed>
     */
    #[Override]
    public function toArray(Request $request): array
    {
        $product = $this->resource;
        $category = $product->category;
        $brand = $product->brand;

        return [
            'id' => $product->id,
            'type' => $product->type->value,
            'url_handle' => $product->url_handle,
            'title' => LocalizedText::resolve($product->getTranslations('title')),
            'description' => LocalizedText::resolve($product->getTranslations('description')),
            'category' => $category === null ? null : [
                'id' => $category->id,
                'name' => LocalizedText::resolve($category->getTranslations('name')),
            ],
            'brand' => $brand === null ? null : [
                'id' => $brand->id,
                'name' => LocalizedText::resolve($brand->getTranslations('name')),
            ],
            'tax_category' => $product->tax_category?->value,
            'is_tax_exempt' => $product->is_tax_exempt,
            'price' => $product->price,
            'price_range' => $product->price_range,
            'compare_at_price' => $product->compare_at_price,
            'cost_per_item' => $product->cost_per_item,
            'sku' => $product->sku,
            'barcode' => $product->barcode,
            'track_stock' => $product->track_stock,
            'stock' => $product->stock,
            'total_stock' => $product->total_stock,
            'low_stock_threshold' => $product->low_stock_threshold,
            'in_stock' => $product->in_stock,
            'is_active' => $product->is_active,
            'weight' => $product->weight,
            'weight_unit' => $product->weight_unit?->value,
            'length' => $product->length,
            'width' => $product->width,
            'height' => $product->height,
            'dimension_unit' => $product->dimension_unit?->value,
            'seo_title' => LocalizedText::resolve($product->getTranslations('seo_title')),
            'seo_description' => LocalizedText::resolve($product->getTranslations('seo_description')),
            'media' => MediaResource::collection($product->mediaGallery),
            'options' => $this->options($product->options),
            'variants' => $this->variants($product->variants),
            'downloads' => $this->downloads($product->downloads),
            'cross_sells' => $this->relatedProducts($product->crossSells),
            'up_sells' => $this->relatedProducts($product->upSells),
            'created_at' => $product->created_at->toIso8601String(),
            'updated_at' => $product->updated_at->toIso8601String(),
        ];
    }

    /**
     * @param  Collection<int, ProductOption>  $options
     * @return list<array<string, mixed>>
     */
    private function options(Collection $options): array
    {
        return array_values($options->map(fn (ProductOption $option): array => [
            'id' => $option->id,
            'name' => LocalizedText::resolve($option->getTranslations('name')),
            'values' => $option->values->map(fn (ProductOptionValue $value): array => [
                'id' => $value->id,
                'value' => LocalizedText::resolve($value->getTranslations('value')),
            ])->values()->all(),
        ])->all());
    }

    /**
     * @param  Collection<int, ProductVariant>  $variants
     * @return list<array<string, mixed>>
     */
    private function variants(Collection $variants): array
    {
        return array_values($variants->map(fn (ProductVariant $variant): array => [
            'id' => $variant->id,
            'title' => LocalizedText::resolve($variant->getTranslations('title')),
            'sku' => $variant->sku,
            'barcode' => $variant->barcode,
            'price' => $variant->price,
            'compare_at_price' => $variant->compare_at_price,
            'cost_per_item' => $variant->cost_per_item,
            'track_stock' => $variant->track_stock,
            'stock' => $variant->stock,
            'low_stock_threshold' => $variant->low_stock_threshold,
            'in_stock' => $variant->in_stock,
            'weight' => $variant->weight,
            'weight_unit' => $variant->weight_unit?->value,
            'length' => $variant->length,
            'width' => $variant->width,
            'height' => $variant->height,
            'dimension_unit' => $variant->dimension_unit?->value,
            'is_default' => $variant->is_default,
            'media' => $variant->media instanceof Media ? new MediaResource($variant->media) : null,
            'options' => $variant->options->map(fn (ProductVariantOption $variantOption): array => [
                'option_id' => $variantOption->product_option_id,
                'value_id' => $variantOption->product_option_value_id,
                'name' => LocalizedText::resolve($variantOption->option->getTranslations('name')),
                'value' => LocalizedText::resolve($variantOption->value->getTranslations('value')),
            ])->values()->all(),
        ])->all());
    }

    /**
     * @param  Collection<int, ProductDownload>  $downloads
     * @return list<array<string, mixed>>
     */
    private function downloads(Collection $downloads): array
    {
        return array_values($downloads->map(fn (ProductDownload $download): array => [
            'id' => $download->id,
            'variant_id' => $download->product_variant_id,
            'name' => $download->name,
            'media_id' => $download->media_id,
            'original_filename' => $download->media->original_filename,
            'file_size' => $download->media->size,
            'mime_type' => $download->media->mime_type,
            'sort_order' => $download->sort_order,
        ])->all());
    }

    /**
     * @param  Collection<int, Product>  $products
     * @return list<array<string, mixed>>
     */
    private function relatedProducts(Collection $products): array
    {
        return array_values($products->map(fn (Product $related): array => [
            'id' => $related->id,
            'type' => $related->type->value,
            'title' => LocalizedText::resolve($related->getTranslations('title')),
            'price' => $related->price,
            'price_range' => $related->price_range,
            'featured_media' => $related->featured_media instanceof Media
                ? new MediaResource($related->featured_media)
                : null,
        ])->all());
    }
}
