<?php

declare(strict_types=1);

namespace App\Filters\Configs;

use App\Filters\Criteria\BooleanCriterion;
use App\Filters\Criteria\SortCriterion;
use App\Filters\Criteria\TextSearchCriterion;
use App\Filters\CriteriaCollection;
use App\Filters\Strategies\ProductsCountColumnSortStrategy;
use App\Filters\Strategies\TranslatableColumnSortStrategy;
use App\Models\Brand;

final readonly class BrandFilterConfig
{
    /**
     * @return CriteriaCollection<Brand>
     */
    public static function getCriteria(mixed $direction = null): CriteriaCollection
    {
        /** @var CriteriaCollection<Brand> $criteria */
        $criteria = new CriteriaCollection();

        /** @var TextSearchCriterion<Brand> $textSearch */
        $textSearch = new TextSearchCriterion(['name', 'url_handle']);
        $criteria->add('query', $textSearch);

        /** @var BooleanCriterion<Brand> $isActive */
        $isActive = new BooleanCriterion('is_active');
        $criteria->add('is_active', $isActive);

        /** @var SortCriterion<Brand> $sortCriterion */
        $sortCriterion = new SortCriterion(
            allowedColumns: ['name', 'products_count', 'created_at'],
            columnStrategies: [
                'name' => new TranslatableColumnSortStrategy('name'),
                'products_count' => new ProductsCountColumnSortStrategy(),
            ],
            direction: $direction,
        );
        $criteria->add('sort', $sortCriterion);

        return $criteria;
    }
}
