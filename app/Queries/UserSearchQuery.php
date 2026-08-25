<?php

declare(strict_types=1);

namespace App\Queries;

use App\Filters\Configs\UserFilterConfig;
use App\Filters\FilterManager;
use App\Models\User;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Builder;

final readonly class UserSearchQuery
{
    /**
     * @param  FilterManager<User>  $filterManager
     */
    public function __construct(private FilterManager $filterManager)
    {
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Paginator<int, User>
     */
    public function execute(array $filters = []): Paginator
    {
        $this->filterManager->setCriteria(UserFilterConfig::getCriteria($filters['direction'] ?? null));

        return User::query()
            ->select(['id', 'name', 'email'])
            ->tap(fn (Builder $query): Builder => $this->filterManager->apply($query, $filters))
            ->simplePaginate();
    }
}
