<?php

declare(strict_types=1);

namespace App\Queries;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Carbon\CarbonInterface;

final readonly class SitemapQuery
{
    /**
     * @return list<array{loc: string, lastmod: string|null, changefreq: string, priority: string}>
     */
    public function execute(): array
    {
        return [
            ...$this->staticPages(),
            ...$this->products(),
            ...$this->categories(),
            ...$this->brands(),
        ];
    }

    /**
     * @return list<array{loc: string, lastmod: string|null, changefreq: string, priority: string}>
     */
    private function staticPages(): array
    {
        return [
            $this->entry(route('home'), null, 'daily', '1.0'),
            $this->entry(route('shop.index'), null, 'daily', '0.9'),
            $this->entry(route('categories.index'), null, 'weekly', '0.6'),
            $this->entry(route('brands.index'), null, 'weekly', '0.6'),
            $this->entry(route('policies.refund'), null, 'yearly', '0.3'),
            $this->entry(route('policies.privacy'), null, 'yearly', '0.3'),
            $this->entry(route('policies.terms'), null, 'yearly', '0.3'),
        ];
    }

    /**
     * @return array<int, array{loc: string, lastmod: string|null, changefreq: string, priority: string}>
     */
    private function products(): array
    {
        return Product::query()
            ->where('is_active', true)
            ->orderBy('id')
            ->get(['url_handle', 'updated_at'])
            ->map(fn (Product $product): array => $this->entry(
                route('products.show', $product->url_handle),
                $product->updated_at,
                'weekly',
                '0.8',
            ))
            ->all();
    }

    /**
     * @return array<int, array{loc: string, lastmod: string|null, changefreq: string, priority: string}>
     */
    private function categories(): array
    {
        return Category::query()
            ->where('is_active', true)
            ->orderBy('id')
            ->get(['url_handle', 'updated_at'])
            ->map(fn (Category $category): array => $this->entry(
                route('categories.products.show', $category->url_handle),
                $category->updated_at,
                'weekly',
                '0.7',
            ))
            ->all();
    }

    /**
     * @return array<int, array{loc: string, lastmod: string|null, changefreq: string, priority: string}>
     */
    private function brands(): array
    {
        return Brand::query()
            ->where('is_active', true)
            ->orderBy('id')
            ->get(['url_handle', 'updated_at'])
            ->map(fn (Brand $brand): array => $this->entry(
                route('brands.products.show', $brand->url_handle),
                $brand->updated_at,
                'weekly',
                '0.5',
            ))
            ->all();
    }

    /**
     * @return array{loc: string, lastmod: string|null, changefreq: string, priority: string}
     */
    private function entry(string $loc, ?CarbonInterface $lastmod, string $changefreq, string $priority): array
    {
        return [
            'loc' => $loc,
            'lastmod' => $lastmod?->toAtomString(),
            'changefreq' => $changefreq,
            'priority' => $priority,
        ];
    }
}
