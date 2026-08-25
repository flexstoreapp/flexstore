<?php

declare(strict_types=1);

namespace App\Queries;

use App\Filters\Criteria\BooleanCriterion;
use App\Filters\Criteria\CouponStatusCriterion;
use App\Filters\Criteria\CouponUsageCriterion;
use App\Filters\Criteria\ExactMatchCriterion;
use App\Filters\Criteria\SortCriterion;
use App\Filters\Criteria\TextSearchCriterion;
use App\Filters\CriteriaCollection;
use App\Filters\FilterManager;
use App\Models\Coupon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

final readonly class CouponListQuery
{
    /**
     * @param  FilterManager<Coupon>  $filterManager
     */
    public function __construct(private FilterManager $filterManager)
    {
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, Coupon>
     */
    public function execute(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        /** @var CriteriaCollection<Coupon> $criteria */
        $criteria = new CriteriaCollection();

        /** @var TextSearchCriterion<Coupon> $textSearch */
        $textSearch = new TextSearchCriterion(['code']);
        $criteria->add('search', $textSearch);

        /** @var ExactMatchCriterion<Coupon> $typeMatch */
        $typeMatch = new ExactMatchCriterion('type');
        $criteria->add('type', $typeMatch);

        /** @var CouponStatusCriterion<Coupon> $statusCriterion */
        $statusCriterion = new CouponStatusCriterion();
        $criteria->add('status', $statusCriterion);

        /** @var CouponUsageCriterion<Coupon> $usageCriterion */
        $usageCriterion = new CouponUsageCriterion();
        $criteria->add('usage', $usageCriterion);

        /** @var BooleanCriterion<Coupon> $isActive */
        $isActive = new BooleanCriterion('is_active');
        $criteria->add('is_active', $isActive);

        /** @var SortCriterion<Coupon> $sortCriterion */
        $sortCriterion = new SortCriterion(
            ['code', 'value', 'starts_at', 'expires_at', 'used_count', 'created_at'],
            direction: $filters['direction'] ?? null,
        );
        $criteria->add('sort', $sortCriterion);

        $this->filterManager->setCriteria($criteria);

        return Coupon::query()
            ->tap(fn (Builder $query): Builder => $this->filterManager->apply($query, $filters))
            ->paginate($perPage)
            ->withQueryString();
    }
}
