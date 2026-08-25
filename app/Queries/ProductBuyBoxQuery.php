<?php

declare(strict_types=1);

namespace App\Queries;

use App\Enums\MediaType;
use App\Enums\ReviewStatus;
use App\Models\Media;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Setting;
use Illuminate\Database\Eloquent\Relations\Relation;

final readonly class ProductBuyBoxQuery
{
    public function __construct(private AvailableStockQuery $availableStockQuery)
    {
    }

    /**
     * @return array<string, mixed>
     */
    public function execute(Product $product): array
    {
        $product->load([
            'brand:id,name,url_handle',
            'category:id,name,url_handle',
            'options' => fn (Relation $q): Relation => $q->orderBy('id'),
            'options.values' => fn (Relation $q): Relation => $q->orderBy('id'),
            'variants' => fn (Relation $q): Relation => $q->orderBy('id'),
            'variants.options',
            'variants.media:' . Media::displaySelect(),
            'mediaGallery:' . Media::displaySelect(),
        ]);

        $product->loadCount([
            'reviews as review_count' => fn ($q) => $q->where('status', ReviewStatus::Approved),
        ]);
        $product->loadAvg([
            'reviews as rating' => fn ($q) => $q->where('status', ReviewStatus::Approved),
        ], 'rating');

        $hasVariants = $product->variants->isNotEmpty();
        $inStock = $hasVariants
            ? $product->variants->contains('in_stock', true)
            : ($product->in_stock ?? false);

        $pricesIncludeTax = (bool) Setting::getValue('prices_include_tax', false);

        $availableStock = $this->resolveAvailableStock($product, $hasVariants);

        return [
            'id' => $product->id,
            'url_handle' => $product->url_handle,
            'title' => $product->getTranslations('title'),
            'description' => $product->getTranslations('description'),
            'sku' => $product->sku,
            'brand' => $product->brand === null ? null : [
                'name' => $product->brand->getTranslations('name'),
                'url_handle' => $product->brand->url_handle,
            ],
            'category' => $product->category === null ? null : [
                'name' => $product->category->getTranslations('name'),
                'url_handle' => $product->category->url_handle,
            ],
            'price' => $product->price,
            'price_range' => $product->price_range,
            'compare_at_price' => $product->compare_at_price,
            'compare_at_price_range' => $product->compare_at_price_range,
            'in_stock' => $inStock,
            'max_quantity' => $hasVariants ? null : ($product->track_stock ? ($availableStock[$product->id . ':null'] ?? 0) : null),
            'rating' => $product->getAttribute('rating') === null ? null : round((float) $product->getAttribute('rating'), 1),
            'review_count' => (int) $product->getAttribute('review_count'),
            'prices_include_tax' => $pricesIncludeTax,
            'has_variants' => $hasVariants,
            'media' => $this->gallery($product),
            'featured_media' => $product->featured_media,
            'options' => $product->options->map(fn ($option): array => [
                'id' => $option->id,
                'name' => $option->getTranslations('name'),
                'values' => $option->values->map(fn ($value): array => [
                    'id' => $value->id,
                    'value' => $value->getTranslations('value'),
                ])->all(),
            ])->all(),
            'variants' => $product->variants->map(fn (ProductVariant $variant): array => [
                'id' => $variant->id,
                'title' => $variant->getTranslations('title'),
                'sku' => $variant->sku,
                'barcode' => $variant->barcode,
                'price' => $variant->price,
                'compare_at_price' => $variant->compare_at_price,
                'in_stock' => $variant->in_stock,
                'is_default' => $variant->is_default,
                'max_quantity' => $variant->track_stock ? ($availableStock[$product->id . ':' . $variant->id] ?? 0) : null,
                'media' => $variant->media,
                'option_values' => $variant->options
                    ->mapWithKeys(fn ($o): array => [$o->product_option_id => $o->product_option_value_id])
                    ->all(),
            ])->all(),
        ];
    }

    /**
     * @return array<string, int>
     */
    private function resolveAvailableStock(Product $product, bool $hasVariants): array
    {
        $items = [];

        if (! $hasVariants && $product->track_stock) {
            $items[] = ['product_id' => $product->id, 'product_variant_id' => null];
        }

        foreach ($product->variants as $variant) {
            if ($variant->track_stock) {
                $items[] = ['product_id' => $product->id, 'product_variant_id' => $variant->id];
            }
        }

        return $items === [] ? [] : $this->availableStockQuery->executeMany($items);
    }

    /**
     * @return list<Media>
     */
    private function gallery(Product $product): array
    {
        return array_values($product->mediaGallery
            ->filter(fn (Media $media): bool => $media->type === MediaType::Image)
            ->all());
    }
}
