<?php

declare(strict_types=1);

namespace Tests\Unit\Filters\Criteria;

use App\Filters\Criteria\NullStateCriterion;
use App\Models\Order;

covers(NullStateCriterion::class);

uses()->group('filters');

test('filters rows where column is null', function () {
    Order::factory()->create(['canceled_at' => null]);
    Order::factory()->create(['canceled_at' => now()]);
    Order::factory()->create(['canceled_at' => null]);

    $criterion = new NullStateCriterion('canceled_at', 'active', 'canceled');

    $result = $criterion->apply(Order::query(), 'active');

    expect($result->count())->toBe(2);
});

test('filters rows where column is not null', function () {
    Order::factory()->create(['canceled_at' => null]);
    Order::factory()->create(['canceled_at' => now()]);
    Order::factory()->create(['canceled_at' => now()]);

    $criterion = new NullStateCriterion('canceled_at', 'active', 'canceled');

    $result = $criterion->apply(Order::query(), 'canceled');

    expect($result->count())->toBe(2);
});

test('canApply returns true for the two configured values only', function () {
    $criterion = new NullStateCriterion('canceled_at', 'active', 'canceled');

    expect($criterion->canApply('active'))->toBeTrue()
        ->and($criterion->canApply('canceled'))->toBeTrue()
        ->and($criterion->canApply('all'))->toBeFalse()
        ->and($criterion->canApply(''))->toBeFalse()
        ->and($criterion->canApply(null))->toBeFalse();
});
