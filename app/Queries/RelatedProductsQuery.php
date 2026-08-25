<?php

declare(strict_types=1);

namespace App\Queries;

use App\Enums\ProductSource;
use App\Enums\ReviewStatus;
use App\Models\Category;
use App\Models\Product;
use Brick\Math\BigDecimal;
use Illuminate\Contracts\Database\Query\Builder;

final readonly class RelatedProductsQuery
{
    /**
     * Cap on candidates scored in memory, protecting against pathologically large categories.
     */
    private const int CANDIDATE_CAP = 200;

    /**
     * Fraction above/below the current price counted as a "similar" price point.
     */
    private const string PRICE_BAND = '0.30';

    public function __construct(
        private SectionProductsQuery $sectionProductsQuery,
    ) {
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function execute(Product $product, int $limit): array
    {
        $ids = $this->rankedIds($product, $limit);

        if (count($ids) < $limit) {
            $ids = [...$ids, ...$this->broadenIds($product, $limit - count($ids), $ids)];
        }

        if (count($ids) < $limit) {
            $ids = [...$ids, ...$this->fillerIds($product, $limit - count($ids), $ids)];
        }

        if ($ids === []) {
            return [];
        }

        return $this->sectionProductsQuery->execute([
            'product_source' => ProductSource::Featured->value,
            'product_ids' => $ids,
        ]);
    }

    /**
     * Score category/brand candidates on cheap columns, then keep the best ids.
     *
     * @return list<int>
     */
    private function rankedIds(Product $product, int $limit): array
    {
        $categoryIds = $product->category_id === null
            ? []
            : Category::query()->withDescendants($product->category_id)->pluck('id')->all();

        if ($categoryIds === [] && $product->brand_id === null) {
            return [];
        }

        $categorySet = array_flip($categoryIds);
        [$low, $high] = $this->priceBand($product->price);

        $ids = Product::query()
            ->select(['id', 'category_id', 'brand_id', 'price', 'in_stock'])
            ->withCount(['reviews as review_count' => fn (Builder $query): Builder => $query->where('status', ReviewStatus::Approved->value)])
            ->where('is_active', true)
            ->whereKeyNot($product->id)
            ->where(function (Builder $query) use ($categoryIds, $product): void {
                if ($categoryIds !== []) {
                    $query->whereIn('category_id', $categoryIds);
                }
                if ($product->brand_id !== null) {
                    $query->orWhere('brand_id', $product->brand_id);
                }
            })
            ->latest('id')
            ->limit(self::CANDIDATE_CAP)
            ->get()
            ->sortByDesc(fn (Product $candidate): array => [
                $this->score($candidate, $categorySet, $product->brand_id, $low, $high),
                $candidate->in_stock ? 1 : 0,
                (int) $candidate->getAttribute('review_count'),
                $candidate->id,
            ])
            ->take($limit)
            ->pluck('id')
            ->all();

        return array_map(static fn (mixed $id): int => (int) $id, array_values($ids));
    }

    /**
     * @param  array<int, int>  $categorySet
     */
    private function score(Product $candidate, array $categorySet, ?int $brandId, ?BigDecimal $low, ?BigDecimal $high): int
    {
        $score = 0;

        if ($candidate->category_id !== null && isset($categorySet[$candidate->category_id])) {
            $score += 3;
        }

        if ($brandId !== null && $candidate->brand_id === $brandId) {
            $score += 2;
        }

        if ($low instanceof BigDecimal && $high instanceof BigDecimal && $candidate->price !== null) {
            $price = BigDecimal::of($candidate->price);
            if ($price->isGreaterThanOrEqualTo($low) && $price->isLessThanOrEqualTo($high)) {
                $score += 2;
            }
        }

        return $score;
    }

    /**
     * @return array{0: ?BigDecimal, 1: ?BigDecimal}
     */
    private function priceBand(?string $price): array
    {
        if ($price === null) {
            return [null, null];
        }

        $base = BigDecimal::of($price);
        $delta = $base->multipliedBy(self::PRICE_BAND);

        return [$base->minus($delta), $base->plus($delta)];
    }

    /**
     * Fill from progressively broader ancestor categories (nearest department first).
     *
     * @param  list<int>  $exclude
     * @return list<int>
     */
    private function broadenIds(Product $product, int $count, array $exclude): array
    {
        if ($product->category_id === null) {
            return [];
        }

        $category = Category::query()->find($product->category_id);

        if ($category === null) {
            return [];
        }

        $picked = [];

        foreach ($category->ancestors()->reverse() as $ancestor) {
            $need = $count - count($picked);

            if ($need <= 0) {
                break;
            }

            $scope = Category::query()->withDescendants($ancestor->id)->pluck('id')->all();

            $ids = Product::query()
                ->where('is_active', true)
                ->whereKeyNot($product->id)
                ->whereIn('category_id', $scope)
                ->whereNotIn('id', [...$exclude, ...$picked])
                ->latest('id')
                ->limit($need)
                ->pluck('id')
                ->all();

            $picked = [...$picked, ...array_map(static fn (mixed $id): int => (int) $id, array_values($ids))];
        }

        return $picked;
    }

    /**
     * @param  list<int>  $exclude
     * @return list<int>
     */
    private function fillerIds(Product $product, int $count, array $exclude): array
    {
        $ids = Product::query()
            ->where('is_active', true)
            ->whereKeyNot($product->id)
            ->whereNotIn('id', $exclude)
            ->latest('id')
            ->limit($count)
            ->pluck('id')
            ->all();

        return array_map(static fn (mixed $id): int => (int) $id, array_values($ids));
    }
}
