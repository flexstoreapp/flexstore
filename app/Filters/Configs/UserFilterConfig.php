<?php

declare(strict_types=1);

namespace App\Filters\Configs;

use App\Filters\Criteria\CustomerOnlyCriterion;
use App\Filters\Criteria\RoleCriterion;
use App\Filters\Criteria\SortCriterion;
use App\Filters\Criteria\TextSearchCriterion;
use App\Filters\CriteriaCollection;
use App\Models\User;

final readonly class UserFilterConfig
{
    /**
     * @return CriteriaCollection<User>
     */
    public static function getCriteria(mixed $direction = null): CriteriaCollection
    {
        /** @var CriteriaCollection<User> $criteria */
        $criteria = new CriteriaCollection();

        /** @var TextSearchCriterion<User> $textSearch */
        $textSearch = new TextSearchCriterion(['name', 'email']);
        $criteria->add('query', $textSearch);

        /** @var RoleCriterion<User> $roleFilter */
        $roleFilter = new RoleCriterion();
        $criteria->add('role', $roleFilter);

        /** @var CustomerOnlyCriterion<User> $customerOnly */
        $customerOnly = new CustomerOnlyCriterion();
        $criteria->add('customers_only', $customerOnly);

        /** @var SortCriterion<User> $sortCriterion */
        $sortCriterion = new SortCriterion(['name', 'email', 'last_login_at', 'created_at'], direction: $direction);
        $criteria->add('sort', $sortCriterion);

        return $criteria;
    }
}
