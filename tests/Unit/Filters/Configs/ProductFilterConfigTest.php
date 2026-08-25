<?php

declare(strict_types=1);

namespace Tests\Unit\Filters\Configs;

use App\Filters\Configs\ProductFilterConfig;
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

covers(ProductFilterConfig::class);

uses()->group('filters');

test('getCriteria returns CriteriaCollection', function () {
    $criteria = ProductFilterConfig::getCriteria();

    expect($criteria)->toBeInstanceOf(CriteriaCollection::class);
});

test('getCriteria includes query filter with TextSearchCriterion', function () {
    $criteria = ProductFilterConfig::getCriteria();

    expect($criteria->has('query'))->toBeTrue();

    $queryCriterion = $criteria->get('query');
    expect($queryCriterion)->toBeInstanceOf(TextSearchCriterion::class);
});

test('getCriteria includes category filter with RelationshipCriterion', function () {
    $criteria = ProductFilterConfig::getCriteria();

    expect($criteria->has('category'))->toBeTrue();

    $categoryCriterion = $criteria->get('category');
    expect($categoryCriterion)->toBeInstanceOf(RelationshipCriterion::class);
});

test('getCriteria includes min_price filter with NumericRangeCriterion', function () {
    $criteria = ProductFilterConfig::getCriteria();

    expect($criteria->has('min_price'))->toBeTrue();

    $minPriceCriterion = $criteria->get('min_price');
    expect($minPriceCriterion)->toBeInstanceOf(NumericRangeCriterion::class);
});

test('getCriteria includes max_price filter with NumericRangeCriterion', function () {
    $criteria = ProductFilterConfig::getCriteria();

    expect($criteria->has('max_price'))->toBeTrue();

    $maxPriceCriterion = $criteria->get('max_price');
    expect($maxPriceCriterion)->toBeInstanceOf(NumericRangeCriterion::class);
});

test('getCriteria includes in_stock filter with InStockCriterion', function () {
    $criteria = ProductFilterConfig::getCriteria();

    expect($criteria->has('in_stock'))->toBeTrue();

    $inStockCriterion = $criteria->get('in_stock');
    expect($inStockCriterion)->toBeInstanceOf(InStockCriterion::class);
});

test('getCriteria includes is_active filter with BooleanCriterion', function () {
    $criteria = ProductFilterConfig::getCriteria();

    expect($criteria->has('is_active'))->toBeTrue();

    $isActiveCriterion = $criteria->get('is_active');
    expect($isActiveCriterion)->toBeInstanceOf(BooleanCriterion::class);
});

test('getCriteria includes sort filter with SortCriterion', function () {
    $criteria = ProductFilterConfig::getCriteria();

    expect($criteria->has('sort'))->toBeTrue();

    $sortCriterion = $criteria->get('sort');
    expect($sortCriterion)->toBeInstanceOf(SortCriterion::class);
});

test('getCriteria includes all expected filter keys', function () {
    $criteria = ProductFilterConfig::getCriteria();
    $expectedKeys = [
        'query',
        'category',
        'min_price',
        'max_price',
        'in_stock',
        'is_active',
        'sort',
    ];

    $actualKeys = $criteria->keys();

    expect($actualKeys)->toEqual($expectedKeys);
});

test('getCriteria returns new instance each time', function () {
    $criteria1 = ProductFilterConfig::getCriteria();
    $criteria2 = ProductFilterConfig::getCriteria();

    expect($criteria1)->not->toBe($criteria2);
    expect($criteria1)->toEqual($criteria2);
});

test('getStorefrontCriteria returns CriteriaCollection', function () {
    $criteria = ProductFilterConfig::getStorefrontCriteria();

    expect($criteria)->toBeInstanceOf(CriteriaCollection::class);
});

test('getStorefrontCriteria includes query filter with TextSearchCriterion', function () {
    $criteria = ProductFilterConfig::getStorefrontCriteria();

    expect($criteria->has('query'))->toBeTrue();

    $queryCriterion = $criteria->get('query');
    expect($queryCriterion)->toBeInstanceOf(TextSearchCriterion::class);
});

test('getStorefrontCriteria includes categories filter with MultipleCategoriesWithDescendantsCriterion', function () {
    $criteria = ProductFilterConfig::getStorefrontCriteria();

    expect($criteria->has('categories'))->toBeTrue();

    $categoriesCriterion = $criteria->get('categories');
    expect($categoriesCriterion)->toBeInstanceOf(MultipleCategoriesWithDescendantsCriterion::class);
});

test('getStorefrontCriteria includes rating and on_sale filters', function () {
    $criteria = ProductFilterConfig::getStorefrontCriteria();

    expect($criteria->get('rating'))->toBeInstanceOf(RatingCriterion::class)
        ->and($criteria->get('on_sale'))->toBeInstanceOf(OnSaleCriterion::class);
});

test('getStorefrontCriteria includes category_tree filter with CategoryWithDescendantsCriterion', function () {
    $criteria = ProductFilterConfig::getStorefrontCriteria();

    expect($criteria->has('category_tree'))->toBeTrue();

    $categoryTreeCriterion = $criteria->get('category_tree');
    expect($categoryTreeCriterion)->toBeInstanceOf(CategoryWithDescendantsCriterion::class);
});

test('getStorefrontCriteria includes brands filter with MultipleIdsCriterion', function () {
    $criteria = ProductFilterConfig::getStorefrontCriteria();

    expect($criteria->has('brands'))->toBeTrue();

    $brandsCriterion = $criteria->get('brands');
    expect($brandsCriterion)->toBeInstanceOf(MultipleIdsCriterion::class);
});

test('getStorefrontCriteria includes the variant-aware price range filter', function () {
    $criteria = ProductFilterConfig::getStorefrontCriteria();

    expect($criteria->has('price_range'))->toBeTrue()
        ->and($criteria->get('price_range'))->toBeInstanceOf(ProductPriceRangeCriterion::class);
});

test('getStorefrontCriteria includes in_stock filter with InStockCriterion', function () {
    $criteria = ProductFilterConfig::getStorefrontCriteria();

    expect($criteria->has('in_stock'))->toBeTrue();

    $inStockCriterion = $criteria->get('in_stock');
    expect($inStockCriterion)->toBeInstanceOf(InStockCriterion::class);
});

test('getStorefrontCriteria includes sort filter with StorefrontSortCriterion', function () {
    $criteria = ProductFilterConfig::getStorefrontCriteria();

    expect($criteria->has('sort'))->toBeTrue();

    $sortCriterion = $criteria->get('sort');
    expect($sortCriterion)->toBeInstanceOf(StorefrontSortCriterion::class);
});

test('getStorefrontCriteria includes all expected filter keys', function () {
    $criteria = ProductFilterConfig::getStorefrontCriteria();
    $expectedKeys = [
        'query',
        'categories',
        'category_tree',
        'brands',
        'price_range',
        'in_stock',
        'rating',
        'on_sale',
        'sort',
    ];

    $actualKeys = $criteria->keys();

    expect($actualKeys)->toEqual($expectedKeys);
});

test('getStorefrontCriteria does not include admin-only filters', function () {
    $criteria = ProductFilterConfig::getStorefrontCriteria();

    expect($criteria->has('category'))->toBeFalse()
        ->and($criteria->has('is_active'))->toBeFalse();
});
