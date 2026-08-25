<?php

declare(strict_types=1);

use App\Filters\Configs\BrandFilterConfig;
use App\Filters\Criteria\BooleanCriterion;
use App\Filters\Criteria\SortCriterion;
use App\Filters\Criteria\TextSearchCriterion;
use App\Filters\CriteriaCollection;

covers(BrandFilterConfig::class);

uses()->group('filter');

test('returns filter criteria collection', function () {
    $criteria = BrandFilterConfig::getCriteria();

    expect($criteria)->toBeInstanceOf(CriteriaCollection::class);
});

test('includes text search criterion for query', function () {
    $criteria = BrandFilterConfig::getCriteria();

    expect($criteria->has('query'))->toBeTrue()
        ->and($criteria->get('query'))->toBeInstanceOf(TextSearchCriterion::class);
});

test('includes boolean criterion for is_active', function () {
    $criteria = BrandFilterConfig::getCriteria();

    expect($criteria->has('is_active'))->toBeTrue()
        ->and($criteria->get('is_active'))->toBeInstanceOf(BooleanCriterion::class);
});

test('includes sort criterion for sort', function () {
    $criteria = BrandFilterConfig::getCriteria();

    expect($criteria->has('sort'))->toBeTrue()
        ->and($criteria->get('sort'))->toBeInstanceOf(SortCriterion::class);
});

test('has all expected filter keys', function () {
    $criteria = BrandFilterConfig::getCriteria();
    $keys = $criteria->keys();

    expect($keys)->toContain('query')
        ->and($keys)->toContain('is_active')
        ->and($keys)->toContain('sort');
});
