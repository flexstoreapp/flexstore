<?php

declare(strict_types=1);

use App\Enums\DatePeriod;
use Illuminate\Support\Facades\Date;

covers(DatePeriod::class);

uses()->group('enums', 'date-period');

beforeEach(function (): void {
    Date::setTestNow(Date::parse('2026-08-27 14:30:00'));
});

afterEach(function (): void {
    Date::setTestNow();
});

$lifetime = fn (): Closure => fn () => Date::parse('2024-02-05 09:15:00');

test('today spans the current day', function () use ($lifetime): void {
    expect(DatePeriod::Today->startsAt($lifetime())->toDateTimeString())->toBe('2026-08-27 00:00:00')
        ->and(DatePeriod::Today->endsAt()->toDateTimeString())->toBe('2026-08-27 23:59:59');
});

test('yesterday spans the previous day only', function () use ($lifetime): void {
    expect(DatePeriod::Yesterday->startsAt($lifetime())->toDateTimeString())->toBe('2026-08-26 00:00:00')
        ->and(DatePeriod::Yesterday->endsAt()->toDateTimeString())->toBe('2026-08-26 23:59:59');
});

test('last 7 days includes today', function () use ($lifetime): void {
    expect(DatePeriod::Last7Days->startsAt($lifetime())->toDateTimeString())->toBe('2026-08-21 00:00:00')
        ->and(DatePeriod::Last7Days->endsAt()->toDateTimeString())->toBe('2026-08-27 23:59:59');
});

test('last 30 days includes today', function () use ($lifetime): void {
    expect(DatePeriod::Last30Days->startsAt($lifetime())->toDateTimeString())->toBe('2026-07-29 00:00:00');
});

test('this month starts on the first of the month', function () use ($lifetime): void {
    expect(DatePeriod::ThisMonth->startsAt($lifetime())->toDateTimeString())->toBe('2026-08-01 00:00:00')
        ->and(DatePeriod::ThisMonth->endsAt()->toDateTimeString())->toBe('2026-08-27 23:59:59');
});

test('last month spans the whole previous month', function () use ($lifetime): void {
    expect(DatePeriod::LastMonth->startsAt($lifetime())->toDateTimeString())->toBe('2026-07-01 00:00:00')
        ->and(DatePeriod::LastMonth->endsAt()->toDateTimeString())->toBe('2026-07-31 23:59:59');
});

test('this quarter starts at the quarter boundary', function () use ($lifetime): void {
    expect(DatePeriod::ThisQuarter->startsAt($lifetime())->toDateTimeString())->toBe('2026-07-01 00:00:00');
});

test('last quarter spans the whole previous quarter', function () use ($lifetime): void {
    expect(DatePeriod::LastQuarter->startsAt($lifetime())->toDateTimeString())->toBe('2026-04-01 00:00:00')
        ->and(DatePeriod::LastQuarter->endsAt()->toDateTimeString())->toBe('2026-06-30 23:59:59');
});

test('this year starts on january first', function () use ($lifetime): void {
    expect(DatePeriod::ThisYear->startsAt($lifetime())->toDateTimeString())->toBe('2026-01-01 00:00:00');
});

test('lifetime starts at the resolved earliest record', function () use ($lifetime): void {
    expect(DatePeriod::Lifetime->startsAt($lifetime())->toDateTimeString())->toBe('2024-02-05 00:00:00')
        ->and(DatePeriod::Lifetime->endsAt()->toDateTimeString())->toBe('2026-08-27 23:59:59');
});

test('the lifetime start is only resolved for the lifetime period', function (): void {
    $resolved = false;
    $lifetimeStart = function () use (&$resolved) {
        $resolved = true;

        return Date::parse('2024-02-05');
    };

    DatePeriod::Last30Days->startsAt($lifetimeStart);
    expect($resolved)->toBeFalse();

    DatePeriod::Lifetime->startsAt($lifetimeStart);
    expect($resolved)->toBeTrue();
});

test('a custom period cannot resolve its own bounds', function () use ($lifetime): void {
    expect(fn () => DatePeriod::Custom->startsAt($lifetime()))->toThrow(LogicException::class)
        ->and(fn () => DatePeriod::Custom->endsAt())->toThrow(LogicException::class);
});
