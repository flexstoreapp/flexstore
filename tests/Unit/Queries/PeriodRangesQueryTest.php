<?php

declare(strict_types=1);

use App\Enums\DatePeriod;
use App\Models\Order;
use App\Queries\PeriodRangesQuery;
use Illuminate\Support\Facades\Date;

covers(PeriodRangesQuery::class);

uses()->group('queries', 'date-period');

beforeEach(function (): void {
    Date::setTestNow(Date::parse('2026-08-27 14:30:00'));
});

afterEach(function (): void {
    Date::setTestNow();
});

test('returns a range for every period except custom', function (): void {
    $ranges = app(PeriodRangesQuery::class)->execute();

    $expected = array_values(array_diff(
        array_map(fn (DatePeriod $period): string => $period->value, DatePeriod::cases()),
        [DatePeriod::Custom->value],
    ));

    expect(array_keys($ranges))->toBe($expected)
        ->and($ranges)->not->toHaveKey(DatePeriod::Custom->value);
});

test('each range is a pair of Y-m-d dates that do not run backwards', function (): void {
    foreach (app(PeriodRangesQuery::class)->execute() as $preset => $range) {
        expect($range)->toHaveKeys(['from', 'to'])
            ->and($range['from'])->toMatch('/^\d{4}-\d{2}-\d{2}$/', $preset)
            ->and($range['to'])->toMatch('/^\d{4}-\d{2}-\d{2}$/', $preset)
            ->and($range['from'] <= $range['to'])->toBeTrue($preset);
    }
});

test('ranges match what the period enum resolves', function (): void {
    Order::factory()->create(['created_at' => '2025-03-14 18:45:00']);

    $ranges = app(PeriodRangesQuery::class)->execute();

    expect($ranges['today'])->toBe(['from' => '2026-08-27', 'to' => '2026-08-27'])
        ->and($ranges['yesterday'])->toBe(['from' => '2026-08-26', 'to' => '2026-08-26'])
        ->and($ranges['30d'])->toBe(['from' => '2026-07-29', 'to' => '2026-08-27'])
        ->and($ranges['last-month'])->toBe(['from' => '2026-07-01', 'to' => '2026-07-31'])
        ->and($ranges['last-quarter'])->toBe(['from' => '2026-04-01', 'to' => '2026-06-30'])
        ->and($ranges['lifetime'])->toBe(['from' => '2025-03-14', 'to' => '2026-08-27']);
});
