<?php

declare(strict_types=1);

namespace Tests\Unit\Filters\Configs;

use App\Filters\Configs\OrderFilterConfig;
use App\Filters\Criteria\ExactMatchCriterion;
use App\Filters\Criteria\NullStateCriterion;
use App\Filters\Criteria\SortCriterion;
use App\Filters\Criteria\TextSearchCriterion;
use App\Filters\CriteriaCollection;

covers(OrderFilterConfig::class);

uses()->group('filters');

test('getCriteria returns CriteriaCollection', function () {
    $criteria = OrderFilterConfig::getCriteria();

    expect($criteria)->toBeInstanceOf(CriteriaCollection::class);
});

test('getCriteria includes query filter with TextSearchCriterion', function () {
    $criteria = OrderFilterConfig::getCriteria();

    expect($criteria->has('query'))->toBeTrue();

    $queryCriterion = $criteria->get('query');
    expect($queryCriterion)->toBeInstanceOf(TextSearchCriterion::class);
});

test('getCriteria includes fulfillment_status filter with ExactMatchCriterion', function () {
    $criteria = OrderFilterConfig::getCriteria();

    expect($criteria->has('fulfillment_status'))->toBeTrue();

    $statusCriterion = $criteria->get('fulfillment_status');
    expect($statusCriterion)->toBeInstanceOf(ExactMatchCriterion::class);
});

test('getCriteria includes payment_status filter with ExactMatchCriterion', function () {
    $criteria = OrderFilterConfig::getCriteria();

    expect($criteria->has('payment_status'))->toBeTrue();

    $paymentStatusCriterion = $criteria->get('payment_status');
    expect($paymentStatusCriterion)->toBeInstanceOf(ExactMatchCriterion::class);
});

test('getCriteria includes cancellation_status filter with NullStateCriterion', function () {
    $criteria = OrderFilterConfig::getCriteria();

    expect($criteria->has('cancellation_status'))->toBeTrue();

    $cancellationCriterion = $criteria->get('cancellation_status');
    expect($cancellationCriterion)->toBeInstanceOf(NullStateCriterion::class);
});

test('getCriteria includes sort filter with SortCriterion', function () {
    $criteria = OrderFilterConfig::getCriteria();

    expect($criteria->has('sort'))->toBeTrue();

    $sortCriterion = $criteria->get('sort');
    expect($sortCriterion)->toBeInstanceOf(SortCriterion::class);
});

test('getCriteria includes all expected filter keys', function () {
    $criteria = OrderFilterConfig::getCriteria();
    $expectedKeys = [
        'query',
        'fulfillment_status',
        'payment_status',
        'cancellation_status',
        'sort',
    ];

    $actualKeys = $criteria->keys();

    expect($actualKeys)->toEqual($expectedKeys);
});

test('query criterion searches by billing address first name', function () {
    $matching = \App\Models\Order::factory()->create();
    \App\Models\OrderAddress::factory()->billing()->forOrder($matching)->create(['first_name' => 'Findme']);

    $other = \App\Models\Order::factory()->create();
    \App\Models\OrderAddress::factory()->billing()->forOrder($other)->create(['first_name' => 'Other']);

    $criterion = OrderFilterConfig::getCriteria()->get('query');
    $result = $criterion->apply(\App\Models\Order::query(), 'Findme');

    expect($result->count())->toBe(1)
        ->and($result->first()->id)->toBe($matching->id);
});

test('query criterion searches by billing address last name', function () {
    $matching = \App\Models\Order::factory()->create();
    \App\Models\OrderAddress::factory()->billing()->forOrder($matching)->create(['last_name' => 'Findme']);

    $other = \App\Models\Order::factory()->create();
    \App\Models\OrderAddress::factory()->billing()->forOrder($other)->create(['last_name' => 'Other']);

    $criterion = OrderFilterConfig::getCriteria()->get('query');
    $result = $criterion->apply(\App\Models\Order::query(), 'Findme');

    expect($result->count())->toBe(1)
        ->and($result->first()->id)->toBe($matching->id);
});

test('query criterion strips # prefix when searching by id', function () {
    $order = \App\Models\Order::factory()->create();

    $criteria = OrderFilterConfig::getCriteria();
    $queryCriterion = $criteria->get('query');

    $result = $queryCriterion->apply(\App\Models\Order::query(), "#{$order->id}");

    expect($result->count())->toBe(1)
        ->and($result->first()->id)->toBe($order->id);
});

test('getCriteria returns new instance each time', function () {
    $criteria1 = OrderFilterConfig::getCriteria();
    $criteria2 = OrderFilterConfig::getCriteria();

    expect($criteria1)->not->toBe($criteria2);
    expect($criteria1)->toEqual($criteria2);
});
