<?php

declare(strict_types=1);

namespace App\Queries;

use App\Filters\Criteria\TextSearchCriterion;
use App\Filters\CriteriaCollection;
use App\Filters\FilterManager;
use App\Models\Region;
use App\Utilities\TranslatableJsonExtract;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Builder;

final readonly class RegionSearchQuery
{
    /**
     * @param  FilterManager<Region>  $filterManager
     */
    public function __construct(private FilterManager $filterManager)
    {
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Paginator<int, Region>
     */
    public function execute(array $filters = []): Paginator
    {
        /** @var CriteriaCollection<Region> $criteria */
        $criteria = new CriteriaCollection();

        /** @var TextSearchCriterion<Region> $textSearch */
        $textSearch = new TextSearchCriterion(['name']);
        $criteria->add('query', $textSearch);

        $this->filterManager->setCriteria($criteria);

        return Region::query()
            ->select(['id', 'name'])
            ->where('is_active', true)
            ->tap(fn (Builder $query): Builder => $this->filterManager->apply($query, $filters))
            ->orderByRaw(...TranslatableJsonExtract::orderByExpression('name'))
            ->simplePaginate();
    }
}
