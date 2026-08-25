<?php

declare(strict_types=1);

namespace App\Filters\Criteria;

use App\Filters\Contracts\Criterion;
use Illuminate\Database\Eloquent\Builder;

/**
 * @template TModel of \App\Models\Coupon
 *
 * @implements Criterion<TModel>
 */
final readonly class CouponUsageCriterion implements Criterion
{
    public function canApply(mixed $value): bool
    {
        return in_array($value, ['unlimited', 'limited', 'available']);
    }

    public function apply(Builder $query, mixed $value): Builder
    {
        return match ($value) {
            'unlimited' => $query->whereNull('usage_limit'),
            'limited' => $query->whereNotNull('usage_limit'),
            'available' => $query->where(function (Builder $q): void {
                $q->whereNull('usage_limit')
                    ->orWhereColumn('used_count', '<', 'usage_limit');
            }),
            default => $query,
        };
    }
}
