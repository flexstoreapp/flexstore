<?php

declare(strict_types=1);

namespace App\Queries;

use App\Filters\Configs\ProductFilterConfig;
use App\Filters\FilterManager;
use App\Models\Media;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ProductVariantOption;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

final readonly class ProductSearchQuery
{
    /**
     * @param  FilterManager<Product>  $filterManager
     */
    public function __construct(private FilterManager $filterManager)
    {
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Paginator<int, array<string, mixed>>
     */
    public function execute(array $filters = [], bool $withVariants = false): Paginator
    {
        $this->filterManager->setCriteria(ProductFilterConfig::getCriteria($filters['direction'] ?? null));

        return Product::query() // @phpstan-ignore return.type
            ->select(['id', 'type', 'title', 'price'])
            ->withFeaturedMedia()
            ->when($withVariants, fn (Builder $query): Builder => $query->with([
                'variants' => fn (Relation $query): Relation => $query
                    ->select(['id', 'product_id', 'title', 'price', 'media_id'])
                    ->with([
                        'options' => fn (Relation $query) => $query
                            ->select(['id', 'product_variant_id', 'product_option_id', 'product_option_value_id'])
                            ->with(['option:id,name', 'value:id,value']),
                        'media:' . Media::displaySelect(),
                    ]),
            ]), fn (Builder $query): Builder => $query->with('variants:id,product_id,price'))
            ->where('is_active', true)
            ->tap(fn (Builder $query): Builder => $this->filterManager->apply($query, $filters))
            ->simplePaginate()
            ->through(function (Product $product) use ($withVariants): array {
                $product->append(['featured_media', 'price_range'])->makeHidden(['mediaGallery', 'variants']);

                return [
                    ...$product->toArray(),
                    ...$withVariants ? [
                        'variants' => $product->variants->map(fn (ProductVariant $variant): array => [
                            ...$variant->toArray(),
                            'media' => $variant->media,
                            'options' => $variant->options->map(fn (ProductVariantOption $variantOption): array => [
                                'option_id' => $variantOption->product_option_id,
                                'value_id' => $variantOption->product_option_value_id,
                                'name' => $variantOption->option->name,
                                'value' => $variantOption->value->value,
                            ]),
                        ]),
                    ] : [],
                ];
            });
    }
}
