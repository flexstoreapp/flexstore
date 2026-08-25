<?php

declare(strict_types=1);

namespace App\Filters;

use App\Filters\Contracts\Criterion;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * @template TModel of Model
 */
final readonly class CriteriaCollection
{
    /**
     * @param  Collection<string, Criterion<TModel>>  $criteria
     */
    public function __construct(private Collection $criteria = new Collection())
    {
    }

    /**
     * @param  Criterion<TModel>  $criterion
     * @return CriteriaCollection<TModel>
     */
    public function add(string $key, Criterion $criterion): self
    {
        $this->criteria->put($key, $criterion);

        return $this;
    }

    /**
     * @param  Builder<TModel>  $builder
     * @param  array<string, mixed>  $filters
     * @return Builder<TModel>
     */
    public function apply(Builder $builder, array $filters): Builder
    {
        $this->applyMatchingFilters($builder, $filters);
        $this->applyDefaultSortIfNeeded($builder, $filters);

        return $builder;
    }

    public function has(string $key): bool
    {
        return $this->criteria->has($key);
    }

    /**
     * @return Criterion<TModel>|null
     */
    public function get(string $key): ?Criterion
    {
        return $this->criteria->get($key);
    }

    /**
     * @return array<int, string>
     */
    public function keys(): array
    {
        return $this->criteria->keys()->all();
    }

    /**
     * @param  Builder<TModel>  $builder
     * @param  array<string, mixed>  $filters
     */
    private function applyMatchingFilters(Builder $builder, array $filters): void
    {
        foreach ($filters as $key => $value) {
            if (! $this->criteria->has($key)) {
                continue;
            }

            $criterion = $this->criteria->get($key);

            if ($criterion?->canApply($value)) {
                $criterion->apply($builder, $value);
            }
        }

    }

    /**
     * @param  Builder<TModel>  $builder
     * @param  array<string, mixed>  $filters
     */
    private function applyDefaultSortIfNeeded(Builder $builder, array $filters): void
    {
        $sortCriterion = $this->criteria->get('sort');

        if ($sortCriterion && ! array_key_exists('sort', $filters)) {
            $sortCriterion->apply($builder, null);
        }
    }
}
