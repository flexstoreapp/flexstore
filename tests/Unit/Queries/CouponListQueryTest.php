<?php

declare(strict_types=1);

use App\Models\Coupon;
use App\Queries\CouponListQuery;
use Illuminate\Pagination\LengthAwarePaginator;

covers(CouponListQuery::class);

uses()->group('queries', 'coupon');

test('returns paginated coupons', function () {
    Coupon::factory()->count(20)->create();

    $query = app(CouponListQuery::class);
    $result = $query->execute(perPage: 15);

    expect($result)->toBeInstanceOf(LengthAwarePaginator::class)
        ->and($result)->toHaveCount(15)
        ->and($result->total())->toBe(20);
});

test('returns empty paginator when no coupons exist', function () {
    $query = app(CouponListQuery::class);
    $result = $query->execute();

    expect($result)->toBeEmpty();
});

test('respects per page parameter', function () {
    Coupon::factory()->count(10)->create();

    $query = app(CouponListQuery::class);
    $result = $query->execute(perPage: 5);

    expect($result)->toHaveCount(5)
        ->and($result->total())->toBe(10);
});

test('defaults to 15 per page', function () {
    Coupon::factory()->count(20)->create();

    $query = app(CouponListQuery::class);
    $result = $query->execute();

    expect($result)->toHaveCount(15)
        ->and($result->perPage())->toBe(15);
});
