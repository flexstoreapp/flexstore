<?php

declare(strict_types=1);

namespace App\Queries;

use App\Models\Brand;
use App\Models\Category;
use App\Models\PaymentGateway;
use App\Models\Product;
use App\Models\Region;
use Illuminate\Support\Collection;

final readonly class PaymentGatewayListQuery
{
    /**
     * @return Collection<int, PaymentGateway>
     */
    public function execute(): Collection
    {
        $paymentGateways = PaymentGateway::all()->makeVisible('credentials');

        $excludedProducts = $this->getExcludedProducts($paymentGateways);
        $excludedCategories = $this->getExcludedCategories($paymentGateways);
        $excludedBrands = $this->getExcludedBrands($paymentGateways);
        $allowedRegions = $this->getAllowedRegions($paymentGateways);

        $paymentGateways->each(function (PaymentGateway $paymentGateway) use ($excludedProducts, $excludedCategories, $excludedBrands, $allowedRegions): void {
            $paymentGateway->fill([
                'excluded_products' => $excludedProducts->filter(fn (Product $product): bool => in_array($product->id, $paymentGateway->excluded_products))->values(),
                'excluded_categories' => $excludedCategories->filter(fn (Category $category): bool => in_array($category->id, $paymentGateway->excluded_categories))->values(),
                'excluded_brands' => $excludedBrands->filter(fn (Brand $brand): bool => in_array($brand->id, $paymentGateway->excluded_brands))->values(),
                'allowed_regions' => $allowedRegions->filter(fn (Region $region): bool => in_array($region->id, $paymentGateway->allowed_regions))->values(),
                'supported_currencies' => array_map(fn (string $code): array => [
                    'code' => $code,
                    'name' => $code,
                ], $paymentGateway->supported_currencies ?? []),
            ]);
        });

        return $paymentGateways;
    }

    /**
     * @param  Collection<int, PaymentGateway>  $paymentGateways
     * @return Collection<int, Product>
     */
    private function getExcludedProducts(Collection $paymentGateways): Collection
    {
        $excludedProductIds = $paymentGateways->pluck('excluded_products')->flatten()->unique();

        if ($excludedProductIds->isEmpty()) {
            return collect();
        }

        return Product::query()
            ->whereIn('id', $excludedProductIds)
            ->withFeaturedMedia()
            ->get(['id', 'type', 'title', 'price'])
            ->append('featured_media')
            ->makeHidden(['mediaGallery']);
    }

    /**
     * @param  Collection<int, PaymentGateway>  $paymentGateways
     * @return Collection<int, Category>
     */
    private function getExcludedCategories(Collection $paymentGateways): Collection
    {
        $excludedCategoryIds = $paymentGateways->pluck('excluded_categories')->flatten()->unique();

        if ($excludedCategoryIds->isEmpty()) {
            return collect();
        }

        return Category::query()->whereIn('id', $excludedCategoryIds)->get(['id', 'name']);
    }

    /**
     * @param  Collection<int, PaymentGateway>  $paymentGateways
     * @return Collection<int, Brand>
     */
    private function getExcludedBrands(Collection $paymentGateways): Collection
    {
        $excludedBrandIds = $paymentGateways->pluck('excluded_brands')->flatten()->unique();

        if ($excludedBrandIds->isEmpty()) {
            return collect();
        }

        return Brand::query()->whereIn('id', $excludedBrandIds)->get(['id', 'name']);
    }

    /**
     * @param  Collection<int, PaymentGateway>  $paymentGateways
     * @return Collection<int, Region>
     */
    private function getAllowedRegions(Collection $paymentGateways): Collection
    {
        $allowedRegionIds = $paymentGateways->pluck('allowed_regions')->flatten()->unique();

        if ($allowedRegionIds->isEmpty()) {
            return collect();
        }

        return Region::query()->whereIn('id', $allowedRegionIds)->get(['id', 'name']);
    }
}
