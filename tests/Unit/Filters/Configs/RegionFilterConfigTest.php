<?php

declare(strict_types=1);

namespace Tests\Unit\Filters\Configs;

use App\Filters\Configs\RegionFilterConfig;
use App\Filters\Criteria\SortCriterion;
use App\Filters\Criteria\TextSearchCriterion;
use App\Filters\CriteriaCollection;

covers(RegionFilterConfig::class);

uses()->group('filters');

test('getCriteria returns CriteriaCollection', function () {
    $criteria = RegionFilterConfig::getCriteria();

    expect($criteria)->toBeInstanceOf(CriteriaCollection::class);
});

test('getCriteria includes query filter with TextSearchCriterion', function () {
    $criteria = RegionFilterConfig::getCriteria();

    expect($criteria->has('query'))->toBeTrue();

    $queryCriterion = $criteria->get('query');
    expect($queryCriterion)->toBeInstanceOf(TextSearchCriterion::class);
});

test('getCriteria includes sort filter with SortCriterion', function () {
    $criteria = RegionFilterConfig::getCriteria();

    expect($criteria->has('sort'))->toBeTrue();

    $sortCriterion = $criteria->get('sort');
    expect($sortCriterion)->toBeInstanceOf(SortCriterion::class);
});

test('getCriteria includes all expected filter keys', function () {
    $criteria = RegionFilterConfig::getCriteria();
    $expectedKeys = ['query', 'sort'];

    $actualKeys = $criteria->keys();

    expect($actualKeys)->toEqual($expectedKeys);
});

test('getCriteria returns new instance each time', function () {
    $criteria1 = RegionFilterConfig::getCriteria();
    $criteria2 = RegionFilterConfig::getCriteria();

    expect($criteria1)->not->toBe($criteria2);
    expect($criteria1)->toEqual($criteria2);
});
