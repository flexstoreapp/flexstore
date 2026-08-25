<?php

declare(strict_types=1);

namespace App\Queries;

use App\Filters\Criteria\BooleanCriterion;
use App\Filters\Criteria\SortCriterion;
use App\Filters\Criteria\TextSearchCriterion;
use App\Filters\CriteriaCollection;
use App\Filters\FilterManager;
use App\Models\Currency;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

final readonly class CurrencyListQuery
{
    /**
     * @param  FilterManager<Currency>  $filterManager
     */
    public function __construct(private FilterManager $filterManager)
    {
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, Currency>
     */
    public function execute(array $filters = []): Collection
    {
        /** @var CriteriaCollection<Currency> $criteria */
        $criteria = new CriteriaCollection();

        /** @var TextSearchCriterion<Currency> $textSearch */
        $textSearch = new TextSearchCriterion(['name', 'code']);
        $criteria->add('query', $textSearch);

        /** @var BooleanCriterion<Currency> $isActive */
        $isActive = new BooleanCriterion('is_active');
        $criteria->add('is_active', $isActive);

        /** @var SortCriterion<Currency> $sortCriterion */
        $sortCriterion = new SortCriterion(['code', 'exchange_rate', 'created_at'], direction: $filters['direction'] ?? null);
        $criteria->add('sort', $sortCriterion);

        $this->filterManager->setCriteria($criteria);

        return Currency::query()
            ->tap(fn (Builder $query): Builder => $this->filterManager->apply($query, $filters))
            ->get();
    }
}
