<?php

declare(strict_types=1);

namespace App\Queries;

use App\Enums\Role as RoleEnum;
use App\Filters\Criteria\SortCriterion;
use App\Filters\Criteria\TextSearchCriterion;
use App\Filters\CriteriaCollection;
use App\Filters\FilterManager;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

final readonly class CustomerListQuery
{
    /**
     * @param  FilterManager<User>  $filterManager
     */
    public function __construct(private FilterManager $filterManager)
    {
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, User>
     */
    public function execute(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        /** @var CriteriaCollection<User> $criteria */
        $criteria = new CriteriaCollection();

        /** @var TextSearchCriterion<User> $textSearch */
        $textSearch = new TextSearchCriterion(['name', 'email']);
        $criteria->add('query', $textSearch);

        /** @var SortCriterion<User> $sortCriterion */
        $sortCriterion = new SortCriterion(['name', 'email', 'order_count', 'lifetime_value', 'last_login_at', 'created_at'], direction: $filters['direction'] ?? null);
        $criteria->add('sort', $sortCriterion);

        $this->filterManager->setCriteria($criteria);

        return User::query()
            ->select([
                'id',
                'name',
                'email',
                'last_login_at',
                'lifetime_value',
                'created_at',
            ])
            ->withCount('orders as order_count')
            ->whereRelation('roles', 'name', RoleEnum::Customer)
            ->tap(fn (Builder $query): Builder => $this->filterManager->apply($query, $filters))
            ->paginate($perPage)
            ->withQueryString();
    }
}
