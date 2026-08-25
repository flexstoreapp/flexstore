<?php

declare(strict_types=1);

namespace App\Queries;

use App\Filters\Criteria\BooleanCriterion;
use App\Filters\Criteria\SortCriterion;
use App\Filters\Criteria\TextSearchCriterion;
use App\Filters\CriteriaCollection;
use App\Filters\FilterManager;
use App\Filters\Strategies\TranslatableColumnSortStrategy;
use App\Models\TaxRate;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

final readonly class TaxRateListQuery
{
    /**
     * @param  FilterManager<TaxRate>  $filterManager
     */
    public function __construct(private FilterManager $filterManager)
    {
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, TaxRate>
     */
    public function execute(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        /** @var CriteriaCollection<TaxRate> $criteria */
        $criteria = new CriteriaCollection();

        /** @var TextSearchCriterion<TaxRate> $textSearch */
        $textSearch = new TextSearchCriterion(['name']);
        $criteria->add('query', $textSearch);

        /** @var BooleanCriterion<TaxRate> $isActive */
        $isActive = new BooleanCriterion('is_active');
        $criteria->add('is_active', $isActive);

        /** @var SortCriterion<TaxRate> $sortCriterion */
        $sortCriterion = new SortCriterion(
            allowedColumns: ['name', 'rate', 'region', 'tax_category', 'created_at'],
            columnStrategies: [
                'name' => new TranslatableColumnSortStrategy('name'),
            ],
            direction: $filters['direction'] ?? null,
        );
        $criteria->add('sort', $sortCriterion);

        $this->filterManager->setCriteria($criteria);

        return TaxRate::query()
            ->with(['region:id,name'])
            ->tap(fn (Builder $query): Builder => $this->filterManager->apply($query, $filters))
            ->paginate($perPage)
            ->withQueryString();
    }
}
