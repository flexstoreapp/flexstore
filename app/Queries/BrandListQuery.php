<?php

declare(strict_types=1);

namespace App\Queries;

use App\Filters\Configs\BrandFilterConfig;
use App\Filters\FilterManager;
use App\Models\Brand;
use App\Models\Media;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

final readonly class BrandListQuery
{
    /**
     * @param  FilterManager<Brand>  $filterManager
     */
    public function __construct(private FilterManager $filterManager)
    {
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, Brand>
     */
    public function execute(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $this->filterManager->setCriteria(BrandFilterConfig::getCriteria($filters['direction'] ?? null));

        return Brand::query()
            ->with(['image:' . Media::displaySelect()])
            ->withCount('products')
            ->tap(fn (Builder $query): Builder => $this->filterManager->apply($query, $filters))
            ->paginate($perPage)
            ->withQueryString();
    }
}
