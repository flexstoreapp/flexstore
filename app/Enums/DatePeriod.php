<?php

declare(strict_types=1);

namespace App\Enums;

use Carbon\CarbonInterface;
use Closure;
use Illuminate\Support\Facades\Date;
use LogicException;

enum DatePeriod: string
{
    case Today = 'today';
    case Yesterday = 'yesterday';
    case Last7Days = '7d';
    case Last30Days = '30d';
    case ThisMonth = 'this-month';
    case LastMonth = 'last-month';
    case ThisQuarter = 'this-quarter';
    case LastQuarter = 'last-quarter';
    case ThisYear = 'this-year';
    case Lifetime = 'lifetime';
    case Custom = 'custom';

    /**
     * @param  Closure(): CarbonInterface  $lifetimeStart  resolved only when the period is {@see self::Lifetime}
     *
     * @throws LogicException when called on {@see self::Custom}, which resolves from request input
     */
    public function startsAt(Closure $lifetimeStart): CarbonInterface
    {
        return match ($this) {
            self::Today => Date::now()->startOfDay(),
            self::Yesterday => Date::now()->subDay()->startOfDay(),
            self::Last7Days => Date::now()->subDays(6)->startOfDay(),
            self::Last30Days => Date::now()->subDays(29)->startOfDay(),
            self::ThisMonth => Date::now()->startOfMonth(),
            self::LastMonth => Date::now()->subMonthNoOverflow()->startOfMonth(),
            self::ThisQuarter => Date::now()->startOfQuarter(),
            self::LastQuarter => Date::now()->subQuarterNoOverflow()->startOfQuarter(),
            self::ThisYear => Date::now()->startOfYear(),
            self::Lifetime => $lifetimeStart()->startOfDay(),
            self::Custom => throw new LogicException('Custom periods resolve their start from request input.'),
        };
    }

    /**
     * @throws LogicException when called on {@see self::Custom}, which resolves from request input
     */
    public function endsAt(): CarbonInterface
    {
        return match ($this) {
            self::Yesterday => Date::now()->subDay()->endOfDay(),
            self::LastMonth => Date::now()->subMonthNoOverflow()->endOfMonth(),
            self::LastQuarter => Date::now()->subQuarterNoOverflow()->endOfQuarter(),
            self::Custom => throw new LogicException('Custom periods resolve their end from request input.'),
            default => Date::now()->endOfDay(),
        };
    }
}
