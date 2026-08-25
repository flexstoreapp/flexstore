<?php

declare(strict_types=1);

use App\Utilities\TimeBucket;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

covers(TimeBucket::class);

uses()->group('utilities');

test('fill returns a daily bucket for each day in the range', function () {
    $rows = TimeBucket::fill(
        from: Carbon::parse('2026-01-01'),
        to: Carbon::parse('2026-01-03'),
        grouped: collect([
            '2026-01-02' => ['count' => 5],
        ]),
        rowBuilder: fn (string $label, mixed $bucket, string $key): array => [
            'label' => $label,
            'key' => $key,
            'count' => $bucket['count'] ?? 0,
        ],
    );

    expect($rows)->toHaveCount(3)
        ->and($rows[0])->toBe(['label' => 'Jan 01', 'key' => '2026-01-01', 'count' => 0])
        ->and($rows[1])->toBe(['label' => 'Jan 02', 'key' => '2026-01-02', 'count' => 5])
        ->and($rows[2])->toBe(['label' => 'Jan 03', 'key' => '2026-01-03', 'count' => 0]);
});

test('fill switches to monthly buckets for ranges longer than 90 days', function () {
    $rows = TimeBucket::fill(
        from: Carbon::parse('2026-01-15'),
        to: Carbon::parse('2026-05-20'),
        grouped: collect([
            '2026-03' => ['count' => 7],
        ]),
        rowBuilder: fn (string $label, mixed $bucket, string $key): array => [
            'label' => $label,
            'key' => $key,
            'count' => $bucket['count'] ?? 0,
        ],
    );

    expect($rows)->toHaveCount(5)
        ->and($rows[0]['key'])->toBe('2026-01')
        ->and($rows[0]['label'])->toBe('Jan 2026')
        ->and($rows[2]['key'])->toBe('2026-03')
        ->and($rows[2]['count'])->toBe(7)
        ->and($rows[4]['key'])->toBe('2026-05');
});

test('fill handles a single-day range', function () {
    $rows = TimeBucket::fill(
        from: Carbon::parse('2026-01-15'),
        to: Carbon::parse('2026-01-15'),
        grouped: collect(['2026-01-15' => ['count' => 0]]),
        rowBuilder: fn (string $label, mixed $bucket, string $key): array => [
            'label' => $label,
            'key' => $key,
        ],
    );

    expect($rows)->toHaveCount(1)
        ->and($rows[0]['key'])->toBe('2026-01-15');
});

test('fill returns an empty array when grouped data is empty', function () {
    $rows = TimeBucket::fill(
        from: Carbon::parse('2026-01-01'),
        to: Carbon::parse('2026-01-10'),
        grouped: new Collection(),
        rowBuilder: fn (string $label, mixed $bucket, string $key): array => [
            'label' => $label,
            'key' => $key,
        ],
    );

    expect($rows)->toBe([]);
});

test('dateGroupExpression groups by day for short ranges on orders table', function () {
    $expression = TimeBucket::dateGroupExpression(
        Carbon::parse('2026-01-01'),
        Carbon::parse('2026-01-10'),
        'orders.created_at',
    );

    expect((string) $expression->getValue(DB::connection()->getQueryGrammar()))
        ->toBe('DATE(orders.created_at) as date_group');
});

test('dateGroupExpression groups by month for long ranges on orders table', function () {
    $expression = TimeBucket::dateGroupExpression(
        Carbon::parse('2026-01-01'),
        Carbon::parse('2026-06-01'),
        'orders.created_at',
    );

    expect((string) $expression->getValue(DB::connection()->getQueryGrammar()))
        ->toBe(monthlyDateGroup('orders.created_at'));
});

test('dateGroupExpression groups by day for short ranges on checkout_sessions table', function () {
    $expression = TimeBucket::dateGroupExpression(
        Carbon::parse('2026-01-01'),
        Carbon::parse('2026-01-10'),
        'checkout_sessions.created_at',
    );

    expect((string) $expression->getValue(DB::connection()->getQueryGrammar()))
        ->toBe('DATE(checkout_sessions.created_at) as date_group');
});

test('dateGroupExpression groups by month for long ranges on checkout_sessions table', function () {
    $expression = TimeBucket::dateGroupExpression(
        Carbon::parse('2026-01-01'),
        Carbon::parse('2026-06-01'),
        'checkout_sessions.created_at',
    );

    expect((string) $expression->getValue(DB::connection()->getQueryGrammar()))
        ->toBe(monthlyDateGroup('checkout_sessions.created_at'));
});

test('dateGroupExpression falls back to default column for short range', function () {
    $expression = TimeBucket::dateGroupExpression(
        Carbon::parse('2026-01-01'),
        Carbon::parse('2026-01-10'),
    );

    expect((string) $expression->getValue(DB::connection()->getQueryGrammar()))
        ->toBe('DATE(created_at) as date_group');
});

test('dateGroupExpression falls back to default column for long range', function () {
    $expression = TimeBucket::dateGroupExpression(
        Carbon::parse('2026-01-01'),
        Carbon::parse('2026-06-01'),
    );

    expect((string) $expression->getValue(DB::connection()->getQueryGrammar()))
        ->toBe(monthlyDateGroup('created_at'));
});

function monthlyDateGroup(string $column): string
{
    return match (DB::getDriverName()) {
        'pgsql' => "to_char({$column}, 'YYYY-MM') as date_group",
        'sqlite' => "strftime('%Y-%m', {$column}) as date_group",
        default => "DATE_FORMAT({$column}, '%Y-%m') as date_group",
    };
}
