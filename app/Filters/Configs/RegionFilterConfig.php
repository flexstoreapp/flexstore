<?php

declare(strict_types=1);

namespace App\Filters\Configs;

use App\Filters\Criteria\SortCriterion;
use App\Filters\Criteria\TextSearchCriterion;
use App\Filters\CriteriaCollection;
use App\Filters\Strategies\TranslatableColumnSortStrategy;
use App\Models\Region;

final readonly class RegionFilterConfig
{
    /**
     * @return CriteriaCollection<Region>
     */
    public static function getCriteria(mixed $direction = null): CriteriaCollection
    {
        /** @var CriteriaCollection<Region> $criteria */
        $criteria = new CriteriaCollection();

        /** @var TextSearchCriterion<Region> $textSearch */
        $textSearch = new TextSearchCriterion(['name']);
        $criteria->add('query', $textSearch);

        /** @var SortCriterion<Region> $sortCriterion */
        $sortCriterion = new SortCriterion(
            allowedColumns: ['name'],
            columnStrategies: [
                'name' => new TranslatableColumnSortStrategy('name'),
            ],
            direction: $direction,
        );
        $criteria->add('sort', $sortCriterion);

        return $criteria;
    }
}
