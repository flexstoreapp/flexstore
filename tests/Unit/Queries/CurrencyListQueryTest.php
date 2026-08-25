<?php

declare(strict_types=1);

use App\Models\Currency;
use App\Queries\CurrencyListQuery;
use Illuminate\Database\Eloquent\Collection;

covers(CurrencyListQuery::class);

uses()->group('queries', 'currency');

test('returns all currencies', function () {
    $existingCount = Currency::count();
    Currency::factory()->count(3)->sequence(
        ['code' => 'EUR'],
        ['code' => 'GBP'],
        ['code' => 'JPY'],
    )->create();

    $query = app(CurrencyListQuery::class);
    $result = $query->execute();

    expect($result)->toBeInstanceOf(Collection::class)
        ->and($result)->toHaveCount($existingCount + 3);
});

test('includes newly created currencies', function () {
    $before = app(CurrencyListQuery::class)->execute()->count();
    Currency::factory()->create();

    $query = app(CurrencyListQuery::class);
    $result = $query->execute();

    expect($result)->toHaveCount($before + 1);
});
