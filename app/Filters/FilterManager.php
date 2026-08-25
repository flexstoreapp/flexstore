<?php

declare(strict_types=1);

namespace App\Filters;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * @template TModel of Model
 */
final class FilterManager
{
    /**
     * @param  CriteriaCollection<TModel>  $criteria
     */
    public function __construct(
        private CriteriaCollection $criteria = new CriteriaCollection(),
    ) {
    }

    /**
     * @param  CriteriaCollection<TModel>  $criteria
     * @return FilterManager<TModel>
     */
    public function setCriteria(CriteriaCollection $criteria): self
    {
        $this->criteria = $criteria;

        return $this;
    }

    /**
     * @param  Builder<TModel>  $builder
     * @param  array<string, mixed>  $filters
     * @return Builder<TModel>
     */
    public function apply(Builder $builder, array $filters): Builder
    {
        return $this->criteria->apply($builder, $filters);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function getFilterValue(string $key, array $filters): mixed
    {
        if (! $this->criteria->has($key)) {
            return null;
        }

        return $filters[$key] ?? null;
    }
}
