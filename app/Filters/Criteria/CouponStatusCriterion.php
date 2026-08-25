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
final readonly class CouponStatusCriterion implements Criterion
{
    public function canApply(mixed $value): bool
    {
        return in_array($value, ['active', 'inactive', 'expired', 'scheduled']);
    }

    public function apply(Builder $builder, mixed $value): Builder
    {
        return match ($value) {
            'active' => $builder->where('is_active', true)
                ->where(function (Builder $query): void {
                    $query->whereNull('starts_at')
                        ->orWhere('starts_at', '<=', now());
                })
                ->where(function (Builder $query): void {
                    $query->whereNull('expires_at')
                        ->orWhere('expires_at', '>=', now());
                }),
            'inactive' => $builder->where('is_active', false),
            'expired' => $builder->where('expires_at', '<', now()),
            'scheduled' => $builder->where('starts_at', '>', now()),
            default => $builder,
        };
    }
}
