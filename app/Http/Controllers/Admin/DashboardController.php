<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Requests\Admin\ShowDashboardRequest;
use App\Queries\DashboardStatsQuery;
use App\Queries\PeriodRangesQuery;
use Inertia\Inertia;
use Inertia\Response;

final readonly class DashboardController
{
    public function index(ShowDashboardRequest $request, DashboardStatsQuery $dashboardStatsQuery, PeriodRangesQuery $periodRangesQuery): Response
    {
        $from = $request->dateFrom();
        $to = $request->dateTo();

        return Inertia::render('admin/dashboard', [
            'period' => $request->period()->value,
            'from' => $from->format('Y-m-d'),
            'to' => $to->format('Y-m-d'),
            'periods' => $periodRangesQuery->execute(),
            ...$dashboardStatsQuery->execute($from, $to),
        ]);
    }
}
