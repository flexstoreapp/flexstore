<?php

declare(strict_types=1);

namespace App\Queries;

use App\Enums\ReviewStatus;
use App\Models\Category;
use App\Models\Product;
use App\Models\Review;

final readonly class ProductDetailQuery
{
    public function __construct(
        private ProductBuyBoxQuery $productBuyBoxQuery,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function execute(Product $product): array
    {
        $buyBox = $this->productBuyBoxQuery->execute($product);

        return [
            ...$buyBox,
            'seo_title' => $product->getTranslations('seo_title'),
            'seo_description' => $product->getTranslations('seo_description'),
            'barcode' => $product->barcode,
            'type' => $product->type->value,
            'category' => $buyBox['category'] === null ? null : [
                ...$buyBox['category'],
                'ancestors' => $this->ancestors($product),
            ],
            'rating_distribution' => $this->ratingDistribution($product),
        ];
    }

    /**
     * @return list<array{name: array<string, string>, url_handle: string}>
     */
    private function ancestors(Product $product): array
    {
        if ($product->category_id === null) {
            return [];
        }

        $ancestors = Category::query()->find($product->category_id)?->ancestors() ?? collect();

        return array_values($ancestors->map(fn (Category $category): array => [
            'name' => $category->getTranslations('name'),
            'url_handle' => $category->url_handle,
        ])->all());
    }

    /**
     * @return array<int, int>
     */
    private function ratingDistribution(Product $product): array
    {
        $counts = Review::query()
            ->where('product_id', $product->id)
            ->where('status', ReviewStatus::Approved)
            ->groupBy('rating')
            ->selectRaw('rating, count(*) as total')
            ->pluck('total', 'rating');

        return [
            5 => (int) $counts->get(5, 0),
            4 => (int) $counts->get(4, 0),
            3 => (int) $counts->get(3, 0),
            2 => (int) $counts->get(2, 0),
            1 => (int) $counts->get(1, 0),
        ];
    }
}
