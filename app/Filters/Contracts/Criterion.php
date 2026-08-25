<?php

declare(strict_types=1);

namespace App\Filters\Contracts;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * @template TModel of Model
 */
interface Criterion
{
    public function canApply(mixed $value): bool;

    /**
     * @param  Builder<TModel>  $builder
     * @return Builder<TModel>
     */
    public function apply(Builder $builder, mixed $value): Builder;
}
