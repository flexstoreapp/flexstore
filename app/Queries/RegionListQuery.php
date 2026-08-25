<?php

declare(strict_types=1);

namespace App\Queries;

use App\Filters\Configs\RegionFilterConfig;
use App\Filters\FilterManager;
use App\Models\Region;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

final readonly class RegionListQuery
{
    /**
     * @param  FilterManager<Region>  $filterManager
     */
    public function __construct(private FilterManager $filterManager)
    {
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, Region>
     */
    public function execute(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $this->filterManager->setCriteria(RegionFilterConfig::getCriteria($filters['direction'] ?? null));

        return Region::query()
            ->tap(fn (Builder $query): Builder => $this->filterManager->apply($query, $filters))
            ->paginate($perPage)
            ->withQueryString();
    }
}
