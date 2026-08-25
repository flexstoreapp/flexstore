<?php

declare(strict_types=1);

namespace App\Filters\Contracts;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * @template TModel of Model
 */
interface ColumnSortStrategy
{
    /**
     * @param  Builder<TModel>  $builder
     * @param  'asc'|'desc'  $direction
     * @return Builder<TModel>
     */
    public function apply(Builder $builder, string $direction): Builder;
}
