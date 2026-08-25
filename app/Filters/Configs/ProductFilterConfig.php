<?php

declare(strict_types=1);

namespace App\Filters\Configs;

use App\Filters\Criteria\BooleanCriterion;
use App\Filters\Criteria\CategoryWithDescendantsCriterion;
use App\Filters\Criteria\InStockCriterion;
use App\Filters\Criteria\MultipleCategoriesWithDescendantsCriterion;
use App\Filters\Criteria\MultipleIdsCriterion;
use App\Filters\Criteria\NumericRangeCriterion;
use App\Filters\Criteria\OnSaleCriterion;
use App\Filters\Criteria\ProductPriceRangeCriterion;
use App\Filters\Criteria\RatingCriterion;
use App\Filters\Criteria\RelationshipCriterion;
use App\Filters\Criteria\SortCriterion;
use App\Filters\Criteria\StorefrontSortCriterion;
use App\Filters\Criteria\TextSearchCriterion;
use App\Filters\CriteriaCollection;
use App\Filters\Strategies\PriceColumnSortStrategy;
use App\Filters\Strategies\StockColumnSortStrategy;
use App\Filters\Strategies\TranslatableColumnSortStrategy;
use App\Models\Product;

final readonly class ProductFilterConfig
{
    /**
     * @return CriteriaCollection<Product>
     */
    public static function getCriteria(mixed $direction = null): CriteriaCollection
    {
        /** @var CriteriaCollection<Product> $criteria */
        $criteria = new CriteriaCollection();

        /** @var TextSearchCriterion<Product> $textSearch */
        $textSearch = new TextSearchCriterion(['title', 'sku', 'url_handle']);
        $criteria->add('query', $textSearch);

        /** @var RelationshipCriterion<Product> $categoryRelation */
        $categoryRelation = new RelationshipCriterion('category', 'id');
        $criteria->add('category', $categoryRelation);

        /** @var NumericRangeCriterion<Product> $minPrice */
        $minPrice = new NumericRangeCriterion('price', NumericRangeCriterion::TYPE_MIN);
        $criteria->add('min_price', $minPrice);

        /** @var NumericRangeCriterion<Product> $maxPrice */
        $maxPrice = new NumericRangeCriterion('price', NumericRangeCriterion::TYPE_MAX);
        $criteria->add('max_price', $maxPrice);

        /** @var InStockCriterion<Product> $inStock */
        $inStock = new InStockCriterion();
        $criteria->add('in_stock', $inStock);

        /** @var BooleanCriterion<Product> $isActive */
        $isActive = new BooleanCriterion('is_active');
        $criteria->add('is_active', $isActive);

        /** @var SortCriterion<Product> $sortCriterion */
        $sortCriterion = new SortCriterion(
            allowedColumns: ['title', 'sku', 'price', 'stock', 'created_at'],
            // @phpstan-ignore argument.type
            columnStrategies: [
                'title' => new TranslatableColumnSortStrategy('title'),
                'price' => new PriceColumnSortStrategy(),
                'stock' => new StockColumnSortStrategy(),
            ],
            direction: $direction,
        );
        $criteria->add('sort', $sortCriterion);

        return $criteria;
    }

    /**
     * @return CriteriaCollection<Product>
     */
    public static function getStorefrontCriteria(?string $search = null): CriteriaCollection
    {
        /** @var CriteriaCollection<Product> $criteria */
        $criteria = new CriteriaCollection();

        /** @var TextSearchCriterion<Product> $textSearch */
        $textSearch = new TextSearchCriterion(['title', 'description', 'sku']);
        $criteria->add('query', $textSearch);

        /** @var MultipleCategoriesWithDescendantsCriterion<Product> $categories */
        $categories = new MultipleCategoriesWithDescendantsCriterion();
        $criteria->add('categories', $categories);

        /** @var CategoryWithDescendantsCriterion<Product> $categoryTree */
        $categoryTree = new CategoryWithDescendantsCriterion();
        $criteria->add('category_tree', $categoryTree);

        /** @var MultipleIdsCriterion<Product> $brands */
        $brands = new MultipleIdsCriterion('brand_id');
        $criteria->add('brands', $brands);

        /** @var ProductPriceRangeCriterion<Product> $priceRange */
        $priceRange = new ProductPriceRangeCriterion();
        $criteria->add('price_range', $priceRange);

        /** @var InStockCriterion<Product> $inStock */
        $inStock = new InStockCriterion();
        $criteria->add('in_stock', $inStock);

        /** @var RatingCriterion<Product> $rating */
        $rating = new RatingCriterion();
        $criteria->add('rating', $rating);

        /** @var OnSaleCriterion<Product> $onSale */
        $onSale = new OnSaleCriterion();
        $criteria->add('on_sale', $onSale);

        /** @var StorefrontSortCriterion<Product> $sort */
        $sort = new StorefrontSortCriterion($search);
        $criteria->add('sort', $sort);

        return $criteria;
    }
}
