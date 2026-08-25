<?php

declare(strict_types=1);

use App\Models\Order;
use App\Models\User;
use App\Queries\LifetimeStartQuery;
use Illuminate\Support\Facades\Date;

covers(LifetimeStartQuery::class);

uses()->group('queries', 'date-period');

beforeEach(function (): void {
    Date::setTestNow(Date::parse('2026-08-27 14:30:00'));
});

afterEach(function (): void {
    Date::setTestNow();
});

test('returns the start of the day of the earliest order', function (): void {
    Order::factory()->create(['created_at' => '2025-03-14 18:45:00']);
    Order::factory()->create(['created_at' => '2026-01-09 08:00:00']);

    expect(app(LifetimeStartQuery::class)->execute()->toDateTimeString())->toBe('2025-03-14 00:00:00');
});

test('falls back to the earliest user when there are no orders', function (): void {
    User::factory()->create(['created_at' => '2024-11-02 12:00:00']);

    expect(app(LifetimeStartQuery::class)->execute()->toDateTimeString())->toBe('2024-11-02 00:00:00');
});

test('prefers the earliest order over the earliest user', function (): void {
    User::factory()->create(['created_at' => '2024-11-02 12:00:00']);
    Order::factory()->create(['created_at' => '2025-03-14 18:45:00']);

    expect(app(LifetimeStartQuery::class)->execute()->toDateTimeString())->toBe('2025-03-14 00:00:00');
});

test('falls back to today when the store has no records', function (): void {
    User::query()->delete();

    expect(app(LifetimeStartQuery::class)->execute()->toDateTimeString())->toBe('2026-08-27 00:00:00');
});
