<?php

declare(strict_types=1);

namespace App\Queries;

use App\DTOs\ProductCard;
use App\DTOs\ProductFilterInput;
use App\Enums\ListLoadingMethod;
use App\Enums\ProductSortOption;
use App\Enums\SettingGroup;
use App\Filters\Configs\ProductFilterConfig;
use App\Filters\FilterManager;
use App\Models\Category;
use App\Models\Product;
use App\Models\Setting;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Builder;

final readonly class StorefrontProductListQuery
{
    /** @var list<int> */
    public const array PER_PAGE_OPTIONS = [24, 36, 48, 60];

    /**
     * @param  FilterManager<Product>  $filterManager
     */
    public function __construct(private FilterManager $filterManager)
    {
    }

    /**
     * @return LengthAwarePaginator<int, array<string, mixed>>|Paginator<int, array<string, mixed>>
     */
    public function execute(ProductFilterInput $input = new ProductFilterInput()): LengthAwarePaginator|Paginator
    {
        $settings = Setting::getByGroup(SettingGroup::Storefront);
        $perPage = $this->resolvePerPage($input->perPage, (int) $settings->get('storefront_product_list_default_per_page', 24));
        $loadingMethod = $settings->get('storefront_product_list_loading_method', ListLoadingMethod::Pagination->value);
        $sort = ProductSortOption::resolve(
            $input->sort,
            $input->hasSearch(),
            (string) $settings->get('storefront_product_list_default_sort', ProductSortOption::Latest->value),
        );

        $query = $this->buildBaseQuery();

        $this->applyContextFilters($query, $input->contextCategoryId, $input->contextBrandId);
        $this->filterManager
            ->setCriteria(ProductFilterConfig::getStorefrontCriteria($input->search))
            ->apply($query, $this->expandedFilters($input->toFilters($sort)));

        $useSimplePagination = in_array($loadingMethod, [
            ListLoadingMethod::InfiniteScroll->value,
            ListLoadingMethod::LoadMore->value,
        ], true);

        $paginator = $useSimplePagination
            ? $query->simplePaginate($perPage, ['*'], 'page', $input->page)->withQueryString()
            : $query->paginate($perPage, ['*'], 'page', $input->page)->withQueryString();

        return $paginator->through(fn (Product $product): array => $this->transformProduct($product));
    }

    private function resolvePerPage(?int $requested, int $default): int
    {
        return $requested !== null && in_array($requested, self::PER_PAGE_OPTIONS, true) ? $requested : $default;
    }

    /**
     * @return Builder<Product>
     */
    private function buildBaseQuery(): Builder
    {
        return Product::query()
            ->where('is_active', true)
            ->select(['id', 'url_handle', 'title', 'price', 'compare_at_price', 'in_stock', 'category_id', 'brand_id', 'created_at'])
            ->with([
                'brand:id,name,url_handle',
                'variants:id,product_id,price,compare_at_price,in_stock',
            ])
            ->withFeaturedMedia()
            ->withCount('variants');
    }

    /**
     * @param  Builder<Product>  $query
     */
    private function applyContextFilters(Builder $query, ?int $categoryId, ?int $brandId): void
    {
        if ($categoryId !== null) {
            $categoryIds = Category::query()->withDescendants($categoryId)->pluck('id');
            $query->whereIn('category_id', $categoryIds);
        }

        if ($brandId !== null) {
            $query->where('brand_id', $brandId);
        }
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    private function expandedFilters(array $filters): array
    {
        return $filters;
    }

    /**
     * @return array<string, mixed>
     */
    private function transformProduct(Product $product): array
    {
        return ProductCard::fromProduct($product)->toArray();
    }
}
